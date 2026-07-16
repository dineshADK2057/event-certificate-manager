<?php

/**
 * ECM Participant Fields Actions
 *
 * Handles creation, updating, deletion, and key generation for
 * event-specific participant fields.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Participant_Fields_Actions
{
    /**
     * Participant fields that cannot be deleted.
     *
     * @return array
     */
    private function get_protected_participant_field_keys()
    {
        return [
            'member_id',
            'member_name',
            'home_club',
        ];
    }

    /* -------------------------------------------------------------------------
     * Default Participant Fields
     * ---------------------------------------------------------------------- */

    /**
     * Add the standard participant fields to an event.
     *
     * @return void
     */
    public function handle_add_default_fields()
    {
        if (!isset($_POST['ecm_add_default_fields_submit'])) {
            return;
        }

        $this->verify_event_settings_request(
            'ecm_default_fields_nonce',
            'ecm_add_default_fields'
        );

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        if (!$event_id) {
            wp_die('Invalid event.');
        }

        global $wpdb;

        $fields_table =
            $wpdb->prefix . 'ecm_event_fields';

        $defaults = [
            [
                'field_key'   => 'member_id',
                'field_label' => 'Member ID',
                'field_type'  => 'number',
                'is_required' => 1,
                'field_order' => 1,
            ],
            [
                'field_key'   => 'member_name',
                'field_label' => 'Member Name',
                'field_type'  => 'text',
                'is_required' => 1,
                'field_order' => 2,
            ],
            [
                'field_key'   => 'home_club',
                'field_label' => 'Home Club',
                'field_type'  => 'text',
                'is_required' => 1,
                'field_order' => 3,
            ],
        ];

        foreach ($defaults as $field) {
            $existing_field_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$fields_table}
                    WHERE event_id = %d
                    AND field_key = %s",
                    $event_id,
                    $field['field_key']
                )
            );

            if ($existing_field_id) {
                continue;
            }

            $wpdb->insert(
                $fields_table,
                [
                    'event_id'    => $event_id,
                    'field_key'   => $field['field_key'],
                    'field_label' => $field['field_label'],
                    'field_type'  => $field['field_type'],
                    'is_required' => $field['is_required'],
                    'field_order' => $field['field_order'],
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                ]
            );
        }

        wp_safe_redirect(
            $this->get_event_settings_tab_url(
                $event_id,
                [
                    'fields_added' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Custom Field Creation
     * ---------------------------------------------------------------------- */

    /**
     * Add one custom participant field.
     *
     * @return void
     */
    public function handle_add_custom_field()
    {
        if (!isset($_POST['ecm_add_custom_field_submit'])) {
            return;
        }

        $this->verify_event_settings_request(
            'ecm_custom_field_nonce',
            'ecm_add_custom_field'
        );

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $field_label = isset($_POST['field_label'])
            ? sanitize_text_field(
                wp_unslash($_POST['field_label'])
            )
            : '';

        $field_type = $this->sanitize_participant_field_type(
            $_POST['field_type'] ?? 'text'
        );

        $is_required = isset($_POST['is_required'])
            ? 1
            : 0;

        if (!$event_id || $field_label === '') {
            wp_die('Field label is required.');
        }

        global $wpdb;

        $fields_table =
            $wpdb->prefix . 'ecm_event_fields';

        $field_key = $this->create_unique_field_key(
            $event_id,
            $field_label,
            $fields_table
        );

        $max_order = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(field_order)
                FROM {$fields_table}
                WHERE event_id = %d",
                $event_id
            )
        );

        $inserted = $wpdb->insert(
            $fields_table,
            [
                'event_id'    => $event_id,
                'field_key'   => $field_key,
                'field_label' => $field_label,
                'field_type'  => $field_type,
                'is_required' => $is_required,
                'field_order' => $max_order + 1,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
            ]
        );

        if (!$inserted) {
            wp_die('Failed to add participant field.');
        }

        wp_safe_redirect(
            $this->get_event_settings_tab_url(
                $event_id,
                [
                    'field_added' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Custom Field Update
     * ---------------------------------------------------------------------- */

    /**
     * Update one participant field.
     *
     * @return void
     */
    public function handle_update_custom_field()
    {
        if (!isset($_POST['ecm_update_custom_field_submit'])) {
            return;
        }

        $this->verify_event_settings_request(
            'ecm_update_custom_field_nonce',
            'ecm_update_custom_field'
        );

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $field_id = isset($_POST['field_id'])
            ? absint($_POST['field_id'])
            : 0;

        $field_label = isset($_POST['field_label'])
            ? sanitize_text_field(
                wp_unslash($_POST['field_label'])
            )
            : '';

        $field_type = $this->sanitize_participant_field_type(
            $_POST['field_type'] ?? 'text'
        );

        $is_required = isset($_POST['is_required'])
            ? 1
            : 0;

        $field_order = isset($_POST['field_order'])
            ? absint($_POST['field_order'])
            : 0;

        if (!$event_id || !$field_id || $field_label === '') {
            wp_die('Invalid field data.');
        }

        global $wpdb;

        $fields_table =
            $wpdb->prefix . 'ecm_event_fields';

        $field = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$fields_table}
                WHERE id = %d
                AND event_id = %d",
                $field_id,
                $event_id
            )
        );

        if (!$field) {
            wp_die('Field not found.');
        }

        $updated = $wpdb->update(
            $fields_table,
            [
                'field_label' => $field_label,
                'field_type'  => $field_type,
                'is_required' => $is_required,
                'field_order' => $field_order,
            ],
            [
                'id'       => $field_id,
                'event_id' => $event_id,
            ],
            [
                '%s',
                '%s',
                '%d',
                '%d',
            ],
            [
                '%d',
                '%d',
            ]
        );

        if ($updated === false) {
            wp_die('Failed to update participant field.');
        }

        wp_safe_redirect(
            $this->get_event_settings_tab_url(
                $event_id,
                [
                    'field_updated' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Custom Field Deletion
     * ---------------------------------------------------------------------- */

    /**
     * Delete one non-protected participant field.
     *
     * @return void
     */
    public function handle_delete_custom_field()
    {
        if (
            !isset(
                $_GET['page'],
                $_GET['action'],
                $_GET['event_id'],
                $_GET['field_id']
            ) ||
            sanitize_key(
                wp_unslash($_GET['page'])
            ) !== 'ecm-events' ||
            sanitize_key(
                wp_unslash($_GET['action'])
            ) !== 'delete_custom_field'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }

        $event_id = absint($_GET['event_id']);
        $field_id = absint($_GET['field_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_GET['_wpnonce'])
                ),
                'ecm_delete_custom_field_' . $field_id
            )
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $fields_table =
            $wpdb->prefix . 'ecm_event_fields';

        $field = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$fields_table}
                WHERE id = %d
                AND event_id = %d",
                $field_id,
                $event_id
            )
        );

        if (!$field) {
            wp_die('Field not found.');
        }

        if (
            in_array(
                $field->field_key,
                $this->get_protected_participant_field_keys(),
                true
            )
        ) {
            wp_die('Default fields cannot be deleted.');
        }

        $deleted = $wpdb->delete(
            $fields_table,
            [
                'id'       => $field_id,
                'event_id' => $event_id,
            ],
            [
                '%d',
                '%d',
            ]
        );

        if ($deleted === false) {
            wp_die('Failed to delete participant field.');
        }

        wp_safe_redirect(
            $this->get_event_settings_tab_url(
                $event_id,
                [
                    'field_deleted' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Shared Participant Field Helpers
     * ---------------------------------------------------------------------- */

    /**
     * Convert a field label into a normalized database key.
     *
     * @param string $label Field label.
     *
     * @return string
     */
    private function create_field_key($label)
    {
        $key = sanitize_key(
            str_replace(' ', '_', strtolower($label))
        );

        return $key !== ''
            ? $key
            : 'custom_field';
    }

    /**
     * Create an event-unique field key.
     *
     * @param int    $event_id    Event ID.
     * @param string $field_label Field label.
     * @param string $table       Event fields table.
     *
     * @return string
     */
    private function create_unique_field_key(
        $event_id,
        $field_label,
        $table
    ) {
        global $wpdb;

        $base_key = $this->create_field_key($field_label);
        $field_key = $base_key;
        $counter = 2;

        while (
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$table}
                    WHERE event_id = %d
                    AND field_key = %s",
                    $event_id,
                    $field_key
                )
            )
        ) {
            $field_key = $base_key . '_' . $counter;
            $counter++;
        }

        return $field_key;
    }

    /**
     * Validate and normalize a participant field type.
     *
     * @param string $field_type Submitted field type.
     *
     * @return string
     */
    private function sanitize_participant_field_type($field_type)
    {
        $field_type = sanitize_key(
            wp_unslash($field_type)
        );

        $allowed_types = [
            'text',
            'number',
            'email',
            'select',
        ];

        return in_array($field_type, $allowed_types, true)
            ? $field_type
            : 'text';
    }

    /**
     * Verify a Settings form nonce and capability.
     *
     * @param string $nonce_field Nonce input name.
     * @param string $nonce_action Nonce action.
     *
     * @return void
     */
    private function verify_event_settings_request(
        $nonce_field,
        $nonce_action
    ) {
        if (
            !isset($_POST[$nonce_field]) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST[$nonce_field])
                ),
                $nonce_action
            )
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }
    }

    /**
     * Build the event Settings tab URL.
     *
     * @param int   $event_id Event ID.
     * @param array $args     Additional query arguments.
     *
     * @return string
     */
    private function get_event_settings_tab_url(
        $event_id,
        $args = []
    ) {
        $base_url = admin_url(
            'admin.php?page=ecm-events'
            . '&action=manage'
            . '&event_id='
            . absint($event_id)
            . '&tab=settings'
        );

        return empty($args)
            ? $base_url
            : add_query_arg($args, $base_url);
    }
}