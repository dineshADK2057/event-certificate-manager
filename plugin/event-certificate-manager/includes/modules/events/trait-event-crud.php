<?php

/**
 * ECM Event CRUD
 *
 * Handles event creation, updating, deletion,
 * and automatic event-code generation.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_CRUD
{
    /**
     * Create or update an event.
     *
     * @return void
     */
    public function handle_event_save()
    {
        if (!isset($_POST['ecm_save_event_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_event_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['ecm_event_nonce'])
                ),
                'ecm_save_event'
            )
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }

        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $event_name = sanitize_text_field(
            wp_unslash($_POST['event_name'] ?? '')
        );

        $event_type = sanitize_text_field(
            wp_unslash($_POST['event_type'] ?? '')
        );

        $venue = sanitize_text_field(
            wp_unslash($_POST['venue'] ?? '')
        );

        $start_date = sanitize_text_field(
            wp_unslash($_POST['start_date'] ?? '')
        );

        $end_date = sanitize_text_field(
            wp_unslash($_POST['end_date'] ?? '')
        );

        $status = sanitize_text_field(
            wp_unslash($_POST['status'] ?? 'draft')
        );

        if ($event_name === '') {
            wp_die('Event name is required.');
        }

        $allowed_statuses = [
            'draft',
            'active',
            'closed',
        ];

        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'draft';
        }

        $data = [
            'event_name' => $event_name,
            'event_type' => $event_type,
            'venue'      => $venue,
            'start_date' => $start_date ?: null,
            'end_date'   => $end_date ?: null,
            'status'     => $status,
            'updated_at' => current_time('mysql'),
        ];

        $formats = [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        ];

        if ($event_id > 0) {
            $updated = $wpdb->update(
                $table,
                $data,
                [
                    'id' => $event_id,
                ],
                $formats,
                [
                    '%d',
                ]
            );

            if ($updated === false) {
                wp_die('Failed to update event.');
            }

            wp_safe_redirect(
                admin_url(
                    'admin.php?page=ecm-events&updated=1'
                )
            );

            exit;
        }

        $data['event_code'] = $this->generate_event_code(
            $event_name
        );

        $inserted = $wpdb->insert(
            $table,
            $data,
            array_merge(
                $formats,
                [
                    '%s',
                ]
            )
        );

        if (!$inserted) {
            wp_die('Failed to save event.');
        }

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events&created=1'
            )
        );

        exit;
    }

    /**
     * Delete an event.
     *
     * @return void
     */
    public function handle_event_delete()
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
            ) !== 'delete'
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
                'ecm_delete_event_' . $event_id
            )
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';

        $deleted = $wpdb->delete(
            $table,
            [
                'id' => $event_id,
            ],
            [
                '%d',
            ]
        );

        if ($deleted === false) {
            wp_die('Failed to delete event.');
        }

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events&deleted=1'
            )
        );

        exit;
    }

    /**
     * Generate a unique event code.
     *
     * @param string $event_name Event name.
     *
     * @return string
     */
    private function generate_event_code($event_name)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';

        $prefix = strtoupper(
            preg_replace(
                '/[^A-Z0-9]/',
                '',
                substr($event_name, 0, 8)
            )
        );

        if ($prefix === '') {
            $prefix = 'EVENT';
        }

        $count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table}"
        );

        $number = str_pad(
            (string) ($count + 1),
            4,
            '0',
            STR_PAD_LEFT
        );

        return $prefix . '-' . $number;
    }
}