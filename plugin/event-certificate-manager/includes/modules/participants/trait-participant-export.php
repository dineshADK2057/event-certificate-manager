<?php

/**
 * ECM Participant Export
 *
 * Handles exporting participants belonging to one event as CSV.
 *
 * Export columns follow the event's configured participant fields
 * and retain the same order used by participant import.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Participant_Export
{
    /**
     * Export all participants belonging to one event as CSV.
     *
     * @return void
     */
    public function handle_export_participants_csv()
    {
        if (
            !isset(
                $_GET['page'],
                $_GET['action'],
                $_GET['event_id']
            ) ||
            sanitize_key(
                wp_unslash($_GET['page'])
            ) !== 'ecm-events' ||
            sanitize_key(
                wp_unslash($_GET['action'])
            ) !== 'export_participants_csv'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }

        $event_id = absint($_GET['event_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_GET['_wpnonce'])
                ),
                'ecm_export_participants_csv_' . $event_id
            )
        ) {
            wp_die('Security check failed.');
        }

        $fields = $this->get_event_fields($event_id);

        if (empty($fields)) {
            wp_die(
                'Participant fields are not configured for this event.'
            );
        }

        global $wpdb;

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        $participants = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$participants_table}
                WHERE event_id = %d
                ORDER BY id ASC",
                $event_id
            )
        );

        $headers = [];

        foreach ($fields as $field) {
            $headers[] = sanitize_key(
                $field->field_key
            );
        }

        $filename =
            'ecm-participants-event-'
            . $event_id
            . '.csv';

        /*
         * Prevent previous WordPress or PHP output from corrupting
         * the downloaded CSV file.
         */
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        nocache_headers();

        header(
            'Content-Type: text/csv; charset=utf-8'
        );

        header(
            'Content-Disposition: attachment; filename="'
            . sanitize_file_name($filename)
            . '"'
        );

        $output = fopen('php://output', 'w');

        if (!$output) {
            wp_die('Unable to create participant export.');
        }

        fputcsv($output, $headers);

        foreach ($participants as $participant) {
            $meta_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_key, meta_value
                    FROM {$meta_table}
                    WHERE participant_id = %d",
                    $participant->id
                ),
                OBJECT_K
            );

            $row = [];

            foreach ($fields as $field) {
                if ($field->field_key === 'member_id') {
                    $row[] = $participant->member_id;
                    continue;
                }

                $row[] = isset(
                    $meta_rows[$field->field_key]
                )
                    ? $meta_rows[
                        $field->field_key
                    ]->meta_value
                    : '';
            }

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}