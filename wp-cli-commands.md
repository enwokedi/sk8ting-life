# WP-CLI Commands for WordPress Migration

This file contains WP-CLI commands to help export and import WordPress settings using the wp-cli.phar file included in this installation.

## Using WP-CLI

All commands should be run from the WordPress root directory using:
```bash
php wp-cli.phar [command]
```

Or on Windows from this directory:
```cmd
php wp-cli.phar [command]
```

---

## Export Commands (Run on Remote Server)

### 1. Export Database
```bash
php wp-cli.phar db export wordpress-backup.sql
```

### 2. Export All Options
```bash
php wp-cli.phar option list --format=json > all-options.json
```

### 3. Export Specific Option Groups
```bash
# General settings
php wp-cli.phar option get blogname
php wp-cli.phar option get blogdescription
php wp-cli.phar option get admin_email

# Permalink structure
php wp-cli.phar option get permalink_structure

# Active theme
php wp-cli.phar option get template
php wp-cli.phar option get stylesheet

# Active plugins
php wp-cli.phar option get active_plugins --format=json
```

### 4. Export Content (Posts, Pages, Media)
```bash
php wp-cli.phar export --dir=./wp-export
```

### 5. Export Menus
```bash
php wp-cli.phar menu list --format=json > menus.json
```

### 6. Export Users
```bash
php wp-cli.phar user list --format=json > users.json
```

### 7. Export Plugin List
```bash
php wp-cli.phar plugin list --format=json > plugins.json
```

### 8. Export Theme List
```bash
php wp-cli.phar theme list --format=json > themes.json
```

### 9. Export WooCommerce Settings
```bash
php wp-cli.phar option list --search="woocommerce_*" --format=json > woocommerce-settings.json
```

### 10. Export HivePress Settings
```bash
php wp-cli.phar option list --search="hp_*" --format=json > hivepress-settings.json
php wp-cli.phar option list --search="hivepress_*" --format=json >> hivepress-settings.json
```

---

## Import Commands (Run on Local Server)

### 1. Import Database
```bash
php wp-cli.phar db import wordpress-backup.sql
```

### 2. Import Content
```bash
php wp-cli.phar import wp-export/your-export-file.xml --authors=create
```

### 3. Import Basic Settings
```bash
# Site title
php wp-cli.phar option update blogname "Your Site Name"

# Site description
php wp-cli.phar option update blogdescription "Your Site Description"

# Admin email
php wp-cli.phar option update admin_email "your@email.com"

# Timezone
php wp-cli.phar option update timezone_string "America/New_York"

# Date format
php wp-cli.phar option update date_format "F j, Y"

# Time format
php wp-cli.phar option update time_format "g:i a"
```

### 4. Import Permalink Settings
```bash
php wp-cli.phar option update permalink_structure "/%postname%/"
php wp-cli.phar rewrite flush
```

### 5. Install and Activate Plugins
```bash
# Install plugins from WordPress.org
php wp-cli.phar plugin install hivepress --activate
php wp-cli.phar plugin install woocommerce --activate

# Or activate if already installed
php wp-cli.phar plugin activate hivepress
php wp-cli.phar plugin activate woocommerce

# Activate multiple plugins
php wp-cli.phar plugin activate hivepress hivepress-geolocation hivepress-reviews
```

### 6. Install and Activate Theme
```bash
# Install theme
php wp-cli.phar theme install listinghive

# Activate theme
php wp-cli.phar theme activate listinghive
```

### 7. Import Users
```bash
# Create user
php wp-cli.phar user create username email@example.com --role=administrator --user_pass=password
```

### 8. Search and Replace URLs
```bash
# Replace remote URL with local URL
php wp-cli.phar search-replace "https://wordpress-1283661-6072402.cloudwaysapps.com" "http://localhost/sk8ting-life" --all-tables

# Dry run first to see what would change
php wp-cli.phar search-replace "https://wordpress-1283661-6072402.cloudwaysapps.com" "http://localhost/sk8ting-life" --all-tables --dry-run
```

### 9. Import WooCommerce Settings
```bash
# Example: Set WooCommerce currency
php wp-cli.phar option update woocommerce_currency "USD"

# Set store address
php wp-cli.phar option update woocommerce_store_address "123 Main St"
php wp-cli.phar option update woocommerce_store_city "Your City"
php wp-cli.phar option update woocommerce_default_country "US:NY"
php wp-cli.phar option update woocommerce_store_postcode "12345"
```

### 10. Regenerate Thumbnails
```bash
php wp-cli.phar media regenerate --yes
```

### 11. Flush Cache
```bash
php wp-cli.phar cache flush
php wp-cli.phar rewrite flush
php wp-cli.phar transient delete --all
```

---

## Batch Export Script (Remote Server)

Create a file `export-all.sh` (Linux/Mac) or `export-all.bat` (Windows):

### Linux/Mac: export-all.sh
```bash
#!/bin/bash

# Create export directory
mkdir -p wp-migration-export
cd wp-migration-export

# Export database
php ../wp-cli.phar db export database.sql

# Export content
php ../wp-cli.phar export --dir=./content

# Export all options
php ../wp-cli.phar option list --format=json > all-options.json

# Export menus
php ../wp-cli.phar menu list --format=json > menus.json

# Export users
php ../wp-cli.phar user list --format=json > users.json

# Export plugins
php ../wp-cli.phar plugin list --format=json > plugins.json

# Export themes
php ../wp-cli.phar theme list --format=json > themes.json

# Export WooCommerce settings
php ../wp-cli.phar option list --search="woocommerce_*" --format=json > woocommerce-settings.json

# Export HivePress settings
php ../wp-cli.phar option list --search="hp_*" --format=json > hivepress-settings.json
php ../wp-cli.phar option list --search="hivepress_*" --format=json >> hivepress-settings.json

echo "Export complete! Files are in wp-migration-export directory"
```

### Windows: export-all.bat
```batch
@echo off

REM Create export directory
mkdir wp-migration-export
cd wp-migration-export

REM Export database
php ..\wp-cli.phar db export database.sql

REM Export content
php ..\wp-cli.phar export --dir=./content

REM Export all options
php ..\wp-cli.phar option list --format=json > all-options.json

REM Export menus
php ..\wp-cli.phar menu list --format=json > menus.json

REM Export users
php ..\wp-cli.phar user list --format=json > users.json

REM Export plugins
php ..\wp-cli.phar plugin list --format=json > plugins.json

REM Export themes
php ..\wp-cli.phar theme list --format=json > themes.json

REM Export WooCommerce settings
php ..\wp-cli.phar option list --search="woocommerce_*" --format=json > woocommerce-settings.json

REM Export HivePress settings
php ..\wp-cli.phar option list --search="hp_*" --format=json > hivepress-settings.json
php ..\wp-cli.phar option list --search="hivepress_*" --format=json >> hivepress-settings.json

echo Export complete! Files are in wp-migration-export directory
pause
```

---

## Batch Import Script (Local Server)

Create a file `import-all.sh` (Linux/Mac) or `import-all.bat` (Windows):

### Linux/Mac: import-all.sh
```bash
#!/bin/bash

# Navigate to export directory
cd wp-migration-export

# Import database
echo "Importing database..."
php ../wp-cli.phar db import database.sql

# Search and replace URLs
echo "Replacing URLs..."
php ../wp-cli.phar search-replace "https://wordpress-1283661-6072402.cloudwaysapps.com" "http://localhost/sk8ting-life" --all-tables

# Import content
echo "Importing content..."
php ../wp-cli.phar import content/*.xml --authors=create

# Regenerate thumbnails
echo "Regenerating thumbnails..."
php ../wp-cli.phar media regenerate --yes

# Flush cache
echo "Flushing cache..."
php ../wp-cli.phar cache flush
php ../wp-cli.phar rewrite flush

echo "Import complete!"
```

### Windows: import-all.bat
```batch
@echo off

REM Navigate to export directory
cd wp-migration-export

REM Import database
echo Importing database...
php ..\wp-cli.phar db import database.sql

REM Search and replace URLs
echo Replacing URLs...
php ..\wp-cli.phar search-replace "https://wordpress-1283661-6072402.cloudwaysapps.com" "http://localhost/sk8ting-life" --all-tables

REM Import content
echo Importing content...
php ..\wp-cli.phar import content\*.xml --authors=create

REM Regenerate thumbnails
echo Regenerating thumbnails...
php ..\wp-cli.phar media regenerate --yes

REM Flush cache
echo Flushing cache...
php ..\wp-cli.phar cache flush
php ..\wp-cli.phar rewrite flush

echo Import complete!
pause
```

---

## Useful Diagnostic Commands

### Check WordPress Installation
```bash
php wp-cli.phar core version
php wp-cli.phar core verify-checksums
```

### Check Database
```bash
php wp-cli.phar db check
php wp-cli.phar db optimize
```

### Check Site Health
```bash
php wp-cli.phar site health
```

### List All Options (with search)
```bash
php wp-cli.phar option list --search="*theme*"
```

### Check Rewrite Rules
```bash
php wp-cli.phar rewrite list
```

### Update WordPress Core
```bash
php wp-cli.phar core update
```

### Update All Plugins
```bash
php wp-cli.phar plugin update --all
```

---

## Notes

1. Always backup your database before importing
2. Test search-replace with `--dry-run` flag first
3. Some settings may need manual configuration after import
4. API keys and credentials should be updated for local environment
5. File permissions may need to be adjusted after import
6. WP-CLI requires PHP CLI to be installed and accessible
