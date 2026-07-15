<?php

if (!defined('ABSPATH')) {
    exit;
}



require_once ECM_PLUGIN_PATH . 'includes/modules/events/trait-events-page.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/events/trait-event-crud.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/events/trait-event-workspace.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/overview/trait-event-overview.php';


require_once ECM_PLUGIN_PATH . 'includes/modules/trait-event-settings.php';

/*
 * Sessions module.
 */
require_once ECM_PLUGIN_PATH . 'includes/modules/sessions/trait-event-sessions.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/sessions/trait-session-ui.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/sessions/trait-session-crud.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/sessions/trait-session-participants-ui.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/sessions/trait-session-participants.php';


/*
 * Participants module.
 */
require_once ECM_PLUGIN_PATH . 'includes/modules/participants/trait-event-participants.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/participants/trait-participant-ui.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/participants/trait-participant-crud.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/participants/trait-participant-import.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/participants/trait-participant-export.php';


require_once ECM_PLUGIN_PATH . 'includes/modules/trait-event-helpers.php';

require_once ECM_PLUGIN_PATH . 'includes/modules/templates/trait-event-templates.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/templates/trait-template-builder.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/templates/trait-template-elements.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/templates/trait-template-preview.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/templates/trait-template-renderer.php';

require_once ECM_PLUGIN_PATH . 'includes/modules/fonts/trait-font-ajax.php';

/*
 * Certificates module.
 */
require_once ECM_PLUGIN_PATH
    . 'includes/modules/certificates/trait-event-certificates.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/certificates/trait-certificate-pdf-test.php';

/*
 * Logs module.
 */
require_once ECM_PLUGIN_PATH
    . 'includes/modules/logs/trait-event-logs.php';


class ECM_Events
{


    use ECM_Events_Page;
    use ECM_Event_CRUD;
    use ECM_Event_Workspace;
    use ECM_Event_Overview;

    use ECM_Event_Participants;
    use ECM_Participant_UI;
    use ECM_Participant_CRUD;
    use ECM_Participant_Import;
    use ECM_Participant_Export;

    use ECM_Event_Sessions;
    use ECM_Session_UI;
    use ECM_Session_CRUD;
    use ECM_Session_Participants_UI;
    use ECM_Session_Participants;



    use ECM_Event_Settings;
    use ECM_Event_Helpers;

    /*
    * Templates module.
    */
    use ECM_Event_Templates;
    use ECM_Template_Builder;
    use ECM_Template_Elements;
    use ECM_Template_Preview;
    use ECM_Template_Renderer;

    use ECM_Font_Ajax;

    /*
    * Certificates module.
    */
    use ECM_Event_Certificates;
    use ECM_Certificate_PDF_Test;

    /*
    * Logs module.
    */
    use ECM_Event_Logs;


    public function __construct()
    {
        add_action('admin_init', [$this, 'handle_event_save']);
        add_action('admin_init', [$this, 'handle_event_delete']);

        add_action('admin_init', [$this, 'handle_add_default_fields']);
        add_action('admin_init', [$this, 'handle_add_custom_field']);
        add_action('admin_init', [$this, 'handle_update_custom_field']);
        add_action('admin_init', [$this, 'handle_delete_custom_field']);

        add_action('admin_init', [$this, 'handle_add_participant']);
        add_action('admin_init', [$this, 'handle_update_participant']);
        add_action('admin_init', [$this, 'handle_delete_participant']);
        add_action('admin_init', [$this, 'handle_bulk_participant_action']);

        add_action('admin_init', [$this, 'handle_csv_import']);
        add_action('admin_init', [$this, 'handle_download_sample_csv']);
        add_action('admin_init', [$this, 'handle_export_participants_csv']);

        add_action('admin_init', [$this, 'handle_add_session']);
        add_action('admin_init', [$this, 'handle_update_session']);
        add_action('admin_init', [$this, 'handle_delete_session']);
        add_action('admin_init', [$this, 'handle_add_session_participants']);

        add_action('wp_ajax_ecm_search_session_available_participants', [$this, 'ajax_search_session_available_participants']);
        add_action('wp_ajax_ecm_add_session_participants_ajax', [$this, 'ajax_add_session_participants']);

        add_action('admin_init', [$this, 'handle_remove_session_participant']);
        add_action('admin_init', [$this, 'handle_save_session_settings']);

        add_action('admin_init', [$this, 'handle_add_template']);
        add_action('admin_init', [$this, 'handle_update_template']);
        add_action('admin_init', [$this, 'handle_delete_template']);
        add_action('admin_init', [$this, 'handle_upload_template_background']);
        add_action('admin_init', [$this, 'handle_add_template_element']);
        add_action('admin_init', [$this, 'handle_delete_template_element']);

        add_action(
            'wp_ajax_ecm_update_template_element_properties',
            [$this, 'ajax_update_template_element_properties']
        );

        add_action(
            'wp_ajax_ecm_install_google_font',
            [$this, 'ajax_install_google_font']
        );

        add_action(
            'admin_post_ecm_pdf_compatibility_test',
            [$this, 'handle_pdf_compatibility_test']
        );
    }



    
}
