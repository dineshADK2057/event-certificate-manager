<?php

/**
 * ECM Database Manager
 *
 * Creates and updates all Event Certificate Manager database tables
 * and prepares the required upload directories.
 *
 * Fonts are managed as filesystem assets instead of database records.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Database
{
    /**
     * Create or update all plugin database tables.
     *
     * This method is safe to run multiple times because WordPress
     * dbDelta() creates missing tables and updates compatible schemas.
     *
     * @return void
     */
    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        /*
         * Database table names.
         */
        $events               = $wpdb->prefix . 'ecm_events';
        $fields               = $wpdb->prefix . 'ecm_event_fields';
        $participants         = $wpdb->prefix . 'ecm_participants';
        $participant_meta     = $wpdb->prefix . 'ecm_participant_meta';
        $sessions             = $wpdb->prefix . 'ecm_sessions';
        $session_participants = $wpdb->prefix . 'ecm_session_participants';
        $templates            = $wpdb->prefix . 'ecm_templates';
        $template_elements    = $wpdb->prefix . 'ecm_template_elements';
        $certificates         = $wpdb->prefix . 'ecm_certificates';
        $logs                 = $wpdb->prefix . 'ecm_logs';
        $settings             = $wpdb->prefix . 'ecm_settings';

        $sql = [];

        /*
         * Events
         *
         * Stores the main event information.
         */
        $sql[] = "CREATE TABLE {$events} (
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
            UNIQUE KEY event_code (event_code),
            KEY status (status)
        ) {$charset_collate};";

        /*
         * Event participant fields
         *
         * Stores default and custom participant field definitions
         * for each event.
         */
        $sql[] = "CREATE TABLE {$fields} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            field_key VARCHAR(100) NOT NULL,
            field_label VARCHAR(150) NOT NULL,
            field_type VARCHAR(50) NOT NULL DEFAULT 'text',
            is_required TINYINT(1) NOT NULL DEFAULT 0,
            field_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY event_field_key (event_id, field_key),
            KEY event_id (event_id),
            KEY field_key (field_key),
            KEY field_order (field_order)
        ) {$charset_collate};";

        /*
         * Event participants
         *
         * Stores the primary participant record for each event.
         * Additional dynamic values are stored in participant meta.
         */
        $sql[] = "CREATE TABLE {$participants} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            member_id VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY event_member (event_id, member_id),
            KEY event_id (event_id),
            KEY member_id (member_id)
        ) {$charset_collate};";

        /*
         * Participant metadata
         *
         * Stores dynamic values such as name, club, email,
         * phone number, and event-specific custom fields.
         */
        $sql[] = "CREATE TABLE {$participant_meta} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            participant_id BIGINT(20) UNSIGNED NOT NULL,
            meta_key VARCHAR(100) NOT NULL,
            meta_value LONGTEXT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY participant_meta_key (participant_id, meta_key),
            KEY participant_id (participant_id),
            KEY meta_key (meta_key)
        ) {$charset_collate};";

        /*
         * Event sessions
         *
         * Stores event sessions, workshops, classes, or training units.
         */
        $sql[] = "CREATE TABLE {$sessions} (
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
            KEY event_id (event_id),
            KEY status (status)
        ) {$charset_collate};";

        /*
         * Session participants
         *
         * Links event participants to one or more sessions.
         */
        $sql[] = "CREATE TABLE {$session_participants} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id BIGINT(20) UNSIGNED NOT NULL,
            participant_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_participant (session_id, participant_id),
            KEY session_id (session_id),
            KEY participant_id (participant_id)
        ) {$charset_collate};";

        /*
         * Certificate templates
         *
         * Stores event-wide or session-specific template settings.
         * The background_file value contains a relative upload path.
         */
        $sql[] = "CREATE TABLE {$templates} (
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
        ) {$charset_collate};";

        /*
         * Template elements
         *
         * Stores placeholders and their visual properties on a template.
         * Font files themselves are managed through the filesystem.
         */
        $sql[] = "CREATE TABLE {$template_elements} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            template_id BIGINT(20) UNSIGNED NOT NULL,
            placeholder_key VARCHAR(100) NOT NULL,
            source_type VARCHAR(50) NOT NULL DEFAULT 'participant',
            x_position FLOAT NOT NULL DEFAULT 0,
            y_position FLOAT NOT NULL DEFAULT 0,
            width FLOAT DEFAULT NULL,
            height FLOAT DEFAULT NULL,
            font_family VARCHAR(150) NOT NULL DEFAULT 'Arial',
            font_size FLOAT NOT NULL DEFAULT 18,
            font_style VARCHAR(30) NOT NULL DEFAULT '',
            font_color VARCHAR(20) NOT NULL DEFAULT '#000000',
            alignment VARCHAR(20) NOT NULL DEFAULT 'left',
            rotation FLOAT NOT NULL DEFAULT 0,
            element_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_id (template_id),
            KEY placeholder_key (placeholder_key),
            KEY element_order (element_order)
        ) {$charset_collate};";

        /*
         * Generated certificates
         *
         * Stores generated certificate records and delivery status.
         */
        $sql[] = "CREATE TABLE {$certificates} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            certificate_id VARCHAR(100) NOT NULL,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            session_id BIGINT(20) UNSIGNED DEFAULT NULL,
            participant_id BIGINT(20) UNSIGNED NOT NULL,
            template_id BIGINT(20) UNSIGNED NOT NULL,
            recipient_name VARCHAR(255) NOT NULL,
            recipient_email VARCHAR(255) DEFAULT NULL,
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
            KEY template_id (template_id),
            KEY recipient_email (recipient_email),
            KEY status (status)
        ) {$charset_collate};";

        /*
         * Activity logs
         *
         * Stores generation, delivery, verification, and error logs.
         */
        $sql[] = "CREATE TABLE {$logs} (
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
            KEY log_type (log_type),
            KEY created_at (created_at)
        ) {$charset_collate};";

        /*
         * ECM settings
         *
         * Stores plugin-wide configuration values.
         */
        $sql[] = "CREATE TABLE {$settings} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(150) NOT NULL,
            setting_value LONGTEXT DEFAULT NULL,
            autoload VARCHAR(20) NOT NULL DEFAULT 'yes',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key),
            KEY autoload (autoload)
        ) {$charset_collate};";

        /*
         * Create or update every table.
         */
        foreach ($sql as $query) {
            dbDelta($query);
        }

        /*
         * Prepare the filesystem directories used by ECM.
         */
        self::create_upload_directories();

        /*
         * Save the installed database version.
         */
        update_option('ecm_db_version', ECM_VERSION);
    }

    /**
     * Create ECM upload directories.
     *
     * Fonts are stored as filesystem assets instead of database rows.
     * A future Font Manager will maintain the fonts manifest file.
     *
     * @return void
     */
    private static function create_upload_directories()
    {
        $upload_dir = wp_upload_dir();

        if (!empty($upload_dir['error'])) {
            return;
        }

        $base_dir = trailingslashit($upload_dir['basedir']) . 'ecm/';

        $directories = [
            $base_dir,
            $base_dir . 'templates/',
            $base_dir . 'templates/previews/',
            $base_dir . 'generated/',
            $base_dir . 'qr/',
            $base_dir . 'fonts/',
            $base_dir . 'exports/',
            $base_dir . 'temp/',
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                wp_mkdir_p($directory);
            }
        }

        self::create_fonts_manifest($base_dir . 'fonts/');
    }

    /**
     * Create the initial font manifest.
     *
     * The manifest contains built-in browser-safe fonts. Google Fonts
     * and website/custom fonts will be added later by the Font Manager.
     *
     * @param string $fonts_directory Absolute fonts directory path.
     *
     * @return void
     */
    private static function create_fonts_manifest($fonts_directory)
    {
        $manifest_path = trailingslashit($fonts_directory) . 'fonts.json';

        /*
         * Never overwrite an existing manifest because it may already
         * contain downloaded Google Fonts or website custom fonts.
         */
        if (file_exists($manifest_path)) {
            return;
        }

        $fonts = [
            [
                'family'   => 'Arial',
                'slug'     => 'arial',
                'source'   => 'builtin',
                'category' => 'sans-serif',
                'files'    => new stdClass(),
            ],
            [
                'family'   => 'Helvetica',
                'slug'     => 'helvetica',
                'source'   => 'builtin',
                'category' => 'sans-serif',
                'files'    => new stdClass(),
            ],
            [
                'family'   => 'Verdana',
                'slug'     => 'verdana',
                'source'   => 'builtin',
                'category' => 'sans-serif',
                'files'    => new stdClass(),
            ],
            [
                'family'   => 'Tahoma',
                'slug'     => 'tahoma',
                'source'   => 'builtin',
                'category' => 'sans-serif',
                'files'    => new stdClass(),
            ],
            [
                'family'   => 'Trebuchet MS',
                'slug'     => 'trebuchet-ms',
                'source'   => 'builtin',
                'category' => 'sans-serif',
                'files'    => new stdClass(),
            ],
            [
                'family'   => 'Times New Roman',
                'slug'     => 'times-new-roman',
                'source'   => 'builtin',
                'category' => 'serif',
                'files'    => new stdClass(),
            ],
            [
                'family'   => 'Georgia',
                'slug'     => 'georgia',
                'source'   => 'builtin',
                'category' => 'serif',
                'files'    => new stdClass(),
            ],
            [
                'family'   => 'Garamond',
                'slug'     => 'garamond',
                'source'   => 'builtin',
                'category' => 'serif',
                'files'    => new stdClass(),
            ],
            [
                'family'   => 'Courier New',
                'slug'     => 'courier-new',
                'source'   => 'builtin',
                'category' => 'monospace',
                'files'    => new stdClass(),
            ],
        ];

        $manifest = [
            'version'    => 1,
            'updated_at' => current_time('mysql'),
            'fonts'      => $fonts,
        ];

        $encoded_manifest = wp_json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        if (!$encoded_manifest) {
            return;
        }

        file_put_contents(
            $manifest_path,
            $encoded_manifest,
            LOCK_EX
        );
    }
}