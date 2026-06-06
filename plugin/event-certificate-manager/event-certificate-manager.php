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

define('ECM_VERSION', '1.0.0');
define('ECM_PLUGIN_FILE', __FILE__);
define('ECM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ECM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once ECM_PLUGIN_PATH . 'includes/class-database.php';
require_once ECM_PLUGIN_PATH . 'includes/class-activator.php';
require_once ECM_PLUGIN_PATH . 'includes/class-deactivator.php';
require_once ECM_PLUGIN_PATH . 'includes/class-loader.php';

register_activation_hook(__FILE__, ['ECM_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['ECM_Deactivator', 'deactivate']);

function ecm_run_plugin() {
    $plugin = new ECM_Loader();
    $plugin->run();
}

ecm_run_plugin();