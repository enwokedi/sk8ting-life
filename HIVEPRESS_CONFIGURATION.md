# HivePress Configuration Guide

This guide helps you replicate HivePress and ListingHive theme settings from the remote WordPress site to your local installation.

## About Your Setup

- **Theme**: ListingHive (HivePress compatible theme)
- **Core Plugin**: HivePress
- **Extensions**:
  - HivePress Authentication
  - HivePress Claim Listings
  - HivePress Favorites
  - HivePress Geolocation
  - HivePress Messages
  - HivePress Paid Listings
  - HivePress Reviews

---

## Step 1: HivePress Core Settings

### Accessing Settings
1. Log in to WordPress admin on remote site
2. Navigate to **HivePress → Settings**

### Settings to Document/Export

#### General Tab
- **Listing submission**: How listings are submitted (free, paid, require approval)
- **Listing moderation**: Auto-publish or require approval
- **Default listing image**: Upload/note the default image
- **Listing expiration**: Set expiration days
- **Allow renewals**: Enable/disable listing renewals

#### Vendors Tab
- **Vendor registration**: Allow users to become vendors
- **Vendor approval**: Auto-approve or manual approval
- **Vendor page**: Assign vendor page template
- **Default vendor image**: Upload/note the default image

#### Categories Tab
- List all listing categories
- Note category hierarchy
- Document category images/icons if used

#### Attributes Tab
Document all custom listing attributes:
- Field name
- Field type (text, number, select, etc.)
- Required/Optional
- Searchable/Filterable
- Display location

#### Search Tab
- **Search fields**: Which attributes are searchable
- **Default sorting**: How results are sorted by default
- **Results per page**: Number of listings per page

#### Emails Tab
Document email template settings:
- **Listing submitted**: Email sent when listing is submitted
- **Listing published**: Email when listing goes live
- **Listing expired**: Email when listing expires
- **Review submitted**: Email when review is posted

---

## Step 2: ListingHive Theme Settings

### Accessing Theme Customizer
1. Go to **Appearance → Customize**

### Settings to Document

#### Site Identity
- Site title
- Tagline
- Logo
- Site icon (favicon)

#### Colors
- Primary color
- Secondary color
- Link color
- Background color
- Text color
- Heading color

#### Typography
- Font family for headings
- Font family for body text
- Font sizes

#### Header Settings
- Header layout
- Header background
- Sticky header: Enabled/disabled
- Top bar: Enabled/disabled
- Search bar in header: Enabled/disabled

#### Footer Settings
- Footer layout
- Footer widgets
- Footer text
- Social media links

#### Homepage Settings
- **Static front page** or **Latest posts**
- If static, which page is set as homepage
- Featured sections enabled
- Hero section settings
- Call-to-action sections

#### Listing Settings (Theme-specific)
- Listing card layout
- Listing grid columns
- Show/hide listing attributes
- Default listing image size

#### Vendor Settings (Theme-specific)
- Vendor card layout
- Vendor profile layout
- Show/hide vendor information

---

## Step 3: HivePress Extension Settings

### HivePress Geolocation
1. Navigate to **HivePress → Settings → Geolocation**
2. Document:
   - Map provider (Google Maps, OpenStreetMap, Mapbox)
   - API keys
   - Default map center (latitude/longitude)
   - Default zoom level
   - Map markers style
   - Radius search enabled/disabled

### HivePress Reviews
1. Navigate to **HivePress → Settings → Reviews**
2. Document:
   - Review approval: Auto-approve or manual
   - Rating scale (1-5, 1-10, etc.)
   - Allow anonymous reviews
   - Require purchase/booking for review
   - Review display order

### HivePress Messages
1. Navigate to **HivePress → Settings → Messages**
2. Document:
   - Message storage (database or external)
   - Email notifications for new messages
   - Message read receipts

### HivePress Favorites
1. Navigate to **HivePress → Settings → Favorites**
2. Document:
   - Allow guests to favorite
   - Favorite icon/button style
   - Display favorites count

### HivePress Claim Listings
1. Navigate to **HivePress → Settings → Claim Listings**
2. Document:
   - Claim approval process
   - Required fields for claims
   - Verification method

### HivePress Paid Listings
1. Navigate to **HivePress → Settings → Paid Listings**
2. Document:
   - Payment gateway (WooCommerce integration)
   - Listing packages/pricing
   - Free trial period
   - Featured listings pricing
   - Renewal pricing

### HivePress Authentication
1. Navigate to **HivePress → Settings → Authentication**
2. Document:
   - Social login providers (Facebook, Google, etc.)
   - API keys and secrets
   - Redirect URLs after login
   - Registration form fields

---

## Step 4: WooCommerce Integration Settings

### Product Settings
1. Navigate to **Products → All Products**
2. Document HivePress-created products:
   - Listing packages
   - Featured listing add-ons
   - Any subscription products

### Payment Gateway Settings
1. Navigate to **WooCommerce → Settings → Payments**
2. Document enabled payment methods:
   - PayPal
   - Stripe
   - Other gateways
   - API credentials (update for local testing)

---

## Step 5: Forms Configuration

### Listing Submission Forms
1. Navigate to **HivePress → Forms → Add Listing**
2. Document all form fields:
   - Field name
   - Field type
   - Required/Optional
   - Validation rules
   - Display order

### Vendor Registration Forms
1. Navigate to **HivePress → Forms → Register Vendor**
2. Document form structure

### Search Forms
1. Navigate to **HivePress → Forms → Search Listings**
2. Document search fields and filters

---

## Step 6: Menu Configuration

### Primary Menu
1. Navigate to **Appearance → Menus**
2. Document menu structure:
   - Menu name
   - Menu location (Primary, Footer, etc.)
   - Menu items and hierarchy
   - Custom links
   - Categories/pages included

### Footer Menu
1. Document footer menu items

---

## Step 7: Widget Configuration

### Sidebar Widgets
1. Navigate to **Appearance → Widgets**
2. Document widgets in each widget area:
   - Widget name
   - Widget settings
   - Widget order

Common HivePress widgets:
- Listing Categories
- Search Listings
- Featured Listings
- Latest Listings
- Listing Attributes Filter

---

## Step 8: Pages and Templates

### Required HivePress Pages

Document which pages are assigned for:
1. **Listings Archive**: Page displaying all listings
2. **Submit Listing**: Listing submission form page
3. **Vendors Archive**: Page displaying all vendors
4. **Vendor Dashboard**: Where vendors manage their listings
5. **User Account**: User profile and account page
6. **Search Results**: Search results page

### Page Templates
Note which template is assigned to each page:
- Default
- Full Width
- Listing Archive
- Vendor Archive
- etc.

---

## Step 9: Custom Post Types and Taxonomies

### Listing Post Type
1. Navigate to **Listings → All Listings**
2. Document:
   - Sample listing structure
   - Custom fields
   - Featured image requirements

### Listing Categories
1. Navigate to **Listings → Categories**
2. Export category list with:
   - Category name
   - Slug
   - Parent category
   - Description
   - Category image

### Listing Tags
1. Document if tags are used

---

## Step 10: Attributes and Custom Fields

### Listing Attributes
1. Navigate to **HivePress → Attributes**
2. For each attribute, document:
   - Attribute name
   - Attribute slug
   - Field type (text, select, number, date, etc.)
   - Required field
   - Editable after submission
   - Searchable
   - Display in listings
   - Options (for select/radio/checkbox fields)

Example attributes commonly used:
- Location
- Price
- Category
- Features/Amenities
- Business Hours
- Contact Information
- Social Media Links

---

## Step 11: Export Settings Using Export Script

1. Upload `export-settings.php` to remote site root directory
2. Log in to WordPress as admin
3. Access: `https://wordpress-1283661-6072402.cloudwaysapps.com/export-settings.php?password=22nPY6k2eN`
4. Download the generated JSON file
5. Save as `wordpress-settings-export.json`

---

## Step 12: Import Settings to Local Site

### Method 1: Using Import Script
1. Place `wordpress-settings-export.json` in local WordPress root
2. Access: `http://localhost/sk8ting-life/import-settings.php`
3. Upload the JSON file
4. Select which settings to import
5. Click "Import Settings"

### Method 2: Manual Configuration
Use the documentation from Steps 1-10 to manually configure each setting in the WordPress admin panel.

---

## Step 13: Post-Import Verification

### Check List
- [ ] All HivePress settings configured
- [ ] Theme customizer settings applied
- [ ] All plugins activated and configured
- [ ] Listing categories created
- [ ] Listing attributes created
- [ ] Forms configured (Add Listing, Search, Register)
- [ ] Menus created and assigned to locations
- [ ] Widgets added to widget areas
- [ ] Required pages created and assigned
- [ ] WooCommerce products for listing packages created
- [ ] Payment gateways configured (test mode)
- [ ] Email templates tested
- [ ] Map provider and geolocation working
- [ ] Social login providers configured (if used)
- [ ] Test listing submission works
- [ ] Test vendor registration works
- [ ] Test search and filters work
- [ ] Test reviews and ratings work
- [ ] Test messaging system works
- [ ] Test favorites system works

---

## Common HivePress Settings Reference

### Listing Statuses
- **Draft**: Not published yet
- **Pending**: Awaiting moderation
- **Publish**: Live and visible
- **Expired**: Past expiration date

### User Roles
- **Administrator**: Full access
- **Vendor**: Can submit and manage listings
- **Customer**: Can browse and favorite listings
- **Subscriber**: Basic user

### Shortcodes
- `[hivepress_listings]` - Display listings grid
- `[hivepress_listing_search_form]` - Display search form
- `[hivepress_vendor_register_form]` - Vendor registration form
- `[hivepress_listing_submit_form]` - Listing submission form

---

## Troubleshooting

### Listings Not Displaying
- Check if listing post type is registered
- Verify permalink structure
- Flush rewrite rules: **Settings → Permalinks → Save Changes**

### Forms Not Working
- Check form settings in **HivePress → Forms**
- Verify required fields are configured
- Check for JavaScript errors in browser console

### Map Not Displaying
- Verify map provider API key is set
- Check if geolocation extension is activated
- Verify JavaScript is not blocked

### Payment Not Working
- Set WooCommerce payment gateways to test mode
- Verify webhook URLs are configured
- Check WooCommerce → Status → Logs for errors

---

## Additional Resources

- **HivePress Documentation**: https://hivepress.io/docs/
- **ListingHive Theme Docs**: Check theme documentation
- **WooCommerce Docs**: https://woocommerce.com/documentation/
- **WordPress Codex**: https://codex.wordpress.org/

---

## Notes

- Screenshots are highly recommended for complex settings
- API keys should be different for local development
- Test all functionality after import
- Consider using staging environment before production
