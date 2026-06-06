<?php

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Deactivator {

    public static function deactivate() {

        flush_rewrite_rules();

    }

}