<?php

/**
 * Plugin Name: Event Certificate Manager
 * Plugin URI: https://example.com
 * Description: Professional Event & Certificate Management System.
 * Version: 1.0.0
 * Author: Dinesh Adhikari
 * Author URI: https://dineshadk.com
 * Text Domain: event-certificate-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Plugin constants.
 */
define('ECM_VERSION', '1.0.0');
define('ECM_PLUGIN_FILE', __FILE__);
define('ECM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ECM_PLUGIN_URL', plugin_dir_url(__FILE__));

/*
 * Composer dependencies.
 *
 * Loads tc-lib-pdf and its related packages.
 */
$ecm_composer_autoload = ECM_PLUGIN_PATH . 'vendor/autoload.php';

if (file_exists($ecm_composer_autoload)) {
    require_once $ecm_composer_autoload;
}

/*
 * PDF engine bootstrap.
 */
require_once ECM_PLUGIN_PATH
    . 'includes/modules/certificates/engine/class-pdf-bootstrap.php';

/*

/*
 * Core classes.
 */
require_once ECM_PLUGIN_PATH . 'includes/class-database.php';
require_once ECM_PLUGIN_PATH . 'includes/class-activator.php';
require_once ECM_PLUGIN_PATH . 'includes/class-deactivator.php';
require_once ECM_PLUGIN_PATH . 'includes/class-loader.php';



/*
 * Font management.
 */
require_once ECM_PLUGIN_PATH . 'includes/modules/fonts/class-font-manager.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/fonts/class-google-fonts.php';

require_once ECM_PLUGIN_PATH . 'includes/modules/fonts/class-google-fonts.php';

/*
 * Plugin lifecycle hooks.
 */
register_activation_hook(
    __FILE__,
    ['ECM_Activator', 'activate']
);

register_deactivation_hook(
    __FILE__,
    ['ECM_Deactivator', 'deactivate']
);

/**
 * Run the plugin.
 *
 * @return void
 */
function ecm_run_plugin()
{
    /*
     * Ensure the font directories and manifest are available.
     */
    if (class_exists('ECM_Font_Manager')) {
        ECM_Font_Manager::initialize();
    }

    $plugin = new ECM_Loader();
    $plugin->run();
}

ecm_run_plugin();
