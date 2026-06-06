<?php

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Database {

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $events = $wpdb->prefix . 'ecm_events';
        $fields = $wpdb->prefix . 'ecm_event_fields';
        $participants = $wpdb->prefix . 'ecm_participants';
        $participant_meta = $wpdb->prefix . 'ecm_participant_meta';
        $sessions = $wpdb->prefix . 'ecm_sessions';
        $session_participants = $wpdb->prefix . 'ecm_session_participants';
        $templates = $wpdb->prefix . 'ecm_templates';
        $template_elements = $wpdb->prefix . 'ecm_template_elements';
        $certificates = $wpdb->prefix . 'ecm_certificates';
        $logs = $wpdb->prefix . 'ecm_logs';
        $settings = $wpdb->prefix . 'ecm_settings';

        $sql = [];

        $sql[] = "CREATE TABLE $events (
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

        $sql[] = "CREATE TABLE $fields (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            field_key VARCHAR(100) NOT NULL,
            field_label VARCHAR(150) NOT NULL,
            field_type VARCHAR(50) NOT NULL DEFAULT 'text',
            is_required TINYINT(1) NOT NULL DEFAULT 0,
            field_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY field_key (field_key)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $participants (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            member_id VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY event_member (event_id, member_id),
            KEY event_id (event_id),
            KEY member_id (member_id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $participant_meta (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            participant_id BIGINT(20) UNSIGNED NOT NULL,
            meta_key VARCHAR(100) NOT NULL,
            meta_value LONGTEXT DEFAULT NULL,
            PRIMARY KEY (id),
            KEY participant_id (participant_id),
            KEY meta_key (meta_key)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $sessions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            session_code VARCHAR(50) NOT NULL,
            session_name VARCHAR(255) NOT NULL,
            tutor_name VARCHAR(255) DEFAULT NULL,
            session_date DATE DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY event_session_code (event_id, session_code),
            KEY event_id (event_id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $session_participants (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id BIGINT(20) UNSIGNED NOT NULL,
            participant_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_participant (session_id, participant_id),
            KEY session_id (session_id),
            KEY participant_id (participant_id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $templates (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            session_id BIGINT(20) UNSIGNED DEFAULT NULL,
            template_name VARCHAR(255) NOT NULL,
            certificate_type VARCHAR(100) NOT NULL DEFAULT 'participant',
            background_file TEXT DEFAULT NULL,
            orientation VARCHAR(20) NOT NULL DEFAULT 'landscape',
            page_size VARCHAR(20) NOT NULL DEFAULT 'A4',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY session_id (session_id),
            KEY certificate_type (certificate_type)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $template_elements (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            template_id BIGINT(20) UNSIGNED NOT NULL,
            placeholder_key VARCHAR(100) NOT NULL,
            source_type VARCHAR(50) NOT NULL DEFAULT 'participant',
            x_position FLOAT NOT NULL DEFAULT 0,
            y_position FLOAT NOT NULL DEFAULT 0,
            width FLOAT DEFAULT NULL,
            height FLOAT DEFAULT NULL,
            font_family VARCHAR(150) DEFAULT 'Arial',
            font_size INT(11) NOT NULL DEFAULT 18,
            font_style VARCHAR(30) DEFAULT '',
            font_color VARCHAR(20) DEFAULT '#000000',
            alignment VARCHAR(20) DEFAULT 'left',
            rotation FLOAT NOT NULL DEFAULT 0,
            element_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_id (template_id),
            KEY placeholder_key (placeholder_key)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $certificates (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            certificate_id VARCHAR(100) NOT NULL,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            session_id BIGINT(20) UNSIGNED DEFAULT NULL,
            participant_id BIGINT(20) UNSIGNED NOT NULL,
            template_id BIGINT(20) UNSIGNED NOT NULL,
            recipient_name VARCHAR(255) NOT NULL,
            recipient_email VARCHAR(255) NOT NULL,
            pdf_file TEXT DEFAULT NULL,
            qr_file TEXT DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'generated',
            generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            emailed_at DATETIME DEFAULT NULL,
            verification_count INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY certificate_id (certificate_id),
            KEY event_id (event_id),
            KEY session_id (session_id),
            KEY participant_id (participant_id),
            KEY recipient_email (recipient_email)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $logs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            certificate_id BIGINT(20) UNSIGNED DEFAULT NULL,
            event_id BIGINT(20) UNSIGNED DEFAULT NULL,
            log_type VARCHAR(50) NOT NULL,
            log_message TEXT DEFAULT NULL,
            log_data LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY certificate_id (certificate_id),
            KEY event_id (event_id),
            KEY log_type (log_type)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $settings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(150) NOT NULL,
            setting_value LONGTEXT DEFAULT NULL,
            autoload VARCHAR(20) NOT NULL DEFAULT 'yes',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";

        foreach ($sql as $query) {
            dbDelta($query);
        }

        update_option('ecm_db_version', ECM_VERSION);
    }
}