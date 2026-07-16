<?php

/**
 * ECM Event Workspace Controller
 *
 * Coordinates all event-scoped ECM modules.
 *
 * This class intentionally contains only:
 * - Module loading
 * - Trait composition
 * - WordPress hook registration
 *
 * Event rendering, CRUD operations, participant management,
 * sessions, templates, settings, certificates, logs, fonts,
 * and PDF-related logic are implemented inside their dedicated
 * module traits.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Events Module
|--------------------------------------------------------------------------
|
| Handles the global Events page, event CRUD, and the event workspace.
|
*/

require_once ECM_PLUGIN_PATH
    . 'includes/modules/events/trait-events-page.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/events/trait-event-crud.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/events/trait-event-workspace.php';

/*
|--------------------------------------------------------------------------
| Event Overview Module
|--------------------------------------------------------------------------
*/

require_once ECM_PLUGIN_PATH
    . 'includes/modules/overview/trait-event-overview.php';

/*
|--------------------------------------------------------------------------
| Participants Module
|--------------------------------------------------------------------------
|
| Separates event-tab orchestration, UI rendering, CRUD operations,
| CSV import, and CSV export.
|
*/

require_once ECM_PLUGIN_PATH
    . 'includes/modules/participants/trait-event-participants.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/participants/trait-participant-ui.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/participants/trait-participant-crud.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/participants/trait-participant-import.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/participants/trait-participant-export.php';

/*
|--------------------------------------------------------------------------
| Sessions Module
|--------------------------------------------------------------------------
|
| Handles session UI, CRUD operations, participant assignment screens,
| AJAX participant search, assignment, and removal.
|
*/

require_once ECM_PLUGIN_PATH
    . 'includes/modules/sessions/trait-event-sessions.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/sessions/trait-session-ui.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/sessions/trait-session-crud.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/sessions/trait-session-participants-ui.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/sessions/trait-session-participants.php';

/*
|--------------------------------------------------------------------------
| Templates Module
|--------------------------------------------------------------------------
|
| Handles template CRUD, Builder UI, element management, preview
| generation, and template rendering helpers.
|
*/

require_once ECM_PLUGIN_PATH
    . 'includes/modules/templates/trait-event-templates.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/templates/trait-template-builder.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/templates/trait-template-elements.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/templates/trait-template-preview.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/templates/trait-template-renderer.php';

/*
|--------------------------------------------------------------------------
| Settings Module
|--------------------------------------------------------------------------
|
| Handles the event Settings tab, participant-field configuration,
| and session-specific certificate settings.
|
*/

require_once ECM_PLUGIN_PATH
    . 'includes/modules/settings/trait-event-settings.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/settings/trait-participant-fields-ui.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/settings/trait-participant-fields-actions.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/settings/trait-session-settings-ui.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/settings/trait-session-settings-actions.php';

/*
|--------------------------------------------------------------------------
| Fonts Module
|--------------------------------------------------------------------------
*/

require_once ECM_PLUGIN_PATH
    . 'includes/modules/fonts/trait-font-ajax.php';

/*
|--------------------------------------------------------------------------
| Certificates Module
|--------------------------------------------------------------------------
|
| Includes the event Certificates tab and the temporary tc-lib-pdf
| compatibility test action.
|
*/

require_once ECM_PLUGIN_PATH
    . 'includes/modules/certificates/trait-event-certificates.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/certificates/trait-certificate-pdf-test.php';

/*
|--------------------------------------------------------------------------
| Logs Module
|--------------------------------------------------------------------------
*/

require_once ECM_PLUGIN_PATH
    . 'includes/modules/logs/trait-event-logs.php';

/*
|--------------------------------------------------------------------------
| Shared Module
|--------------------------------------------------------------------------
|
| Contains helpers genuinely shared by multiple event modules.
|
*/

require_once ECM_PLUGIN_PATH
    . 'includes/modules/shared/trait-event-helpers.php';

/**
 * ECM Event Workspace Controller.
 */
class ECM_Events
{
    /*
    |--------------------------------------------------------------------------
    | Events and Workspace
    |--------------------------------------------------------------------------
    */

    use ECM_Events_Page;
    use ECM_Event_CRUD;
    use ECM_Event_Workspace;
    use ECM_Event_Overview;

    /*
    |--------------------------------------------------------------------------
    | Participants
    |--------------------------------------------------------------------------
    */

    use ECM_Event_Participants;
    use ECM_Participant_UI;
    use ECM_Participant_CRUD;
    use ECM_Participant_Import;
    use ECM_Participant_Export;

    /*
    |--------------------------------------------------------------------------
    | Sessions
    |--------------------------------------------------------------------------
    */

    use ECM_Event_Sessions;
    use ECM_Session_UI;
    use ECM_Session_CRUD;
    use ECM_Session_Participants_UI;
    use ECM_Session_Participants;

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    */

    use ECM_Event_Templates;
    use ECM_Template_Builder;
    use ECM_Template_Elements;
    use ECM_Template_Preview;
    use ECM_Template_Renderer;

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    use ECM_Event_Settings;
    use ECM_Participant_Fields_UI;
    use ECM_Participant_Fields_Actions;
    use ECM_Session_Settings_UI;
    use ECM_Session_Settings_Actions;

    /*
    |--------------------------------------------------------------------------
    | Fonts
    |--------------------------------------------------------------------------
    */

    use ECM_Font_Ajax;

    /*
    |--------------------------------------------------------------------------
    | Certificates
    |--------------------------------------------------------------------------
    */

    use ECM_Event_Certificates;
    use ECM_Certificate_PDF_Test;

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    */

    use ECM_Event_Logs;

    /*
    |--------------------------------------------------------------------------
    | Shared Helpers
    |--------------------------------------------------------------------------
    */

    use ECM_Event_Helpers;

    /**
     * Register event-level WordPress hooks.
     *
     * This controller must be instantiated only once by ECM_Loader.
     * Registering multiple instances would attach the same handlers
     * more than once.
     */
    public function __construct()
    {
        $this->register_event_hooks();
        $this->register_participant_hooks();
        $this->register_session_hooks();
        $this->register_template_hooks();
        $this->register_settings_hooks();
        $this->register_font_hooks();
        $this->register_certificate_hooks();
    }

    /*
    |--------------------------------------------------------------------------
    | Event Hooks
    |--------------------------------------------------------------------------
    */

    /**
     * Register event CRUD hooks.
     *
     * @return void
     */
    private function register_event_hooks()
    {
        add_action(
            'admin_init',
            [$this, 'handle_event_save']
        );

        add_action(
            'admin_init',
            [$this, 'handle_event_delete']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Participant Hooks
    |--------------------------------------------------------------------------
    */

    /**
     * Register participant CRUD, CSV import, and CSV export hooks.
     *
     * @return void
     */
    private function register_participant_hooks()
    {
        add_action(
            'admin_init',
            [$this, 'handle_add_participant']
        );

        add_action(
            'admin_init',
            [$this, 'handle_update_participant']
        );

        add_action(
            'admin_init',
            [$this, 'handle_delete_participant']
        );

        add_action(
            'admin_init',
            [$this, 'handle_bulk_participant_action']
        );

        add_action(
            'admin_init',
            [$this, 'handle_csv_import']
        );

        add_action(
            'admin_init',
            [$this, 'handle_download_sample_csv']
        );

        add_action(
            'admin_init',
            [$this, 'handle_export_participants_csv']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Session Hooks
    |--------------------------------------------------------------------------
    */

    /**
     * Register session CRUD and participant-assignment hooks.
     *
     * @return void
     */
    private function register_session_hooks()
    {
        add_action(
            'admin_init',
            [$this, 'handle_add_session']
        );

        add_action(
            'admin_init',
            [$this, 'handle_update_session']
        );

        add_action(
            'admin_init',
            [$this, 'handle_delete_session']
        );

        add_action(
            'admin_init',
            [$this, 'handle_add_session_participants']
        );

        add_action(
            'admin_init',
            [$this, 'handle_remove_session_participant']
        );

        add_action(
            'wp_ajax_ecm_search_session_available_participants',
            [$this, 'ajax_search_session_available_participants']
        );

        add_action(
            'wp_ajax_ecm_add_session_participants_ajax',
            [$this, 'ajax_add_session_participants']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Template Hooks
    |--------------------------------------------------------------------------
    */

    /**
     * Register template and Builder hooks.
     *
     * @return void
     */
    private function register_template_hooks()
    {
        add_action(
            'admin_init',
            [$this, 'handle_add_template']
        );

        add_action(
            'admin_init',
            [$this, 'handle_update_template']
        );

        add_action(
            'admin_init',
            [$this, 'handle_delete_template']
        );

        add_action(
            'admin_init',
            [$this, 'handle_upload_template_background']
        );

        add_action(
            'admin_init',
            [$this, 'handle_add_template_element']
        );

        add_action(
            'admin_init',
            [$this, 'handle_delete_template_element']
        );

        add_action(
            'wp_ajax_ecm_update_template_element_properties',
            [$this, 'ajax_update_template_element_properties']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Settings Hooks
    |--------------------------------------------------------------------------
    */

    /**
     * Register participant-field and session-settings hooks.
     *
     * @return void
     */
    private function register_settings_hooks()
    {
        add_action(
            'admin_init',
            [$this, 'handle_add_default_fields']
        );

        add_action(
            'admin_init',
            [$this, 'handle_add_custom_field']
        );

        add_action(
            'admin_init',
            [$this, 'handle_update_custom_field']
        );

        add_action(
            'admin_init',
            [$this, 'handle_delete_custom_field']
        );

        add_action(
            'admin_init',
            [$this, 'handle_save_session_settings']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Font Hooks
    |--------------------------------------------------------------------------
    */

    /**
     * Register local Google Font installation hooks.
     *
     * @return void
     */
    private function register_font_hooks()
    {
        add_action(
            'wp_ajax_ecm_install_google_font',
            [$this, 'ajax_install_google_font']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Certificate Hooks
    |--------------------------------------------------------------------------
    */

    /**
     * Register certificate and PDF compatibility hooks.
     *
     * The compatibility action is temporary and may be removed after
     * tc-lib-pdf bootstrap testing has been completed successfully.
     *
     * @return void
     */
    private function register_certificate_hooks()
    {
        add_action(
            'admin_post_ecm_pdf_compatibility_test',
            [$this, 'handle_pdf_compatibility_test']
        );
    }
}