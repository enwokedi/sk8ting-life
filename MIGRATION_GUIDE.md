# WordPress Settings Migration Guide

## Remote Site Information
- **URL**: https://wordpress-1283661-6072402.cloudwaysapps.com
- **Username**: emmanuel.nwokedi@gmail.com
- **Password**: 22nPY6k2eN

## Local Site Information
- **Database**: sk8ting_life_db
- **Theme**: ListingHive (HivePress)
- **Plugins**: HivePress suite + WooCommerce

---

## Migration Checklist

### Phase 1: Content Export from Remote Site

#### Step 1: Export WordPress Content
1. Log in to remote site: https://wordpress-1283661-6072402.cloudwaysapps.com/wp-login.php
2. Go to **Tools → Export**
3. Select **All content** (Posts, Pages, Media, etc.)
4. Click **Download Export File**
5. Save the XML file to your local machine

#### Step 2: Export Media Files
1. Install **All-in-One WP Migration** plugin on remote site (if not present):
   - Go to **Plugins → Add New**
   - Search for "All-in-One WP Migration"
   - Install and activate
2. Go to **All-in-One WP Migration → Export**
3. Export to **File**
4. Download the export file

#### Step 3: Export Theme Settings
1. Go to **Appearance → Customize**
2. For ListingHive theme, export customizer settings:
   - Look for Export/Import option in Customizer
   - Or use **Customizer Export/Import** plugin
3. Document any custom CSS from **Appearance → Customize → Additional CSS**

#### Step 4: Export Plugin Settings

**HivePress Settings:**
1. Go to **HivePress → Settings**
2. Take screenshots or document settings for:
   - Listings
   - Vendors
   - Categories
   - Attributes
   - Forms
   - Emails
3. Export marketplace settings (if available through Settings → Import/Export)

**WooCommerce Settings:**
1. Go to **WooCommerce → Settings**
2. Use **WooCommerce Settings Export** or document:
   - General settings
   - Products
   - Shipping
   - Payments
   - Accounts & Privacy
   - Emails
3. Go to **WooCommerce → System Status → Tools**
4. Use "Export System Status Report" for reference

#### Step 5: Export Widgets and Menus
1. Go to **Appearance → Widgets**
2. Install **Widget Importer & Exporter** plugin
3. Go to **Tools → Widget Importer & Exporter**
4. Export widgets as JSON file

5. Go to **Appearance → Menus**
6. Document menu structure or use theme export tools

#### Step 6: Export Site Options
1. Install **WP-CFM (Configuration Management)** plugin
2. Or use WP-CLI to export options (see scripts below)
3. Key options to note:
   - Site title, tagline, URL
   - Reading settings
   - Discussion settings
   - Permalink structure
   - Homepage and blog page settings

---

### Phase 2: Import to Local Site

#### Step 1: Prepare Local Site
1. Ensure WordPress is installed with same version as remote
2. Install and activate the same theme (ListingHive)
3. Install and activate all plugins:
   - HivePress core
   - HivePress extensions (Authentication, Claim Listings, Favorites, Geolocation, Messages, Paid Listings, Reviews)
   - WooCommerce

#### Step 2: Import Content
1. Log in to local WordPress admin
2. Go to **Tools → Import**
3. Install **WordPress Importer** if not present
4. Click **Run Importer**
5. Upload the XML file from Phase 1, Step 1
6. Assign authors or create new users
7. Check "Download and import file attachments"
8. Click **Submit**

#### Step 3: Import Media (using All-in-One WP Migration)
1. Install **All-in-One WP Migration** on local site
2. Go to **All-in-One WP Migration → Import**
3. Upload the export file from Phase 1, Step 2
4. Wait for import to complete

**OR manually download media:**
1. Use FTP/SFTP to download `/wp-content/uploads` from remote site
2. Copy to local `/wp-content/uploads` directory

#### Step 4: Import Theme Settings
1. Go to **Appearance → Customize**
2. If ListingHive has import feature, use it
3. Or manually configure based on screenshots/documentation
4. Add any custom CSS to **Additional CSS** section
5. Click **Publish**

#### Step 5: Import Plugin Settings

**HivePress:**
1. Go to **HivePress → Settings**
2. Manually configure each section based on remote settings
3. If export file available, use import feature
4. Configure:
   - Listing types and attributes
   - Vendor settings
   - Email templates
   - Form fields

**WooCommerce:**
1. Go to **WooCommerce → Settings**
2. Manually configure each tab
3. Set up payment gateways
4. Configure shipping zones and methods
5. Set tax rates if applicable

#### Step 6: Import Widgets and Menus
1. Install **Widget Importer & Exporter** on local site
2. Go to **Tools → Widget Importer & Exporter → Import**
3. Upload the widget JSON file
4. Click **Import Widgets**

5. Go to **Appearance → Menus**
6. Recreate menu structure or use theme import

#### Step 7: Configure Site Options
1. Go to **Settings → General**
   - Set site title and tagline
   - Set WordPress Address (URL) to local URL
   - Set Site Address (URL) to local URL
   - Set timezone, date format, time format

2. Go to **Settings → Reading**
   - Set homepage displays (static page or latest posts)
   - Select homepage and blog page

3. Go to **Settings → Discussion**
   - Configure comment settings

4. Go to **Settings → Permalinks**
   - Set permalink structure to match remote site
   - Click **Save Changes**

---

### Phase 3: Verification

#### Checklist:
- [ ] All pages imported correctly
- [ ] All posts imported correctly
- [ ] All media files accessible
- [ ] Theme looks identical to remote site
- [ ] All menus functioning
- [ ] All widgets displaying correctly
- [ ] HivePress listings displaying correctly
- [ ] HivePress vendor features working
- [ ] WooCommerce products displaying
- [ ] WooCommerce checkout process working
- [ ] All forms working (contact, listing submission)
- [ ] Email templates configured
- [ ] User roles and permissions set
- [ ] Permalink structure matches

---

### Phase 4: Additional Considerations

#### Database Search and Replace
If importing from production URL to local development:
1. Use **Better Search Replace** plugin
2. Or use WP-CLI: `wp search-replace 'https://wordpress-1283661-6072402.cloudwaysapps.com' 'http://localhost/sk8ting-life'`
3. Search for hardcoded URLs in:
   - Post content
   - Page content
   - Widget content
   - Theme options
   - Plugin settings

#### Users and Roles
1. Go to **Users → All Users**
2. Verify user roles match remote site
3. Create any missing users
4. Update user meta if needed

#### Custom Code
1. Check if remote site has any custom code in:
   - Theme's `functions.php`
   - Custom plugins
   - `wp-config.php` (custom constants)
2. Copy to local site if needed

---

## Troubleshooting

### Import Errors
- If XML import fails: Split the export file into smaller chunks
- If media import fails: Manually upload via FTP
- If import times out: Increase PHP limits in `php.ini`:
  ```
  max_execution_time = 300
  memory_limit = 256M
  post_max_size = 64M
  upload_max_filesize = 64M
  ```

### Missing Content
- Check **Trash** for deleted items
- Re-run importer with different options
- Verify XML file contains all content

### Broken Images/Links
- Run search-replace for URLs
- Regenerate thumbnails: **Tools → Regen. Thumbnails** (plugin)
- Check file permissions on uploads directory

---

## Alternative: Use Migration Plugin

For easier migration, use one of these plugins on both sites:

### All-in-One WP Migration (Recommended)
1. Install on both sites
2. Export from remote: **All-in-One WP Migration → Export → File**
3. Import to local: **All-in-One WP Migration → Import**
4. Upload the export file
5. Replace URLs during import

### Duplicator
1. Install on remote site
2. Create package: **Duplicator → Packages → Create New**
3. Download installer.php and archive.zip
4. Upload both files to local site root
5. Run installer.php in browser
6. Follow wizard to import

---

## Scripts for Advanced Users

See the following files for automated export/import:
- `export-settings.php` - Export all WordPress options and settings
- `import-settings.php` - Import settings from JSON file
- `wp-cli-export.sh` - WP-CLI commands to export settings
- `wp-cli-import.sh` - WP-CLI commands to import settings

---

## Notes

- Always backup local database before importing
- Test all functionality after import
- Update any API keys or credentials for local environment
- Disable caching plugins during import
- Clear all caches after import
