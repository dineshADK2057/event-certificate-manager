<?php

/**
 * ECM Session CRUD
 *
 * Handles session creation, updating, deletion, session-code
 * generation, and shared Sessions URL helpers.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Session_CRUD
{
    /* -------------------------------------------------------------------------
     * Session Creation
     * ---------------------------------------------------------------------- */

    /**
     * Add a session to an event.
     *
     * @return void
     */
    public function handle_add_session()
    {
        /*
         * The Add and Update buttons exist in the same modal.
         * Prevent the add handler from processing update requests.
         */
        if (isset($_POST['ecm_update_session_submit'])) {
            return;
        }

        if (!isset($_POST['ecm_add_session_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_add_session_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['ecm_add_session_nonce'])
                ),
                'ecm_add_session'
            )
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }

        $session_data = $this->sanitize_session_request();

        if (!$session_data['event_id']) {
            wp_die('Invalid event.');
        }

        if ($session_data['session_name'] === '') {
            wp_die('Session name is required.');
        }

        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $session_code = $this->generate_session_code(
            $session_data['event_id']
        );

        $inserted = $wpdb->insert(
            $sessions_table,
            [
                'event_id'     => $session_data['event_id'],
                'session_code' => $session_code,
                'session_name' => $session_data['session_name'],
                'tutor_name'   => $session_data['tutor_name'],
                'session_date' => $session_data['session_date']
                    ?: null,
                'status'       => $session_data['status'],
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if (!$inserted) {
            wp_die('Failed to add session.');
        }

        wp_safe_redirect(
            $this->get_sessions_tab_url(
                $session_data['event_id'],
                [
                    'session_added' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Session Update
     * ---------------------------------------------------------------------- */

    /**
     * Update an existing event session.
     *
     * @return void
     */
    public function handle_update_session()
    {
        if (!isset($_POST['ecm_update_session_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_update_session_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['ecm_update_session_nonce'])
                ),
                'ecm_update_session'
            )
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }

        $session_data = $this->sanitize_session_request();

        $session_id = isset($_POST['session_id'])
            ? absint($_POST['session_id'])
            : 0;

        if (!$session_data['event_id'] || !$session_id) {
            wp_die('Invalid session.');
        }

        if ($session_data['session_name'] === '') {
            wp_die('Session name is required.');
        }

        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $updated = $wpdb->update(
            $sessions_table,
            [
                'session_name' => $session_data['session_name'],
                'tutor_name'   => $session_data['tutor_name'],
                'session_date' => $session_data['session_date']
                    ?: null,
                'status'       => $session_data['status'],
                'updated_at'   => current_time('mysql'),
            ],
            [
                'id'       => $session_id,
                'event_id' => $session_data['event_id'],
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ],
            [
                '%d',
                '%d',
            ]
        );

        if ($updated === false) {
            wp_die('Failed to update session.');
        }

        wp_safe_redirect(
            $this->get_sessions_tab_url(
                $session_data['event_id'],
                [
                    'session_updated' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Session Deletion
     * ---------------------------------------------------------------------- */

    /**
     * Delete a session belonging to an event.
     *
     * @return void
     */
    public function handle_delete_session()
    {
        if (
            !isset(
                $_GET['page'],
                $_GET['action'],
                $_GET['event_id'],
                $_GET['session_id']
            ) ||
            sanitize_key(
                wp_unslash($_GET['page'])
            ) !== 'ecm-events' ||
            sanitize_key(
                wp_unslash($_GET['action'])
            ) !== 'delete_session'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }

        $event_id = absint($_GET['event_id']);
        $session_id = absint($_GET['session_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_GET['_wpnonce'])
                ),
                'ecm_delete_session_' . $session_id
            )
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        /*
         * Remove participant assignments before deleting the session.
         */
        $wpdb->delete(
            $session_participants_table,
            [
                'session_id' => $session_id,
            ],
            [
                '%d',
            ]
        );

        $deleted = $wpdb->delete(
            $sessions_table,
            [
                'id'       => $session_id,
                'event_id' => $event_id,
            ],
            [
                '%d',
                '%d',
            ]
        );

        if ($deleted === false) {
            wp_die('Failed to delete session.');
        }

        wp_safe_redirect(
            $this->get_sessions_tab_url(
                $event_id,
                [
                    'session_deleted' => 1,
                ]
            )
        );

        exit;
    }

    /* -------------------------------------------------------------------------
     * Shared Session Helpers
     * ---------------------------------------------------------------------- */

    /**
     * Sanitize shared session form values.
     *
     * @return array
     */
    private function sanitize_session_request()
    {
        $allowed_statuses = [
            'draft',
            'active',
            'closed',
        ];

        $status = isset($_POST['status'])
            ? sanitize_key(wp_unslash($_POST['status']))
            : 'active';

        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'active';
        }

        return [
            'event_id' => isset($_POST['event_id'])
                ? absint($_POST['event_id'])
                : 0,

            'session_name' => isset($_POST['session_name'])
                ? sanitize_text_field(
                    wp_unslash($_POST['session_name'])
                )
                : '',

            'tutor_name' => isset($_POST['tutor_name'])
                ? sanitize_text_field(
                    wp_unslash($_POST['tutor_name'])
                )
                : '',

            'session_date' => isset($_POST['session_date'])
                ? sanitize_text_field(
                    wp_unslash($_POST['session_date'])
                )
                : '',

            'status' => $status,
        ];
    }

    /**
     * Generate the next event-specific session code.
     *
     * @param int $event_id Event ID.
     *
     * @return string
     */
    private function generate_session_code($event_id)
    {
        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$sessions_table}
                WHERE event_id = %d",
                $event_id
            )
        );

        $number = str_pad(
            (string) ($count + 1),
            3,
            '0',
            STR_PAD_LEFT
        );

        return 'SES-' . $number;
    }

    /**
     * Build the main Sessions event-tab URL.
     *
     * @param int   $event_id Event ID.
     * @param array $args     Optional query arguments.
     *
     * @return string
     */
    private function get_sessions_tab_url(
        $event_id,
        $args = []
    ) {
        $base_url = admin_url(
            'admin.php?page=ecm-events'
            . '&action=manage'
            . '&event_id='
            . absint($event_id)
            . '&tab=sessions'
        );

        return empty($args)
            ? $base_url
            : add_query_arg($args, $base_url);
    }

    /**
     * Build a session-participants management URL.
     *
     * @param int   $event_id   Event ID.
     * @param int   $session_id Session ID.
     * @param array $args       Optional query arguments.
     *
     * @return string
     */
    private function get_session_participants_url(
        $event_id,
        $session_id,
        $args = []
    ) {
        $base_url = add_query_arg(
            [
                'page'           => 'ecm-events',
                'action'         => 'manage',
                'event_id'       => absint($event_id),
                'tab'            => 'sessions',
                'session_action' => 'participants',
                'session_id'     => absint($session_id),
            ],
            admin_url('admin.php')
        );

        return empty($args)
            ? $base_url
            : add_query_arg($args, $base_url);
    }
}