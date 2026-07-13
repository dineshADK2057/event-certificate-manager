<?php

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Admin
{

    public function init()
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menu()
    {

        add_menu_page(
            'Event Certificate Manager',
            'ECM',
            'manage_options',
            'ecm-dashboard',
            [$this, 'dashboard_page'],
            'dashicons-awards',
            25
        );

        add_submenu_page(
            'ecm-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ecm-dashboard',
            [$this, 'dashboard_page']
        );

        $events = new ECM_Events();

        add_submenu_page(
            'ecm-dashboard',
            'Events',
            'Events',
            'manage_options',
            'ecm-events',
            [$events, 'events_page']
        );

        add_submenu_page(
            'ecm-dashboard',
            'Participants',
            'Participants',
            'manage_options',
            'ecm-participants',
            [$this, 'placeholder_page']
        );

        add_submenu_page(
            'ecm-dashboard',
            'Templates',
            'Templates',
            'manage_options',
            'ecm-templates',
            [$this, 'placeholder_page']
        );

        add_submenu_page(
            'ecm-dashboard',
            'Certificates',
            'Certificates',
            'manage_options',
            'ecm-certificates',
            [$this, 'placeholder_page']
        );

        add_submenu_page(
            'ecm-dashboard',
            'Logs',
            'Logs',
            'manage_options',
            'ecm-logs',
            [$this, 'placeholder_page']
        );

        add_submenu_page(
            'ecm-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'ecm-settings',
            [$this, 'placeholder_page']
        );
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'ecm') === false) {
            return;
        }

        wp_enqueue_style(
            'ecm-admin',
            ECM_PLUGIN_URL . 'admin/css/ecm-admin.css',
            [],
            ECM_VERSION
        );

        /*
     * General ECM admin functionality:
     * modals, participants, sessions, templates list, etc.
     */
        wp_enqueue_script(
            'ecm-admin',
            ECM_PLUGIN_URL . 'admin/js/ecm-admin.js',
            ['jquery'],
            ECM_VERSION,
            true
        );

        /*
     * Load Builder modules only on the Template Builder screen.
     */
        $action = isset($_GET['action'])
            ? sanitize_key(wp_unslash($_GET['action']))
            : '';

        if ($action !== 'template_builder') {
            return;
        }

        wp_enqueue_script(
            'ecm-builder-core',
            ECM_PLUGIN_URL . 'admin/js/builder/builder-core.js',
            ['jquery'],
            ECM_VERSION,
            true
        );

        wp_enqueue_script(
            'ecm-builder-selection',
            ECM_PLUGIN_URL . 'admin/js/builder/builder-selection.js',
            ['jquery', 'ecm-builder-core'],
            ECM_VERSION,
            true
        );

        wp_enqueue_script(
            'ecm-builder-properties',
            ECM_PLUGIN_URL . 'admin/js/builder/builder-properties.js',
            ['jquery', 'ecm-builder-core', 'ecm-builder-selection'],
            ECM_VERSION,
            true
        );

        wp_enqueue_script(
            'ecm-builder-autosave',
            ECM_PLUGIN_URL . 'admin/js/builder/builder-autosave.js',
            ['jquery', 'ecm-builder-core', 'ecm-builder-properties'],
            ECM_VERSION,
            true
        );

        wp_enqueue_script(
            'ecm-builder-interaction',
            ECM_PLUGIN_URL . 'admin/js/builder/builder-interaction.js',
            [
                'jquery',
                'ecm-builder-core',
                'ecm-builder-selection',
                'ecm-builder-properties',
                'ecm-builder-autosave',
            ],
            ECM_VERSION,
            true
        );



        wp_enqueue_script(
            'ecm-builder-toolbar',
            ECM_PLUGIN_URL . 'admin/js/builder/builder-toolbar.js',
            [
                'jquery',
                'ecm-builder-core',
                'ecm-builder-selection',
                'ecm-builder-interaction',
            ],
            ECM_VERSION,
            true
        );
        
        wp_enqueue_script(
            'ecm-builder-zoom',
            ECM_PLUGIN_URL . 'admin/js/builder/builder-zoom.js',
            [
                'jquery',
                'ecm-builder-core',
                'ecm-builder-toolbar',
            ],
            ECM_VERSION,
            true
        );
    }

    public function dashboard_page()
    {
        global $wpdb;

        $events_table       = $wpdb->prefix . 'ecm_events';
        $participants_table = $wpdb->prefix . 'ecm_participants';
        $templates_table    = $wpdb->prefix . 'ecm_templates';
        $certificates_table = $wpdb->prefix . 'ecm_certificates';

        $events_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $events_table");
        $participants_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $participants_table");
        $templates_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $templates_table");
        $certificates_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $certificates_table");

?>
        <div class="wrap ecm-wrap">
            <h1>Event Certificate Manager</h1>
            <p class="ecm-subtitle">Manage events, participants, templates, certificates, QR verification, and email automation.</p>

            <div class="ecm-dashboard-grid">
                <div class="ecm-card">
                    <span class="ecm-card-label">Events</span>
                    <strong><?php echo esc_html($events_count); ?></strong>
                </div>

                <div class="ecm-card">
                    <span class="ecm-card-label">Participants</span>
                    <strong><?php echo esc_html($participants_count); ?></strong>
                </div>

                <div class="ecm-card">
                    <span class="ecm-card-label">Templates</span>
                    <strong><?php echo esc_html($templates_count); ?></strong>
                </div>

                <div class="ecm-card">
                    <span class="ecm-card-label">Certificates</span>
                    <strong><?php echo esc_html($certificates_count); ?></strong>
                </div>
            </div>

            <div class="ecm-panel">
                <h2>Getting Started</h2>
                <ol>
                    <li>Create an event.</li>
                    <li>Define participant fields for that event.</li>
                    <li>Add participants manually or upload a CSV file.</li>
                    <li>Upload a certificate template and place dynamic elements.</li>
                    <li>Publish a certificate request form.</li>
                </ol>
            </div>
        </div>
    <?php
    }

    public function placeholder_page()
    {
    ?>
        <div class="wrap ecm-wrap">
            <h1>Coming Soon</h1>
            <p>This ECM module will be built in the next sprint.</p>
        </div>
<?php
    }
}
