<?php

/**
 * ECM Admin Controller
 *
 * Registers the WordPress admin menu and loads ECM admin assets.
 *
 * Page rendering is delegated to dedicated module traits.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Global admin page modules.
 */
require_once ECM_PLUGIN_PATH
    . 'includes/modules/dashboard/trait-dashboard-page.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/participants/trait-global-participants.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/templates/trait-global-templates.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/certificates/trait-global-certificates.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/logs/trait-global-logs.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/settings/trait-global-settings.php';

class ECM_Admin
{
    use ECM_Dashboard_Page;
    use ECM_Global_Participants;
    use ECM_Global_Templates;
    use ECM_Global_Certificates;
    use ECM_Global_Logs;
    use ECM_Global_Settings;

    /**
     * Shared Event Workspace controller.
     *
     * The same instance is used for menu callbacks and action hooks,
     * preventing duplicate hook registration.
     *
     * @var ECM_Events
     */
    private $events;

    /**
     * Create the ECM Admin controller.
     *
     * @param ECM_Events $events Event Workspace controller.
     */
    public function __construct($events)
    {
        $this->events = $events;
    }

    /**
     * Register WordPress admin hooks.
     *
     * @return void
     */
    public function init()
    {
        add_action(
            'admin_menu',
            [$this, 'register_menu']
        );

        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueue_assets']
        );
    }

    /**
     * Register the ECM top-level menu and submenu pages.
     *
     * Global submenu pages provide cross-event views.
     * Event-specific work remains inside the Events workspace.
     *
     * @return void
     */
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

        add_submenu_page(
            'ecm-dashboard',
            'Events',
            'Events',
            'manage_options',
            'ecm-events',
            [$this->events, 'events_page']
        );

        add_submenu_page(
            'ecm-dashboard',
            'Participants',
            'Participants',
            'manage_options',
            'ecm-participants',
            [$this, 'global_participants_page']
        );

        add_submenu_page(
            'ecm-dashboard',
            'Templates',
            'Templates',
            'manage_options',
            'ecm-templates',
            [$this, 'global_templates_page']
        );

        add_submenu_page(
            'ecm-dashboard',
            'Certificates',
            'Certificates',
            'manage_options',
            'ecm-certificates',
            [$this, 'global_certificates_page']
        );

        add_submenu_page(
            'ecm-dashboard',
            'Logs',
            'Logs',
            'manage_options',
            'ecm-logs',
            [$this, 'global_logs_page']
        );

        add_submenu_page(
            'ecm-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'ecm-settings',
            [$this, 'global_settings_page']
        );
    }

    /**
     * Load ECM admin styles and scripts.
     *
     * Builder-specific modules are loaded only when the Template
     * Builder screen is active.
     *
     * @param string $hook Current WordPress admin screen hook.
     *
     * @return void
     */
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
         * Shared ECM admin functionality:
         * modals, participants, sessions, templates, and settings.
         */
        wp_enqueue_script(
            'ecm-admin',
            ECM_PLUGIN_URL . 'admin/js/ecm-admin.js',
            ['jquery'],
            ECM_VERSION,
            true
        );

        $action = isset($_GET['action'])
            ? sanitize_key(wp_unslash($_GET['action']))
            : '';

        if ($action !== 'template_builder') {
            return;
        }

        $this->enqueue_builder_assets();
    }

    /**
     * Load modular Template Builder JavaScript.
     *
     * @return void
     */
    private function enqueue_builder_assets()
    {
        wp_enqueue_script(
            'ecm-builder-core',
            ECM_PLUGIN_URL
                . 'admin/js/builder/builder-core.js',
            ['jquery'],
            ECM_VERSION,
            true
        );

        wp_enqueue_script(
            'ecm-builder-selection',
            ECM_PLUGIN_URL
                . 'admin/js/builder/builder-selection.js',
            [
                'jquery',
                'ecm-builder-core',
            ],
            ECM_VERSION,
            true
        );

        wp_enqueue_script(
            'ecm-builder-properties',
            ECM_PLUGIN_URL
                . 'admin/js/builder/builder-properties.js',
            [
                'jquery',
                'ecm-builder-core',
                'ecm-builder-selection',
            ],
            ECM_VERSION,
            true
        );

        wp_enqueue_script(
            'ecm-builder-autosave',
            ECM_PLUGIN_URL
                . 'admin/js/builder/builder-autosave.js',
            [
                'jquery',
                'ecm-builder-core',
                'ecm-builder-properties',
            ],
            ECM_VERSION,
            true
        );

        wp_enqueue_script(
            'ecm-builder-interaction',
            ECM_PLUGIN_URL
                . 'admin/js/builder/builder-interaction.js',
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
            'ecm-builder-zoom',
            ECM_PLUGIN_URL
                . 'admin/js/builder/builder-zoom.js',
            [
                'jquery',
                'ecm-builder-core',
            ],
            ECM_VERSION,
            true
        );

        wp_enqueue_script(
            'ecm-builder-font-picker',
            ECM_PLUGIN_URL
                . 'admin/js/builder/builder-font-picker.js',
            [
                'jquery',
                'ecm-builder-core',
                'ecm-builder-selection',
                'ecm-builder-properties',
            ],
            ECM_VERSION,
            true
        );
    }
}