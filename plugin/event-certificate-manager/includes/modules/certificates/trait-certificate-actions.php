<?php

/**
 * ECM Certificate Actions
 *
 * Handles individual event certificate operations.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Certificate_Actions
{
    /**
     * Generate one certificate from the Event Certificates tab.
     *
     * @return void
     */
    public function handle_generate_event_certificate()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to generate certificates.'
            );
        }

        check_admin_referer(
            'ecm_generate_event_certificate',
            'ecm_generate_certificate_nonce'
        );

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $participant_id = isset($_POST['participant_id'])
            ? absint($_POST['participant_id'])
            : 0;

        $template_id = isset($_POST['template_id'])
            ? absint($_POST['template_id'])
            : 0;

        if (
            !$event_id ||
            !$participant_id ||
            !$template_id
        ) {
            wp_die(
                'Event, participant, and template are required.'
            );
        }

        global $wpdb;

        $templates_table =
            $wpdb->prefix . 'ecm_templates';

        /*
     * Load the selected template while simultaneously
     * establishing that it belongs to this event.
     */
        $template = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, event_id, session_id
            FROM {$templates_table}
            WHERE id = %d
            AND event_id = %d
            LIMIT 1",
                $template_id,
                $event_id
            )
        );

        if (!$template) {
            wp_die(
                'The selected certificate template does not belong to this event.'
            );
        }

        /*
     * Session-specific templates determine their own session.
     * Event-wide templates use NULL.
     */
        $session_id = !empty($template->session_id)
            ? absint($template->session_id)
            : null;

        $result = ECM_Certificate_Generator::generate(
            [
                'event_id' =>
                $event_id,

                'participant_id' =>
                $participant_id,

                'template_id' =>
                $template_id,

                'session_id' =>
                $session_id,

                'force' =>
                false,
            ]
        );

        $redirect_url = admin_url(
            'admin.php?page=ecm-events'
                . '&action=manage'
                . '&event_id=' . $event_id
                . '&tab=certificates'
        );

        if (is_wp_error($result)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'certificate_generation_error' =>
                        $result->get_error_code(),
                    ],
                    $redirect_url
                )
            );

            exit;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'certificate_generated' => 1,
                    'certificate_id' =>
                    $result->certificate_id,
                ],
                $redirect_url
            )
        );

        exit;
    }

    /**
     * Regenerate one existing certificate.
     *
     * @return void
     */
    public function handle_regenerate_event_certificate()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to regenerate certificates.'
            );
        }

        $certificate_record_id =
            isset($_POST['certificate_id'])
            ? absint($_POST['certificate_id'])
            : 0;

        $event_id =
            isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        if (
            !$certificate_record_id ||
            !$event_id
        ) {
            wp_die(
                'Invalid certificate regeneration request.'
            );
        }

        check_admin_referer(
            'ecm_regenerate_certificate_'
                . $certificate_record_id,
            'ecm_regenerate_certificate_nonce'
        );

        global $wpdb;

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        /*
     * Prevent regeneration of a certificate belonging to
     * another event through a manipulated request.
     */
        $belongs_to_event =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                FROM {$certificates_table}
                WHERE id = %d
                AND event_id = %d
                LIMIT 1",
                    $certificate_record_id,
                    $event_id
                )
            );

        if (!$belongs_to_event) {
            wp_die(
                'The selected certificate does not belong to this event.'
            );
        }

        $result =
            ECM_Certificate_Generator::regenerate(
                $certificate_record_id
            );

        $redirect_url =
            admin_url(
                'admin.php?page=ecm-events'
                    . '&action=manage'
                    . '&event_id=' . $event_id
                    . '&tab=certificates'
            );

        if (is_wp_error($result)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'certificate_regeneration_error' =>
                        $result->get_error_code(),
                    ],
                    $redirect_url
                )
            );

            exit;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'certificate_regenerated' => 1,
                ],
                $redirect_url
            )
        );

        exit;
    }

    /**
     * Send or resend one existing certificate by email.
     *
     * @return void
     */
    public function handle_send_event_certificate_email()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to send certificate emails.'
            );
        }

        $certificate_record_id =
            isset($_POST['certificate_id'])
            ? absint($_POST['certificate_id'])
            : 0;

        $event_id =
            isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        if (
            !$certificate_record_id ||
            !$event_id
        ) {
            wp_die(
                'Invalid certificate email request.'
            );
        }

        check_admin_referer(
            'ecm_send_certificate_email_'
                . $certificate_record_id,
            'ecm_send_certificate_email_nonce'
        );

        global $wpdb;

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        /*
     * Ensure the certificate belongs to the current event.
     */
        $belongs_to_event =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                FROM {$certificates_table}
                WHERE id = %d
                AND event_id = %d
                LIMIT 1",
                    $certificate_record_id,
                    $event_id
                )
            );

        if (!$belongs_to_event) {
            wp_die(
                'The selected certificate does not belong to this event.'
            );
        }

        $result =
            ECM_Certificate_Email_Delivery::send(
                $certificate_record_id
            );

        $redirect_url =
            admin_url(
                'admin.php?page=ecm-events'
                    . '&action=manage'
                    . '&event_id=' . $event_id
                    . '&tab=certificates'
            );

        if (is_wp_error($result)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'certificate_email_error' =>
                        $result->get_error_code(),
                    ],
                    $redirect_url
                )
            );

            exit;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'certificate_emailed' => 1,
                ],
                $redirect_url
            )
        );

        exit;
    }
}
