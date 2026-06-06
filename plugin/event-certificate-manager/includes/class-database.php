<?php

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Database {

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $events_table = $wpdb->prefix . 'ecm_events';

        $sql = "CREATE TABLE $events_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_code VARCHAR(50) NOT NULL,
            event_name VARCHAR(255) NOT NULL,
            event_type VARCHAR(100) DEFAULT NULL,
            venue VARCHAR(255) DEFAULT NULL,
            start_date DATE DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY event_code (event_code)
        ) $charset_collate;";

        dbDelta($sql);
    }
}