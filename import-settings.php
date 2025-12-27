<?php
/**
 * WordPress Settings Importer
 *
 * Place the exported JSON file in the same directory as this script
 * Access via browser: http://localhost/sk8ting-life/import-settings.php
 *
 * This will import settings from the JSON file created by export-settings.php
 */

// Load WordPress
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be logged in as an administrator');
}

// Get JSON file from upload or specify filename
$json_file = '';

// Check if file was uploaded
if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
    $json_file = $_FILES['json_file']['tmp_name'];
} elseif (isset($_GET['file']) && file_exists($_GET['file'])) {
    $json_file = $_GET['file'];
} else {
    // Show upload form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>WordPress Settings Importer</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            h1 { color: #333; }
            .form-group { margin: 20px 0; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="file"] { padding: 10px; border: 1px solid #ddd; }
            input[type="submit"] { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; }
            input[type="submit"]:hover { background: #005177; }
            .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .checkbox-group { margin: 15px 0; }
            .checkbox-group label { display: inline; font-weight: normal; margin-left: 5px; }
        </style>
    </head>
    <body>
        <h1>WordPress Settings Importer</h1>

        <div class="warning">
            <strong>Warning:</strong> This will overwrite existing settings. Make sure you have a backup of your database before proceeding.
        </div>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Upload Settings JSON File:</label>
                <input type="file" name="json_file" accept=".json" required>
            </div>

            <h3>Select what to import:</h3>

            <div class="checkbox-group">
                <input type="checkbox" name="import_general" id="import_general" value="1" checked>
                <label for="import_general">General Settings</label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="import_reading" id="import_reading" value="1" checked>
                <label for="import_reading">Reading Settings</label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="import_discussion" id="import_discussion" value="1" checked>
                <label for="import_discussion">Discussion Settings</label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="import_media" id="import_media" value="1" checked>
                <label for="import_media">Media Settings</label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="import_permalinks" id="import_permalinks" value="1" checked>
                <label for="import_permalinks">Permalink Settings</label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="import_theme" id="import_theme" value="1" checked>
                <label for="import_theme">Theme Settings</label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="import_widgets" id="import_widgets" value="1" checked>
                <label for="import_widgets">Widgets</label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="import_menus" id="import_menus" value="1" checked>
                <label for="import_menus">Menus</label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="import_hivepress" id="import_hivepress" value="1" checked>
                <label for="import_hivepress">HivePress Settings</label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="import_woocommerce" id="import_woocommerce" value="1" checked>
                <label for="import_woocommerce">WooCommerce Settings</label>
            </div>

            <div class="form-group">
                <input type="submit" value="Import Settings">
            </div>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Read JSON file
$json_data = file_get_contents($json_file);
$import_data = json_decode($json_data, true);

if (!$import_data) {
    die('Error: Invalid JSON file');
}

// Import results
$results = array(
    'success' => array(),
    'errors' => array(),
);

// Helper function to import options safely
function import_option($option_name, $option_value, &$results) {
    // Skip URL options to prevent breaking local site
    $skip_url_options = array('siteurl', 'home');
    if (in_array($option_name, $skip_url_options)) {
        $results['success'][] = "Skipped {$option_name} (URL option)";
        return;
    }

    $updated = update_option($option_name, $option_value);
    if ($updated) {
        $results['success'][] = "Updated {$option_name}";
    } else {
        $results['errors'][] = "Failed to update {$option_name}";
    }
}

// Import General Settings
if (isset($_POST['import_general']) && isset($import_data['general'])) {
    foreach ($import_data['general'] as $key => $value) {
        import_option($key, $value, $results);
    }
}

// Import Reading Settings
if (isset($_POST['import_reading']) && isset($import_data['reading'])) {
    foreach ($import_data['reading'] as $key => $value) {
        import_option($key, $value, $results);
    }
}

// Import Discussion Settings
if (isset($_POST['import_discussion']) && isset($import_data['discussion'])) {
    foreach ($import_data['discussion'] as $key => $value) {
        import_option($key, $value, $results);
    }
}

// Import Media Settings
if (isset($_POST['import_media']) && isset($import_data['media'])) {
    foreach ($import_data['media'] as $key => $value) {
        import_option($key, $value, $results);
    }
}

// Import Permalink Settings
if (isset($_POST['import_permalinks']) && isset($import_data['permalinks'])) {
    foreach ($import_data['permalinks'] as $key => $value) {
        import_option($key, $value, $results);
    }
    // Flush rewrite rules after updating permalinks
    flush_rewrite_rules();
    $results['success'][] = 'Flushed rewrite rules';
}

// Import Theme Settings
if (isset($_POST['import_theme']) && isset($import_data['theme'])) {
    if (isset($import_data['theme']['theme_mods'])) {
        $current_theme = get_option('stylesheet');
        update_option('theme_mods_' . $current_theme, $import_data['theme']['theme_mods']);
        $results['success'][] = 'Updated theme mods';
    }
}

// Import Widgets
if (isset($_POST['import_widgets']) && isset($import_data['widgets'])) {
    if (isset($import_data['widgets']['sidebars_widgets'])) {
        update_option('sidebars_widgets', $import_data['widgets']['sidebars_widgets']);
        $results['success'][] = 'Updated sidebars_widgets';
    }

    foreach ($import_data['widgets'] as $widget_option => $widget_value) {
        if ($widget_option !== 'sidebars_widgets') {
            update_option($widget_option, $widget_value);
            $results['success'][] = "Updated widget: {$widget_option}";
        }
    }
}

// Import Menus
if (isset($_POST['import_menus']) && isset($import_data['menus'])) {
    // Note: This is a simplified import. Full menu import requires creating menu items
    // which is complex. Consider using a plugin for full menu migration.
    $results['success'][] = 'Menu data available in JSON (manual recreation recommended)';

    if (isset($import_data['menu_locations'])) {
        set_theme_mod('nav_menu_locations', $import_data['menu_locations']);
        $results['success'][] = 'Updated menu locations';
    }
}

// Import HivePress Settings
if (isset($_POST['import_hivepress']) && isset($import_data['hivepress']) && class_exists('HivePress\Core')) {
    foreach ($import_data['hivepress'] as $key => $value) {
        import_option($key, $value, $results);
    }
}

// Import WooCommerce Settings
if (isset($_POST['import_woocommerce']) && isset($import_data['woocommerce']) && class_exists('WooCommerce')) {
    foreach ($import_data['woocommerce'] as $key => $value) {
        import_option($key, $value, $results);
    }

    // Flush WooCommerce rewrite rules
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients();
    }
    if (function_exists('wc_delete_shop_order_transients')) {
        wc_delete_shop_order_transients();
    }
    $results['success'][] = 'Flushed WooCommerce cache';
}

// Display results
?>
<!DOCTYPE html>
<html>
<head>
    <title>Import Results</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #333; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 4px; }
        ul { list-style: none; padding: 0; }
        li { padding: 5px 0; }
        .back-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; }
        .back-link:hover { background: #005177; }
    </style>
</head>
<body>
    <h1>Import Results</h1>

    <?php if (!empty($results['success'])): ?>
    <div class="success">
        <h3>Successfully Imported:</h3>
        <ul>
            <?php foreach ($results['success'] as $msg): ?>
            <li>✓ <?php echo esc_html($msg); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['errors'])): ?>
    <div class="error">
        <h3>Errors:</h3>
        <ul>
            <?php foreach ($results['errors'] as $msg): ?>
            <li>✗ <?php echo esc_html($msg); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <a href="<?php echo admin_url(); ?>" class="back-link">Go to WordPress Admin</a>
</body>
</html>
