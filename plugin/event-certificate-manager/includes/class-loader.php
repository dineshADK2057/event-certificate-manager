
<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once ECM_PLUGIN_PATH . 'includes/class-admin.php';
require_once ECM_PLUGIN_PATH . 'includes/class-events.php';

class ECM_Loader
{

    public function run()
    {

        if (is_admin()) {
            $admin = new ECM_Admin();
            $admin->init();

            $events = new ECM_Events();
        }
    }
}
