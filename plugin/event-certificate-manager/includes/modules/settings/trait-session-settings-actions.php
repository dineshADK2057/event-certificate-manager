<?php

/**
 * ECM Session Settings Actions
 *
 * Handles saving settings belonging to one event session.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Session_Settings_Actions
{
    /**
     * Save certificate, QR, and capacity settings for a session.
     *
     * @return void
     */
    public function handle_save_session_settings()
    {
        if (!isset($_POST['ecm_save_session_settings_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_session_settings_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['ecm_session_settings_nonce']
                    )
                ),
                'ecm_save_session_settings'
            )
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $session_id = isset($_POST['session_id'])
            ? absint($_POST['session_id'])
            : 0;

        if (!$event_id || !$session_id) {
            wp_die('Invalid session.');
        }

        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        /*
         * Confirm the session belongs to the submitted event before
         * saving an option for it.
         */
        $session_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$sessions_table}
                WHERE id = %d
                AND event_id = %d",
                $session_id,
                $event_id
            )
        );

        if (!$session_exists) {
            wp_die('Session not found for this event.');
        }

        $settings = [
            'certificate_enabled' =>
                isset($_POST['certificate_enabled'])
                    ? 1
                    : 0,

            'qr_enabled' =>
                isset($_POST['qr_enabled'])
                    ? 1
                    : 0,

            'capacity' =>
                isset($_POST['capacity'])
                    ? absint($_POST['capacity'])
                    : 0,
        ];

        update_option(
            'ecm_session_settings_' . $session_id,
            $settings,
            false
        );

        wp_safe_redirect(
            add_query_arg(
                [
                    'settings_session_id' =>
                        $session_id,

                    'session_settings_saved' =>
                        1,
                ],
                admin_url(
                    'admin.php?page=ecm-events'
                    . '&action=manage'
                    . '&event_id='
                    . $event_id
                    . '&tab=settings'
                )
            )
        );

        exit;
    }
}