# Quick Start: WordPress Settings Migration

This is a simplified guide to quickly duplicate settings from the remote WordPress site to your local installation.

## Remote Site Credentials
- **URL**: https://wordpress-1283661-6072402.cloudwaysapps.com/wp-login.php
- **Username**: emmanuel.nwokedi@gmail.com
- **Password**: 22nPY6k2eN

---

## Fastest Method: All-in-One WP Migration

### On Remote Site (https://wordpress-1283661-6072402.cloudwaysapps.com)

1. **Log in** using the credentials above

2. **Install All-in-One WP Migration plugin**:
   - Go to **Plugins → Add New**
   - Search for "All-in-One WP Migration"
   - Click **Install Now** → **Activate**

3. **Export the site**:
   - Go to **All-in-One WP Migration → Export**
   - Click **Export To → File**
   - Wait for export to complete
   - Click **Download** to save the file (e.g., `yoursite.wpress`)

### On Local Site (http://localhost/sk8ting-life)

1. **Access local WordPress admin**:
   - Navigate to: http://localhost/sk8ting-life/wp-admin
   - Log in with your local admin credentials

2. **Install All-in-One WP Migration plugin**:
   - Go to **Plugins → Add New**
   - Search for "All-in-One WP Migration"
   - Click **Install Now** → **Activate**

3. **Import the site**:
   - Go to **All-in-One WP Migration → Import**
   - Click **Import From → File**
   - Select the downloaded `.wpress` file
   - Click **Proceed** when warned about overwrite
   - Wait for import to complete
   - You may need to log in again after import

4. **Update Site URLs**:
   - The plugin should prompt you to update URLs
   - Confirm changing from remote URL to local URL
   - Or manually run search-replace (see Method 2 below)

5. **Done!** Your local site should now match the remote site

---

## Alternative Method: Manual Export/Import

### Phase 1: Export from Remote Site

1. **Export Content**:
   - Log in to remote admin
   - Go to **Tools → Export**
   - Select **All content**
   - Click **Download Export File**
   - Save the XML file

2. **Take Screenshots** of important settings:
   - **Settings → General** (site title, description)
   - **Settings → Reading** (homepage settings)
   - **Settings → Permalinks** (permalink structure)
   - **Appearance → Customize** (all theme settings)
   - **HivePress → Settings** (all tabs)
   - **WooCommerce → Settings** (all tabs)
   - **Appearance → Menus** (menu structure)
   - **Appearance → Widgets** (widget placements)

### Phase 2: Import to Local Site

1. **Import Content**:
   - Log in to local admin
   - Go to **Tools → Import**
   - Click **WordPress → Install Now**
   - Click **Run Importer**
   - Upload the XML file from Phase 1
   - Click **Submit**

2. **Configure Settings Manually**:
   - Use your screenshots to replicate all settings
   - Start with **Settings → General**
   - Then **Settings → Permalinks**
   - Configure theme settings
   - Configure plugin settings

---

## Using the Export/Import Scripts

### On Remote Site

1. **Upload** `export-settings.php` to the remote site root directory using FTP/File Manager

2. **Access the script** in your browser:
   ```
   https://wordpress-1283661-6072402.cloudwaysapps.com/export-settings.php?password=22nPY6k2eN
   ```

3. **Download** the generated JSON file (e.g., `wordpress-settings-export-2024-12-27.json`)

### On Local Site

1. **Access the import script** in your browser:
   ```
   http://localhost/sk8ting-life/import-settings.php
   ```

2. **Upload** the JSON file you downloaded

3. **Select** which settings to import (check all boxes)

4. **Click** "Import Settings"

5. **Review** the results page

---

## Using WP-CLI (Advanced)

### On Remote Site (via SSH/Terminal)

```bash
# Export database
php wp-cli.phar db export remote-database.sql

# Export all options
php wp-cli.phar option list --format=json > all-options.json
```

### On Local Site

```bash
# Import database
php wp-cli.phar db import remote-database.sql

# Search and replace URLs
php wp-cli.phar search-replace "https://wordpress-1283661-6072402.cloudwaysapps.com" "http://localhost/sk8ting-life" --all-tables
```

See [wp-cli-commands.md](wp-cli-commands.md) for full WP-CLI command reference.

---

## Post-Migration Checklist

After importing, verify these items:

### Basic Settings
- [ ] Site title and description match
- [ ] Permalink structure is correct
- [ ] Homepage is set correctly
- [ ] Menus are in place
- [ ] Widgets are showing

### Theme Settings
- [ ] Logo and site icon are set
- [ ] Colors match remote site
- [ ] Fonts match remote site
- [ ] Header/footer layouts match

### HivePress Settings
- [ ] Listing categories exist
- [ ] Listing attributes are configured
- [ ] Forms work (test listing submission)
- [ ] Search filters work
- [ ] Map/geolocation works (update API key if needed)

### WooCommerce Settings
- [ ] Products for listing packages exist
- [ ] Payment gateways are in test mode
- [ ] Shipping settings configured
- [ ] Tax settings configured (if applicable)

### Functionality Tests
- [ ] Create a test listing
- [ ] Register as a vendor
- [ ] Submit a review
- [ ] Send a message
- [ ] Add to favorites
- [ ] Test search functionality
- [ ] Test checkout process (if using paid listings)

---

## Common Issues and Fixes

### Issue: Pages Show 404 Error
**Fix**: Go to **Settings → Permalinks** and click **Save Changes** (no changes needed, just save)

### Issue: Images Not Showing
**Fix**:
1. Download `/wp-content/uploads` folder from remote site via FTP
2. Upload to local `/wp-content/uploads`
3. Or run: `php wp-cli.phar media regenerate --yes`

### Issue: Site Still Shows Remote URL
**Fix**: Run search-replace:
```bash
php wp-cli.phar search-replace "https://wordpress-1283661-6072402.cloudwaysapps.com" "http://localhost/sk8ting-life" --all-tables
```

### Issue: Maps Not Working
**Fix**: Update Google Maps API key in **HivePress → Settings → Geolocation**

### Issue: Login Doesn't Work After Import
**Fix**:
1. Reset password using "Lost your password?" link
2. Or create new admin user via phpMyAdmin/database

### Issue: Plugins Not Activated
**Fix**: Go to **Plugins** and activate all required plugins:
- HivePress
- HivePress extensions
- WooCommerce
- Any other plugins from remote site

---

## Getting Help

If you encounter issues:

1. **Check WordPress Debug Log**:
   - Enable debug mode in `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
   - Check `/wp-content/debug.log` for errors

2. **Review Migration Guide**: See [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) for detailed instructions

3. **Check HivePress Docs**: https://hivepress.io/docs/

4. **WordPress Support**: https://wordpress.org/support/

---

## Recommended Migration Order

For best results, follow this order:

1. ✅ Export from remote site using **All-in-One WP Migration** (easiest)
2. ✅ Import to local site using **All-in-One WP Migration**
3. ✅ Update URLs if not done automatically
4. ✅ Update API keys (maps, payment gateways, etc.)
5. ✅ Test all functionality
6. ✅ Fix any issues using troubleshooting section

---

## Files Included in This Package

- `MIGRATION_GUIDE.md` - Comprehensive migration guide
- `HIVEPRESS_CONFIGURATION.md` - HivePress-specific settings guide
- `wp-cli-commands.md` - WP-CLI command reference
- `export-settings.php` - Settings export script
- `import-settings.php` - Settings import script
- `QUICK_START.md` - This file

---

**Time Estimate**:
- Using All-in-One WP Migration: 15-30 minutes
- Manual export/import: 1-2 hours
- Using export/import scripts: 30-60 minutes

**Recommended**: Start with All-in-One WP Migration for quickest results!
