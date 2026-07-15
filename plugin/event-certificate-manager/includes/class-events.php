<?php

if (!defined('ABSPATH')) {
    exit;
}



require_once ECM_PLUGIN_PATH . 'includes/modules/events/trait-events-page.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/events/trait-event-crud.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/events/trait-event-workspace.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/overview/trait-event-overview.php';

require_once ECM_PLUGIN_PATH . 'includes/modules/trait-event-sessions.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/trait-event-settings.php';

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
require_once ECM_PLUGIN_PATH . 'includes/modules/certificates/trait-certificate-pdf-test.php';


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
    use ECM_Event_Settings;
    use ECM_Event_Helpers;

    use ECM_Event_Templates;
    use ECM_Template_Builder;
    use ECM_Template_Elements;
    use ECM_Template_Preview;
    use ECM_Template_Renderer;

    use ECM_Font_Ajax;

    use ECM_Certificate_PDF_Test;


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








    private function tab_logs($event)
    {
?>
        <h2>Certificate Logs</h2>
        <p>Generated certificate history, email status, downloads, and resend actions will be built here.</p>
    <?php
    }


    private function tab_certificates($event)
    {
    ?>
        <h2>Certificates</h2>
        <p>This module will manage generated certificates, downloads, email sending, QR verification, and resend actions.</p>

        <div class="ecm-panel ecm-panel-full">
            <h3>Certificate Engine</h3>
            <p>Coming soon:</p>
            <ul class="ul-disc">
                <li>Generated certificate list</li>
                <li>Certificate ID management</li>
                <li>Download certificate PDF</li>
                <li>Resend email</li>
                <li>QR verification status</li>
            </ul>
        </div>
<?php
    }

    public function handle_add_session()
    {
        if (isset($_POST['ecm_update_session_submit'])) {
            return;
        }

        if (!isset($_POST['ecm_add_session_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_add_session_nonce']) ||
            !wp_verify_nonce($_POST['ecm_add_session_nonce'], 'ecm_add_session')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id     = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $session_name = sanitize_text_field($_POST['session_name'] ?? '');
        $tutor_name   = sanitize_text_field($_POST['tutor_name'] ?? '');
        $session_date = sanitize_text_field($_POST['session_date'] ?? '');
        $status       = sanitize_text_field($_POST['status'] ?? 'active');

        if (!$event_id) {
            wp_die('Invalid event.');
        }

        if (empty($session_name)) {
            wp_die('Session name is required.');
        }

        $allowed_statuses = ['draft', 'active', 'closed'];

        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'active';
        }

        global $wpdb;

        $sessions_table = $wpdb->prefix . 'ecm_sessions';

        $session_code = $this->generate_session_code($event_id);

        $inserted = $wpdb->insert(
            $sessions_table,
            [
                'event_id'     => $event_id,
                'session_code' => $session_code,
                'session_name' => $session_name,
                'tutor_name'   => $tutor_name,
                'session_date' => $session_date ?: null,
                'status'       => $status,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$inserted) {
            wp_die('Failed to add session.');
        }

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=sessions&session_added=1')
        );
        exit;
    }
}
