<?php

/**
 * ECM Participant Import
 *
 * Handles participant CSV imports and event-specific sample CSV
 * downloads.
 *
 * Import validation is based on the participant fields configured
 * for the selected event.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Participant_Import
{
    /* -------------------------------------------------------------------------
     * CSV Participant Import
     * ---------------------------------------------------------------------- */

    /**
     * Import participants from an uploaded CSV file.
     *
     * @return void
     */
    public function handle_csv_import()
    {
        if (!isset($_POST['ecm_import_csv_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_import_csv_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['ecm_import_csv_nonce'])
                ),
                'ecm_import_participants_csv'
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

        if (!$event_id) {
            wp_die('Invalid event.');
        }

        if (
            empty($_FILES['participants_csv']['tmp_name']) ||
            !isset($_FILES['participants_csv']['name'])
        ) {
            wp_die('No CSV file uploaded.');
        }

        $uploaded_name = sanitize_file_name(
            wp_unslash($_FILES['participants_csv']['name'])
        );

        $temporary_path = $_FILES['participants_csv']['tmp_name'];

        if (
            !is_uploaded_file($temporary_path) &&
            !file_exists($temporary_path)
        ) {
            wp_die('The uploaded CSV file is unavailable.');
        }

        $file_type = wp_check_filetype($uploaded_name);

        if (
            strtolower((string) $file_type['ext']) !== 'csv'
        ) {
            wp_die(
                'Invalid file type. Please upload a CSV file.'
            );
        }

        $fields = $this->get_event_fields($event_id);

        if (empty($fields)) {
            wp_die(
                'Participant fields are not configured for this event.'
            );
        }

        $expected_headers = $this->get_participant_csv_headers(
            $fields
        );

        $handle = fopen($temporary_path, 'r');

        if (!$handle) {
            wp_die('Unable to read CSV file.');
        }

        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);

            wp_die('CSV header row is missing.');
        }

        /*
         * Remove a possible UTF-8 BOM from the first column.
         */
        $header[0] = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $header[0]
        );

        $header = array_map(
            'trim',
            $header
        );

        if ($header !== $expected_headers) {
            fclose($handle);

            wp_die(
                'Invalid CSV header. Required header: '
                . esc_html(
                    implode(',', $expected_headers)
                )
            );
        }

        global $wpdb;

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        $inserted = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            /*
             * Skip empty or structurally incomplete rows.
             */
            if (
                empty(array_filter($row, 'strlen')) ||
                count($row) < count($expected_headers)
            ) {
                $skipped++;
                continue;
            }

            $row_data = [];

            foreach ($expected_headers as $index => $key) {
                $row_data[$key] = isset($row[$index])
                    ? sanitize_text_field(
                        trim($row[$index])
                    )
                    : '';
            }

            if (
                !$this->participant_csv_row_is_valid(
                    $fields,
                    $row_data
                )
            ) {
                $skipped++;
                continue;
            }

            if (empty($row_data['member_id'])) {
                $skipped++;
                continue;
            }

            $existing_participant_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$participants_table}
                    WHERE event_id = %d
                    AND member_id = %s",
                    $event_id,
                    $row_data['member_id']
                )
            );

            if ($existing_participant_id) {
                $skipped++;
                continue;
            }

            $inserted_participant = $wpdb->insert(
                $participants_table,
                [
                    'event_id'  => $event_id,
                    'member_id' => $row_data['member_id'],
                ],
                [
                    '%d',
                    '%s',
                ]
            );

            if (!$inserted_participant) {
                $skipped++;
                continue;
            }

            $participant_id = (int) $wpdb->insert_id;

            foreach ($row_data as $key => $value) {
                if ($key === 'member_id') {
                    continue;
                }

                $wpdb->insert(
                    $meta_table,
                    [
                        'participant_id' => $participant_id,
                        'meta_key'       => $key,
                        'meta_value'     => $value,
                    ],
                    [
                        '%d',
                        '%s',
                        '%s',
                    ]
                );
            }

            $inserted++;
        }

        fclose($handle);

        wp_safe_redirect(
            add_query_arg(
                [
                    'csv_imported' => 1,
                    'inserted'     => $inserted,
                    'skipped'      => $skipped,
                ],
                admin_url(
                    'admin.php?page=ecm-events'
                    . '&action=manage'
                    . '&event_id='
                    . $event_id
                    . '&tab=participants'
                )
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Sample CSV Download
     * ---------------------------------------------------------------------- */

    /**
     * Download an event-specific sample participant CSV.
     *
     * The file contains only the required CSV header row.
     *
     * @return void
     */
    public function handle_download_sample_csv()
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
            ) !== 'download_sample_csv'
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
                'ecm_download_sample_csv_' . $event_id
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

        $headers = $this->get_participant_csv_headers(
            $fields
        );

        $filename =
            'ecm-sample-participants-event-'
            . $event_id
            . '.csv';

        $this->clear_output_buffers();

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
            wp_die('Unable to create the sample CSV.');
        }

        fputcsv($output, $headers);

        fclose($output);
        exit;
    }

    /* -------------------------------------------------------------------------
     * Shared CSV Import Helpers
     * ---------------------------------------------------------------------- */

    /**
     * Return CSV headers from event participant fields.
     *
     * @param array $fields Event participant field definitions.
     *
     * @return array
     */
    private function get_participant_csv_headers($fields)
    {
        $headers = [];

        foreach ($fields as $field) {
            $headers[] = sanitize_key(
                $field->field_key
            );
        }

        return $headers;
    }

    /**
     * Validate one participant CSV row.
     *
     * @param array $fields   Event participant field definitions.
     * @param array $row_data Sanitized CSV row data.
     *
     * @return bool
     */
    private function participant_csv_row_is_valid(
        $fields,
        $row_data
    ) {
        foreach ($fields as $field) {
            $key = sanitize_key($field->field_key);

            $value = isset($row_data[$key])
                ? $row_data[$key]
                : '';

            if (
                (int) $field->is_required === 1 &&
                $value === ''
            ) {
                return false;
            }

            if (
                $field->field_type === 'number' &&
                $value !== '' &&
                !ctype_digit($value)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear all active output buffers before sending a CSV file.
     *
     * @return void
     */
    private function clear_output_buffers()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}