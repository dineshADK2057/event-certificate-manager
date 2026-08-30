<?php

/**
 * ECM Certificate Bulk Actions
 *
 * Handles bulk event certificate operations.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Certificate_Bulk_Actions
{
    /**
     * Regenerate all certificates belonging to selected recipients.
     *
     * @return void
     */
    public function handle_bulk_regenerate_event_certificates()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to regenerate certificates.'
            );
        }

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $participant_ids = (
            isset($_POST['participant_ids']) &&
            is_array($_POST['participant_ids'])
        )
            ? array_values(
                array_filter(
                    array_map(
                        'absint',
                        wp_unslash(
                            $_POST['participant_ids']
                        )
                    )
                )
            )
            : [];

        if (!$event_id) {
            wp_die('Invalid event.');
        }

        check_admin_referer(
            'ecm_bulk_regenerate_certificates_' . $event_id,
            'ecm_bulk_regenerate_nonce'
        );

        $redirect_url =
            admin_url(
                'admin.php?page=ecm-events'
                    . '&action=manage'
                    . '&event_id=' . $event_id
                    . '&tab=certificates'
            );

        if (empty($participant_ids)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'bulk_certificate_no_selection' => 1,
                    ],
                    $redirect_url
                )
            );

            exit;
        }

        global $wpdb;

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        /*
     * Retrieve every certificate belonging to the selected
     * participants within this event.
     */
        $participant_placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count($participant_ids),
                    '%d'
                )
            );

        $query =
            "SELECT id
        FROM {$certificates_table}
        WHERE event_id = %d
        AND participant_id IN ({$participant_placeholders})
        ORDER BY id ASC";

        $query_values =
            array_merge(
                [$event_id],
                $participant_ids
            );

        $certificate_ids =
            $wpdb->get_col(
                $wpdb->prepare(
                    $query,
                    ...$query_values
                )
            );

        if (empty($certificate_ids)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'bulk_certificate_none_found' => 1,
                    ],
                    $redirect_url
                )
            );

            exit;
        }

        $regenerated = 0;
        $failed = 0;

        foreach ($certificate_ids as $certificate_id) {
            $result =
                ECM_Certificate_Generator::regenerate(
                    absint($certificate_id)
                );

            if (is_wp_error($result)) {
                $failed++;
                continue;
            }

            $regenerated++;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'bulk_certificates_regenerated' =>
                    $regenerated,

                    'bulk_certificates_failed' =>
                    $failed,
                ],
                $redirect_url
            )
        );

        exit;
    }

    /**
     * Send all certificates belonging to selected recipients.
     *
     * @return void
     */
    public function handle_bulk_send_event_certificate_emails()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to send certificate emails.'
            );
        }

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $participant_ids = (
            isset($_POST['participant_ids']) &&
            is_array($_POST['participant_ids'])
        )
            ? array_values(
                array_filter(
                    array_map(
                        'absint',
                        wp_unslash(
                            $_POST['participant_ids']
                        )
                    )
                )
            )
            : [];

        if (!$event_id) {
            wp_die('Invalid event.');
        }

        check_admin_referer(
            'ecm_bulk_send_certificate_emails_' . $event_id,
            'ecm_bulk_send_email_nonce'
        );

        $redirect_url =
            admin_url(
                'admin.php?page=ecm-events'
                    . '&action=manage'
                    . '&event_id=' . $event_id
                    . '&tab=certificates'
            );

        if (empty($participant_ids)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'bulk_certificate_email_no_selection' => 1,
                    ],
                    $redirect_url
                )
            );

            exit;
        }

        global $wpdb;

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        $participant_placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count($participant_ids),
                    '%d'
                )
            );

        $query =
            "SELECT id
        FROM {$certificates_table}
        WHERE event_id = %d
        AND participant_id IN ({$participant_placeholders})
        ORDER BY id ASC";

        $query_values =
            array_merge(
                [$event_id],
                $participant_ids
            );

        $certificate_ids =
            $wpdb->get_col(
                $wpdb->prepare(
                    $query,
                    ...$query_values
                )
            );

        if (empty($certificate_ids)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'bulk_certificate_email_none_found' => 1,
                    ],
                    $redirect_url
                )
            );

            exit;
        }

        $sent = 0;
        $failed = 0;

        foreach ($certificate_ids as $certificate_id) {
            $result =
                ECM_Certificate_Email_Delivery::send(
                    absint($certificate_id)
                );

            if (is_wp_error($result)) {
                $failed++;
                continue;
            }

            $sent++;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'bulk_certificate_emails_sent' =>
                    $sent,

                    'bulk_certificate_emails_failed' =>
                    $failed,
                ],
                $redirect_url
            )
        );

        exit;
    }
}
