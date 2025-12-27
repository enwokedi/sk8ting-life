<?php
/**
 * SEO component.
 *
 * @package HivePress\Components
 */

namespace HivePress\Components;

use HivePress\Helpers as hp;
use HivePress\Models;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SEO component class.
 *
 * @class SEO
 */
final class SEO extends Component {

	/**
	 * Model names.
	 *
	 * @var array
	 */
	protected $models = [ 'listing', 'vendor', 'request' ];

	/**
	 * Meta tags.
	 *
	 * @var array
	 */
	protected $meta = [];

	/**
	 * Schema properties.
	 *
	 * @var array
	 */
	protected $schema = [];

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Clear cache.
		foreach ( $this->models as $model ) {
			add_action( 'hivepress/v1/models/' . $model . '/update', [ $this, 'clear_cache' ] );
		}

		if ( is_admin() ) {

			// Add settings.
			add_filter( 'hivepress/v1/settings', [ $this, 'add_settings' ] );

			// Add meta boxes.
			add_filter( 'hivepress/v1/meta_boxes', [ $this, 'add_meta_boxes' ] );

			// Filter schema properties.
			add_filter( 'hivepress/v1/schema_properties', [ $this, 'filter_schema_properties' ] );
		} else {

			// Setup schema.
			add_action( 'wp_head', [ $this, 'setup_schema' ], 0 );

			// Render schema.
			add_action( 'wp_head', [ $this, 'render_schema' ], 1 );

			if ( hp\is_plugin_active( 'rankmath' ) ) {

				// Manage Rank Math.
				add_filter( 'rank_math/frontend/robots', [ $this, 'alter_rank_math_tags' ] );
				add_filter( 'rank_math/frontend/title', [ $this, 'alter_rank_math_tags' ] );
				add_filter( 'rank_math/frontend/description', [ $this, 'alter_rank_math_tags' ] );

				add_filter( 'rank_math/opengraph/facebook/image', [ $this, 'alter_rank_math_tags' ] );
				add_filter( 'rank_math/opengraph/twitter/image', [ $this, 'alter_rank_math_tags' ] );

				add_filter( 'rank_math/json_ld', [ $this, 'alter_rank_math_schema' ] );
			} elseif ( class_exists( 'WPSEO_Options' ) ) {

				// Manage Yoast.
				add_filter( 'wpseo_robots', [ $this, 'alter_yoast_tags' ] );
				add_filter( 'wpseo_title', [ $this, 'alter_yoast_tags' ] );
				add_filter( 'wpseo_metadesc', [ $this, 'alter_yoast_tags' ] );

				add_filter( 'wpseo_opengraph_title', [ $this, 'alter_yoast_tags' ] );
				add_filter( 'wpseo_opengraph_desc', [ $this, 'alter_yoast_tags' ] );
				add_filter( 'wpseo_opengraph_image', [ $this, 'alter_yoast_tags' ] );

				add_filter( 'wpseo_twitter_title', [ $this, 'alter_yoast_tags' ] );
				add_filter( 'wpseo_twitter_description', [ $this, 'alter_yoast_tags' ] );
				add_filter( 'wpseo_twitter_image', [ $this, 'alter_yoast_tags' ] );
			} else {

				// Set page title.
				add_filter( 'document_title_parts', [ $this, 'set_page_title' ], 100 );
			}
		}

		parent::__construct( $args );
	}

	/**
	 * Clears cache.
	 *
	 * @param int $id Object ID.
	 */
	public function clear_cache( $id ) {
		hivepress()->cache->delete_post_cache( $id, 'schema' );
	}

	/**
	 * Adds settings.
	 *
	 * @param array $settings Setting.
	 * @return array
	 */
	public function add_settings( $settings ) {

		// Set fields.
		$fields = [
			'robot_tags'      => [
				'label'       => esc_html__( 'Robot Tags', 'hivepress-seo' ),
				'description' => esc_html__( 'Select tags to instruct search engines on how to crawl pages.', 'hivepress-seo' ),
				'type'        => 'select',
				'multiple'    => true,
				'_order'      => 10,

				'options'     => [
					'noindex'      => 'noindex',
					'nofollow'     => 'nofollow',
					'noarchive'    => 'noarchive',
					'noimageindex' => 'noimageindex',
					'nosnippet'    => 'nosnippet',
				],
			],

			'schema_type'     => [
				'label'       => esc_html__( 'Schema Type', 'hivepress-seo' ),
				'description' => esc_html__( 'Choose a Schema.org type that matches the page content.', 'hivepress-seo' ),
				'type'        => 'select',
				'options'     => 'schema_types',
				'_order'      => 20,
			],

			'meta_title'      => [
				'label'       => esc_html__( 'Meta Title', 'hivepress-seo' ),
				'description' => esc_html__( 'Set the page title format for the search engines based on attributes.', 'hivepress-seo' ) . ' ' . sprintf( hivepress()->translator->get_string( 'these_tokens_are_available' ), '%model%' ),
				'type'        => 'text',
				'max_length'  => 256,
				'_order'      => 30,
			],

			'meta_descripton' => [
				'label'       => esc_html__( 'Meta Description', 'hivepress-seo' ),
				'description' => esc_html__( 'Set the page description format for the search engines based on attributes.', 'hivepress-seo' ) . ' ' . sprintf( hivepress()->translator->get_string( 'these_tokens_are_available' ), '%model%' ),
				'type'        => 'textarea',
				'max_length'  => 512,
				'_order'      => 40,
			],
		];

		// Add settings.
		foreach ( $this->models as $model ) {
			if ( isset( $settings[ $model . 's' ] ) ) {
				$section = [
					'title'  => esc_html__( 'SEO', 'hivepress-seo' ),
					'fields' => [],
					'_order' => 200,
				];

				foreach ( $fields as $field_name => $field ) {
					if ( isset( $field['description'] ) ) {
						$field['description'] = hp\replace_tokens( [ 'model' => '%' . $model . '%' ], $field['description'] );
					}

					$section['fields'][ $model . '_' . $field_name ] = $field;
				}

				$settings[ $model . 's' ]['sections']['seo'] = $section;
			}
		}

		return $settings;
	}

	/**
	 * Adds meta boxes.
	 *
	 * @param array $meta_boxes Meta boxes.
	 * @return array
	 */
	public function add_meta_boxes( $meta_boxes ) {
		foreach ( $this->models as $model ) {
			if ( isset( $meta_boxes[ $model . '_attribute_edit' ] ) ) {
				$meta_boxes[ $model . '_attribute_seo' ] = [
					'title'  => esc_html__( 'SEO', 'hivepress-seo' ),
					'screen' => $model . '_attribute',
					'model'  => $model,

					'fields' => [
						'schema_property' => [
							'label'       => esc_html__( 'Schema Property', 'hivepress-seo' ),
							'description' => esc_html__( 'Choose a Schema.org property that matches the attribute value.', 'hivepress-seo' ),
							'type'        => 'select',
							'options'     => 'schema_properties',
							'_order'      => 10,
						],
					],
				];
			}
		}

		return $meta_boxes;
	}

	/**
	 * Filters schema properties.
	 *
	 * @param array $properties Schema properties.
	 * @return array
	 */
	public function filter_schema_properties( $properties ) {
		global $pagenow;

		// Check page.
		if ( ! in_array( $pagenow, [ 'post.php', 'post-new.php' ] ) ) {
			return [];
		}

		// Get post type.
		$post_type = get_post_type();

		if ( strpos( $post_type, 'hp_' ) !== 0 ) {
			return [];
		}

		// Get model name.
		$model = preg_replace( '/_attribute$/', '', hp\unprefix( $post_type ) );

		if ( ! in_array( $model, $this->models ) ) {
			return [];
		}

		// Get schema type.
		$schema_type = get_option( hp\prefix( $model . '_schema_type' ) );

		if ( ! $schema_type ) {
			return [];
		}

		return array_filter(
			$properties,
			function( $property ) use ( $schema_type ) {
				return isset( $property['types'] ) && in_array( $schema_type, $property['types'] );
			}
		);
	}

	/**
	 * Setups schema.
	 */
	public function setup_schema() {

		// Check page.
		if ( ! is_singular( hp\prefix( $this->models ) ) ) {
			return;
		}

		// Get model name.
		$model = hp\unprefix( get_post_type() );

		// Get model object.
		$object = hivepress()->request->get_context( $model );

		if ( ! $object ) {
			return;
		}

		// Set robots.
		$robots = get_option( hp\prefix( $model . '_robot_tags' ) );

		if ( $robots ) {
			$this->meta['robots'] = [
				'content' => implode( ',', (array) $robots ),

				'types'   => [
					'robots',
				],
			];
		}

		// Set title.
		$title = get_option( hp\prefix( $model . '_meta_title' ) );

		if ( $title ) {
			$title = hp\replace_tokens( [ $model => $object ], $title );

			if ( $title ) {
				$this->meta['title'] = [
					'content' => wp_strip_all_tags( $title ),

					'types'   => [
						'og:title',
						'twitter:title',
					],
				];
			}
		}

		// Set description.
		$description = get_option( hp\prefix( $model . '_meta_descripton' ) );

		if ( $description ) {
			$description = hp\replace_tokens( [ $model => $object ], $description );

			if ( $description ) {
				$this->meta['description'] = [
					'content' => wp_strip_all_tags( $description ),

					'types'   => [
						'description',
						'og:description',
						'twitter:description',
					],
				];
			}
		}

		// Set image.
		$image = $object->get_image__url( 'large' );

		if ( $image ) {
			$this->meta['image'] = [
				'content' => $image,

				'types'   => [
					'og:image',
					'twitter:image',
				],
			];
		}

		// Get schema type.
		$schema_type = get_option( hp\prefix( $model . '_schema_type' ) );

		if ( $schema_type ) {

			// Get cached schema.
			$this->schema = hivepress()->cache->get_post_cache( $object->get_id(), 'schema' );

			if ( is_null( $this->schema ) || $this->schema['@type'] !== $schema_type ) {

				// Set defaults.
				$this->schema = [
					'@context' => 'https://schema.org',
					'@type'    => $schema_type,
				];

				// Set properties.
				foreach ( hivepress()->attribute->get_attributes( $model, $object->get_categories__id() ) as $attribute_name => $attribute ) {

					// Check attribute.
					if ( ! isset( $attribute['id'] ) ) {
						continue;
					}

					// Get property.
					$property = get_post_meta( $attribute['id'], 'hp_schema_property', true );

					if ( ! $property ) {
						continue;
					}

					// Get value.
					$value = call_user_func( [ $object, 'display_' . $attribute_name ] );

					if ( strlen( $value ) ) {
						$this->schema[ $property ] = wp_strip_all_tags( $value );
					}
				}

				// Set name.
				if ( 'vendor' === $model && $object->get_name() ) {
					$this->schema['name'] = $object->get_name();
				} elseif ( 'vendor' !== $model && $object->get_title() ) {
					$this->schema['name'] = $object->get_title();
				}

				// Set description.
				if ( $object->get_description() ) {
					$this->schema['description'] = $object->get_description();
				}

				if ( 'listing' === $model ) {
					if ( hivepress()->get_version( 'geolocation' ) && $object->get_location() ) {

						// Set location.
						$this->schema = array_merge(
							$this->schema,
							[
								'address'   => $object->get_location(),
								'latitude'  => $object->get_latitude(),
								'longitude' => $object->get_longitude(),

								'hasMap'    => hivepress()->router->get_url(
									'location_view_page',
									[
										'latitude'  => $object->get_latitude(),
										'longitude' => $object->get_longitude(),
									]
								),
							]
						);
					}

					if ( hivepress()->get_version( 'tags' ) && $object->get_tags__id() ) {

						// Set tags.
						$this->schema['keywords'] = $object->get_tags__name();
					}

					if ( hivepress()->get_version( 'marketplace' ) && $object->get_price() ) {

						// Get product.
						$product = hivepress()->woocommerce->get_related_product( $object->get_id() );

						if ( $product ) {

							// Get currency.
							$currency = get_option( 'woocommerce_currency' );

							// Get availability.
							$availability = hp\get_array_value(
								[
									'instock'     => 'https://schema.org/InStock',
									'outofstock'  => 'https://schema.org/OutOfStock',
									'onbackorder' => 'https://schema.org/BackOrder',
								],
								$product->get_stock_status(),
								'https://schema.org/InStock'
							);

							// Get offers.
							$offer = null;

							if ( $object->get_price_tiers() ) {
								$offers = [];

								foreach ( $object->get_price_tiers() as $tier ) {
									$offers[] = [
										'@type'         => 'Offer',
										'availability'  => $availability,
										'price'         => $tier['price'],
										'priceCurrency' => $currency,
									];
								}

								// Get prices.
								$prices = wp_list_pluck( $offers, 'price' );

								// Set offer.
								$offer = [
									'@type'         => 'AggregateOffer',
									'highPrice'     => max( $prices ),
									'lowPrice'      => min( $prices ),
									'priceCurrency' => $currency,
									'offerCount'    => count( $offers ),
									'offers'        => $offers,
								];
							} else {
								$offer = [
									'@type'         => 'Offer',
									'availability'  => $availability,
									'price'         => $object->get_price(),
									'priceCurrency' => $currency,
								];
							}

							// Set offers.
							if ( $offer ) {
								$this->schema['offers'] = $offer;
							}
						}
					}
				}

				if ( in_array( $model, [ 'listing', 'vendor' ] ) ) {
					if ( hivepress()->get_version( 'reviews' ) && $object->get_rating() ) {

						// Set rating.
						$this->schema['aggregateRating'] = [
							'@type'       => 'AggregateRating',
							'ratingValue' => $object->get_rating(),
							'worstRating' => 1,
							'bestRating'  => 5,
							'ratingCount' => $object->get_rating_count(),
							'reviewCount' => $object->get_rating_count(),
						];

						// Get listing IDs.
						$listing_ids = [];

						if ( 'listing' === $model ) {
							$listing_ids = [ $object->get_id() ];
						} else {
							$listing_ids = Models\Listing::query()->filter(
								[
									'status' => 'publish',
									'vendor' => $object->get_id(),
								]
							)->order( [ 'rating_count' => 'desc' ] )
							->limit( 10 )
							->get_ids();
						}

						// Get reviews.
						$reviews = [];

						if ( $listing_ids ) {
							$reviews = Models\Review::query()->filter(
								[
									'approved'    => true,
									'listing__in' => $listing_ids,
								]
							)->order( [ 'rating' => 'desc' ] )
							->limit( 3 )
							->get();
						}

						if ( $reviews ) {

							// Set reviews.
							$this->schema['review'] = [];

							foreach ( $reviews as $review ) {
								$this->schema['review'][] = [
									'@type'         => 'Review',
									'datePublished' => gmdate( 'Y-m-d', strtotime( $review->get_created_date() ) ),
									'reviewBody'    => $review->get_text(),

									'author'        => [
										'@type' => 'Person',
										'name'  => $review->get_author__display_name(),
									],

									'reviewRating'  => [
										'@type'       => 'Rating',
										'ratingValue' => $review->get_rating(),
										'worstRating' => 1,
										'bestRating'  => 5,
									],
								];
							}
						}
					}

					// Set social links.
					if ( hivepress()->get_version( 'social_links' ) ) {
						$social_links = array_filter( array_intersect_key( $object->serialize(), hivepress()->social_links->get_links( $model ) ) );

						if ( $social_links ) {
							$this->schema['sameAs'] = array_values( $social_links );
						}
					}
				}

				// Cache schema.
				hivepress()->cache->set_post_cache( $object->get_id(), 'schema', null, $this->schema );
			}

			// Filter schema.
			$this->schema = apply_filters( 'hivepress/v1/models/' . $model . '/schema', $this->schema );
		}
	}

	/**
	 * Renders schema.
	 */
	public function render_schema() {
		$output = '';

		// Render meta tags.
		if ( ! hp\is_plugin_active( 'rankmath' ) && ! class_exists( 'WPSEO_Options' ) ) {
			foreach ( $this->meta as $tag ) {
				$attributes = [
					'content' => $tag['content'],
				];

				foreach ( $tag['types'] as $type ) {
					if ( strpos( $type, 'og:' ) === 0 ) {
						$attributes['property'] = $type;

						unset( $attributes['name'] );
					} else {
						$attributes['name'] = $type;

						unset( $attributes['property'] );
					}

					$output .= '<meta ' . hp\html_attributes( $attributes ) . '>' . PHP_EOL;
				}
			}
		}

		if ( ! hp\is_plugin_active( 'rankmath' ) && $this->schema ) {

			// Render schema.
			$output .= '<script type="application/ld+json">' . wp_json_encode(
				wp_kses_post_deep( $this->schema ),
				defined( 'HP_DEBUG' ) && HP_DEBUG ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : JSON_UNESCAPED_SLASHES
			) . '</script>';
		}

		echo $output; // phpcs:ignore
	}

	/**
	 * Sets page title.
	 *
	 * @param array $parts Title parts.
	 * @return array
	 */
	public function set_page_title( $parts ) {
		if ( isset( $this->meta['title'] ) ) {
			$parts['title'] = $this->meta['title']['content'];
		}

		return $parts;
	}

	/**
	 * Alters Rank Math meta tags.
	 *
	 * @param mixed $content Tag content.
	 * @return mixed
	 */
	public function alter_rank_math_tags( $content ) {

		// Check meta.
		if ( ! $this->meta ) {
			return $content;
		}

		// Get name.
		$name = hp\get_last_array_value( explode( '/', current_filter() ) );

		// Get tag.
		$tag = hp\get_array_value( $this->meta, $name );

		if ( $tag ) {

			// Set content.
			if ( 'robots' === $name ) {
				$content = explode( ',', $tag['content'] );
			} else {
				$content = $tag['content'];
			}
		}

		return $content;
	}

	/**
	 * Alters Rank Math schema.
	 *
	 * @param array $schemas Schemas.
	 * @return array
	 */
	public function alter_rank_math_schema( $schemas ) {
		if ( $this->schema ) {
			$schemas[] = $this->schema;
		}

		return $schemas;
	}

	/**
	 * Alters Yoast meta tags.
	 *
	 * @param mixed $content Tag content.
	 * @return mixed
	 */
	public function alter_yoast_tags( $content ) {

		// Check meta.
		if ( ! $this->meta ) {
			return $content;
		}

		// Get name.
		$name = hp\get_last_array_value( explode( '_', current_filter() ) );

		if ( in_array( $name, [ 'desc', 'metadesc' ] ) ) {
			$name = 'description';
		}

		// Get tag.
		$tag = hp\get_array_value( $this->meta, $name );

		if ( $tag ) {
			$content = $tag['content'];
		}

		return $content;
	}
}
