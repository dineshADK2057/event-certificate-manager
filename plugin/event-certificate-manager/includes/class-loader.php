<?php

/**
 * ECM Plugin Loader
 *
 * Creates and connects the primary ECM admin controllers.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once ECM_PLUGIN_PATH
    . 'includes/class-events.php';

require_once ECM_PLUGIN_PATH
    . 'includes/class-admin.php';

class ECM_Loader
{
    /**
     * Initialize the Event Certificate Manager plugin.
     *
     * A single ECM_Events instance is shared with ECM_Admin so that
     * event hooks are registered exactly once.
     *
     * @return void
     */
    public function run()
    {
        if (!is_admin()) {
            return;
        }

        /*
         * ECM_Events registers all event-level form, AJAX, template,
         * participant, session, font, and certificate handlers.
         */
        $events = new ECM_Events();

        /*
         * ECM_Admin uses the same Event controller for the Events
         * menu callback instead of creating another instance.
         */
        $admin = new ECM_Admin($events);
        $admin->init();
    }
}