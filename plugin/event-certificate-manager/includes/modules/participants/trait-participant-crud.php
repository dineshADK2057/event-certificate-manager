<?php

/**
 * ECM Participant CRUD
 *
 * Handles participant creation, updating, individual deletion,
 * and bulk participant actions.
 *
 * This trait contains participant data mutations only. Participant
 * forms and tables are rendered by the Participant UI trait.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Participant_CRUD
{
    /* -------------------------------------------------------------------------
     * Participant Creation
     * ---------------------------------------------------------------------- */

    /**
     * Add a participant to an event.
     *
     * @return void
     */
    public function handle_add_participant()
    {
        if (!isset($_POST['ecm_add_participant_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_add_participant_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['ecm_add_participant_nonce'])
                ),
                'ecm_add_participant'
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

        $submitted_fields = (
            isset($_POST['participant_fields']) &&
            is_array($_POST['participant_fields'])
        )
            ? wp_unslash($_POST['participant_fields'])
            : [];

        if (!$event_id) {
            wp_die('Invalid event.');
        }

        $fields = $this->get_event_fields($event_id);

        if (empty($fields)) {
            wp_die(
                'Participant fields are not configured for this event.'
            );
        }

        $clean_data = $this->sanitize_participant_fields(
            $fields,
            $submitted_fields
        );

        if (empty($clean_data['member_id'])) {
            wp_die('Member ID is required.');
        }

        global $wpdb;

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        /*
         * Member IDs must be unique inside the same event.
         */
        $existing_participant_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$participants_table}
                WHERE event_id = %d
                AND member_id = %s",
                $event_id,
                $clean_data['member_id']
            )
        );

        if ($existing_participant_id) {
            wp_die(
                'This Member ID already exists for this event.'
            );
        }

        $inserted = $wpdb->insert(
            $participants_table,
            [
                'event_id'  => $event_id,
                'member_id' => $clean_data['member_id'],
            ],
            [
                '%d',
                '%s',
            ]
        );

        if (!$inserted) {
            wp_die('Failed to add participant.');
        }

        $participant_id = (int) $wpdb->insert_id;

        /*
         * Member ID lives in the primary participant table.
         * All other dynamic fields are stored as participant metadata.
         */
        foreach ($clean_data as $key => $value) {
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

        wp_safe_redirect(
            $this->get_participants_tab_url(
                $event_id,
                [
                    'participant_added' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Participant Update
     * ---------------------------------------------------------------------- */

    /**
     * Update an existing participant.
     *
     * @return void
     */
    public function handle_update_participant()
    {
        if (!isset($_POST['ecm_update_participant_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_update_participant_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['ecm_update_participant_nonce'])
                ),
                'ecm_update_participant'
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

        $participant_id = isset($_POST['participant_id'])
            ? absint($_POST['participant_id'])
            : 0;

        $submitted_fields = (
            isset($_POST['participant_fields']) &&
            is_array($_POST['participant_fields'])
        )
            ? wp_unslash($_POST['participant_fields'])
            : [];

        if (!$event_id || !$participant_id) {
            wp_die('Invalid participant.');
        }

        $fields = $this->get_event_fields($event_id);

        if (empty($fields)) {
            wp_die(
                'Participant fields are not configured for this event.'
            );
        }

        $clean_data = $this->sanitize_participant_fields(
            $fields,
            $submitted_fields
        );

        if (empty($clean_data['member_id'])) {
            wp_die('Member ID is required.');
        }

        global $wpdb;

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        /*
         * Confirm the participant belongs to the submitted event.
         */
        $participant_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$participants_table}
                WHERE id = %d
                AND event_id = %d",
                $participant_id,
                $event_id
            )
        );

        if (!$participant_exists) {
            wp_die('Participant not found.');
        }

        /*
         * Reject duplicate Member IDs while ignoring the record
         * currently being updated.
         */
        $duplicate = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$participants_table}
                WHERE event_id = %d
                AND member_id = %s
                AND id != %d",
                $event_id,
                $clean_data['member_id'],
                $participant_id
            )
        );

        if ($duplicate) {
            wp_die(
                'This Member ID already exists for this event.'
            );
        }

        $updated = $wpdb->update(
            $participants_table,
            [
                'member_id' => $clean_data['member_id'],
                'updated_at' => current_time('mysql'),
            ],
            [
                'id'       => $participant_id,
                'event_id' => $event_id,
            ],
            [
                '%s',
                '%s',
            ],
            [
                '%d',
                '%d',
            ]
        );

        if ($updated === false) {
            wp_die('Failed to update participant.');
        }

        foreach ($clean_data as $key => $value) {
            if ($key === 'member_id') {
                continue;
            }

            $meta_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$meta_table}
                    WHERE participant_id = %d
                    AND meta_key = %s",
                    $participant_id,
                    $key
                )
            );

            if ($meta_id) {
                $wpdb->update(
                    $meta_table,
                    [
                        'meta_value' => $value,
                    ],
                    [
                        'participant_id' => $participant_id,
                        'meta_key'       => $key,
                    ],
                    [
                        '%s',
                    ],
                    [
                        '%d',
                        '%s',
                    ]
                );
            } else {
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
        }

        wp_safe_redirect(
            $this->get_participants_tab_url(
                $event_id,
                [
                    'participant_updated' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Participant Deletion
     * ---------------------------------------------------------------------- */

    /**
     * Delete one participant and their metadata.
     *
     * @return void
     */
    public function handle_delete_participant()
    {
        if (
            !isset(
                $_GET['page'],
                $_GET['action'],
                $_GET['event_id'],
                $_GET['participant_id']
            ) ||
            sanitize_key(
                wp_unslash($_GET['page'])
            ) !== 'ecm-events' ||
            sanitize_key(
                wp_unslash($_GET['action'])
            ) !== 'delete_participant'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }

        $event_id = absint($_GET['event_id']);
        $participant_id = absint($_GET['participant_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_GET['_wpnonce'])
                ),
                'ecm_delete_participant_' . $participant_id
            )
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        $participant = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id
                FROM {$participants_table}
                WHERE id = %d
                AND event_id = %d",
                $participant_id,
                $event_id
            )
        );

        if (!$participant) {
            wp_die('Participant not found.');
        }

        /*
         * Remove metadata before removing the primary participant row.
         */
        $wpdb->delete(
            $meta_table,
            [
                'participant_id' => $participant_id,
            ],
            [
                '%d',
            ]
        );

        $deleted = $wpdb->delete(
            $participants_table,
            [
                'id'       => $participant_id,
                'event_id' => $event_id,
            ],
            [
                '%d',
                '%d',
            ]
        );

        if ($deleted === false) {
            wp_die('Failed to delete participant.');
        }

        wp_safe_redirect(
            $this->get_participants_tab_url(
                $event_id,
                [
                    'participant_deleted' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Bulk Participant Actions
     * ---------------------------------------------------------------------- */

    /**
     * Process participant bulk actions.
     *
     * Version 1 currently supports bulk deletion only.
     *
     * @return void
     */
    public function handle_bulk_participant_action()
    {
        if (!isset($_POST['ecm_bulk_participant_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_bulk_participant_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['ecm_bulk_participant_nonce'])
                ),
                'ecm_bulk_participant_action'
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

        $bulk_action = isset($_POST['bulk_action'])
            ? sanitize_key(
                wp_unslash($_POST['bulk_action'])
            )
            : '';

        $participant_ids = (
            isset($_POST['participant_ids']) &&
            is_array($_POST['participant_ids'])
        )
            ? array_filter(
                array_map(
                    'absint',
                    wp_unslash($_POST['participant_ids'])
                )
            )
            : [];

        if (!$event_id) {
            wp_die('Invalid event.');
        }

        if ($bulk_action === '') {
            wp_safe_redirect(
                $this->get_participants_tab_url(
                    $event_id,
                    [
                        'bulk_empty_action' => 1,
                    ]
                )
            );

            exit;
        }

        if (empty($participant_ids)) {
            wp_safe_redirect(
                $this->get_participants_tab_url(
                    $event_id,
                    [
                        'bulk_no_selection' => 1,
                    ]
                )
            );

            exit;
        }

        if ($bulk_action !== 'delete') {
            wp_safe_redirect(
                $this->get_participants_tab_url(
                    $event_id,
                    [
                        'bulk_error' => 1,
                    ]
                )
            );

            exit;
        }

        global $wpdb;

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        foreach ($participant_ids as $participant_id) {
            /*
             * Only act on participant IDs belonging to this event.
             */
            $belongs_to_event = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$participants_table}
                    WHERE id = %d
                    AND event_id = %d",
                    $participant_id,
                    $event_id
                )
            );

            if (!$belongs_to_event) {
                continue;
            }

            $wpdb->delete(
                $meta_table,
                [
                    'participant_id' => $participant_id,
                ],
                [
                    '%d',
                ]
            );

            $wpdb->delete(
                $participants_table,
                [
                    'id'       => $participant_id,
                    'event_id' => $event_id,
                ],
                [
                    '%d',
                    '%d',
                ]
            );
        }

        wp_safe_redirect(
            $this->get_participants_tab_url(
                $event_id,
                [
                    'bulk_deleted' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Shared CRUD Helpers
     * ---------------------------------------------------------------------- */

    /**
     * Sanitize and validate submitted participant field values.
     *
     * @param array $fields           Event participant field definitions.
     * @param array $submitted_fields Submitted participant field values.
     *
     * @return array
     */
    private function sanitize_participant_fields(
        $fields,
        $submitted_fields
    ) {
        $clean_data = [];

        foreach ($fields as $field) {
            $key = sanitize_key($field->field_key);

            $value = isset($submitted_fields[$key])
                ? sanitize_text_field(
                    $submitted_fields[$key]
                )
                : '';

            if (
                (int) $field->is_required === 1 &&
                $value === ''
            ) {
                wp_die(
                    esc_html($field->field_label)
                    . ' is required.'
                );
            }

            if (
                $field->field_type === 'number' &&
                $value !== '' &&
                !ctype_digit($value)
            ) {
                wp_die(
                    esc_html($field->field_label)
                    . ' must contain numbers only.'
                );
            }

            $clean_data[$key] = $value;
        }

        return $clean_data;
    }

    /**
     * Build the Participants event-tab URL.
     *
     * @param int   $event_id Event ID.
     * @param array $args     Optional additional query arguments.
     *
     * @return string
     */
    private function get_participants_tab_url(
        $event_id,
        $args = []
    ) {
        $base_url = admin_url(
            'admin.php?page=ecm-events'
            . '&action=manage'
            . '&event_id='
            . absint($event_id)
            . '&tab=participants'
        );

        if (empty($args)) {
            return $base_url;
        }

        return add_query_arg(
            $args,
            $base_url
        );
    }
}