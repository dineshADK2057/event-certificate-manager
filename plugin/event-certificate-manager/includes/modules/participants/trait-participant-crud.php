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
     * Participants are global identities. Event membership is stored
     * separately in the event-participants association table.
     *
     * During the foundation migration, the legacy event_id column on the
     * participant record is still populated when a participant is first
     * created so older ECM components remain operational until their
     * queries are refactored.
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

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        /*
     * Look for the participant globally.
     *
     * Member ID represents the participant identity and therefore
     * must no longer be scoped to one event.
     */
        $participant_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
            FROM {$participants_table}
            WHERE member_id = %s
            ORDER BY id ASC
            LIMIT 1",
                $clean_data['member_id']
            )
        );

        /*
     * If the participant already exists globally, ensure that they
     * are not already assigned to this event.
     */
        if ($participant_id) {
            $existing_association = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                FROM {$event_participants_table}
                WHERE event_id = %d
                AND participant_id = %d
                LIMIT 1",
                    $event_id,
                    $participant_id
                )
            );

            if ($existing_association) {
                wp_die(
                    'This participant is already assigned to this event.'
                );
            }
        } else {
            /*
         * Create the global participant.
         *
         * event_id remains temporarily populated as a compatibility
         * value for ECM components that have not yet been migrated
         * away from the legacy participant architecture.
         */
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
                wp_die('Failed to create participant.');
            }

            $participant_id = (int) $wpdb->insert_id;
        }

        /*
     * Associate the global participant with this event.
     */
        $association_inserted = $wpdb->insert(
            $event_participants_table,
            [
                'event_id'       => $event_id,
                'participant_id' => $participant_id,
                'created_at'     => current_time('mysql'),
            ],
            [
                '%d',
                '%d',
                '%s',
            ]
        );

        if (!$association_inserted) {
            /*
         * Do not delete the participant here.
         *
         * An existing global participant may legitimately belong to
         * other events, so removing the participant record would be
         * destructive.
         */
            wp_die(
                'Failed to assign participant to this event.'
            );
        }

        /*
     * Store participant metadata.
     *
     * Metadata belongs to the global participant rather than to an
     * individual event association.
     *
     * Existing metadata is updated when a known participant is
     * assigned to another event. Missing metadata is inserted.
     */
        foreach ($clean_data as $key => $value) {
            if ($key === 'member_id') {
                continue;
            }

            $existing_meta_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                FROM {$meta_table}
                WHERE participant_id = %d
                AND meta_key = %s
                LIMIT 1",
                    $participant_id,
                    $key
                )
            );

            if ($existing_meta_id) {
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
    /**
     * Update an existing global participant.
     *
     * Event membership is verified through the event-participants
     * association table rather than the legacy participant.event_id
     * column.
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

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        /*
     * Confirm that this global participant is actually
     * associated with the submitted event.
     */
        $association_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
            FROM {$event_participants_table}
            WHERE event_id = %d
            AND participant_id = %d
            LIMIT 1",
                $event_id,
                $participant_id
            )
        );

        if (!$association_exists) {
            wp_die(
                'This participant is not assigned to this event.'
            );
        }

        /*
     * Member ID represents the participant's global identity.
     *
     * Therefore another global participant must not already
     * use the submitted Member ID.
     */
        $duplicate_participant = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
            FROM {$participants_table}
            WHERE member_id = %s
            AND id != %d
            LIMIT 1",
                $clean_data['member_id'],
                $participant_id
            )
        );

        if ($duplicate_participant) {
            wp_die(
                'Another participant already uses this Member ID.'
            );
        }

        /*
     * Update the global participant record.
     *
     * Do not use the legacy event_id column as a condition.
     * Event membership now belongs to ecm_event_participants.
     */
        $updated = $wpdb->update(
            $participants_table,
            [
                'member_id'  => $clean_data['member_id'],
                'updated_at' => current_time('mysql'),
            ],
            [
                'id' => $participant_id,
            ],
            [
                '%s',
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ($updated === false) {
            wp_die('Failed to update participant.');
        }

        /*
     * Update global participant metadata.
     */
        foreach ($clean_data as $key => $value) {
            if ($key === 'member_id') {
                continue;
            }

            $meta_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                FROM {$meta_table}
                WHERE participant_id = %d
                AND meta_key = %s
                LIMIT 1",
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
     * Remove one participant from an event.
     *
     * The participant remains a global identity. Only the event
     * association and event-specific session assignments are removed.
     *
     * Global participant metadata and historical certificates are
     * deliberately preserved.
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

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        /*
     * Confirm that the participant is actually associated
     * with the submitted event.
     */
        $association_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
            FROM {$event_participants_table}
            WHERE event_id = %d
            AND participant_id = %d
            LIMIT 1",
                $event_id,
                $participant_id
            )
        );

        if (!$association_id) {
            wp_die(
                'This participant is not assigned to this event.'
            );
        }

        /*
     * Remove session assignments belonging only to this event.
     *
     * Session assignments from other events must remain intact.
     */
        $wpdb->query(
            $wpdb->prepare(
                "DELETE sp
            FROM {$session_participants_table} sp
            INNER JOIN {$sessions_table} s
                ON s.id = sp.session_id
            WHERE sp.participant_id = %d
            AND s.event_id = %d",
                $participant_id,
                $event_id
            )
        );

        /*
     * Remove only the participant-to-event association.
     *
     * Do not delete:
     * - the global participant record,
     * - participant metadata,
     * - certificates,
     * - associations with other events.
     */
        $deleted = $wpdb->delete(
            $event_participants_table,
            [
                'event_id'       => $event_id,
                'participant_id' => $participant_id,
            ],
            [
                '%d',
                '%d',
            ]
        );

        if ($deleted === false) {
            wp_die(
                'Failed to remove participant from this event.'
            );
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
     * Version 1 currently supports removing selected participants
     * from the current event.
     *
     * Global participant records, participant metadata, certificates,
     * and associations with other events are preserved.
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

        /*
     * During this refactor, the existing UI still submits
     * "delete". Semantically, the action now means:
     * remove selected participants from this event.
     */
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

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        foreach ($participant_ids as $participant_id) {
            /*
         * Confirm that the selected global participant is
         * actually associated with the current event.
         */
            $association_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                FROM {$event_participants_table}
                WHERE event_id = %d
                AND participant_id = %d
                LIMIT 1",
                    $event_id,
                    $participant_id
                )
            );

            if (!$association_exists) {
                continue;
            }

            /*
         * Remove session assignments belonging only to
         * sessions inside the current event.
         */
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE sp
                FROM {$session_participants_table} sp
                INNER JOIN {$sessions_table} s
                    ON s.id = sp.session_id
                WHERE sp.participant_id = %d
                AND s.event_id = %d",
                    $participant_id,
                    $event_id
                )
            );

            /*
         * Remove only the event association.
         *
         * Preserve:
         * - ecm_participants
         * - ecm_participant_meta
         * - certificates
         * - other event associations
         */
            $wpdb->delete(
                $event_participants_table,
                [
                    'event_id'       => $event_id,
                    'participant_id' => $participant_id,
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
