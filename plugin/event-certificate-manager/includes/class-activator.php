
<?php

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Activator {

    public static function activate() {
        ECM_Database::create_tables();

        flush_rewrite_rules();
    }
}