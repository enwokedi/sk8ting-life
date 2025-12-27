<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'sk8ting_life_db' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '/16cYn,rC,;.v7C6n;V1_hMvI0HrJG{Mpzv_{)L:~QfF36;~|]Tyj wOqS<{(ZQO' );
define( 'SECURE_AUTH_KEY',  'i|w*,xw> 9W?ATxlj1|{7>o^[#<Yt@D9.K<<j: 70,8WS7l4X(HvtubdBKOB;CZZ' );
define( 'LOGGED_IN_KEY',    'P@,ch6(f%~$c_yyfoJMeTh;<4v2bz#:opnl%~]2@67CyKy,snYwlA#A`A`BL%|b)' );
define( 'NONCE_KEY',        ';}X.qeLI9A+:/}OOdVLz7]&fJheSmd&!nJos^gXxP*s$GK*?$EjgS;hHB;drn)lh' );
define( 'AUTH_SALT',        'M|4mF+k8Jp8WBGPgAy>wH 8y.>Hc]|ZDy<a7jPGpA>s%;q@B. 3@xHg#:{qO>S3y' );
define( 'SECURE_AUTH_SALT', 'hCLT7XPlPnhu>XaX HWkq^Jwe>Q_jV#DkX3D%2%p1!a.Bc#EU#/%#Wa2o~uoZE_`' );
define( 'LOGGED_IN_SALT',   'KQvP?pm~;2_bS>^=4j}$ZaYY,QLJW*bzoUa$e741?kj|-;[5t>EA8^Gg`{6m.2s{' );
define( 'NONCE_SALT',       'K&;#.EBa!~o1H1QMD2Wl9yoDQXgX*Mp89|EbFdq+JnY-yafzhUH.J`_@!hW:-<1}' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
