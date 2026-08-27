<?php

/**
 * ECM Session Participants
 *
 * Handles adding, searching, assigning, and removing participants
 * from event sessions.
 *
 * Event membership is resolved through the dedicated
 * ecm_event_participants association table.
 *
 * Both traditional form actions and AJAX-based assignment actions
 * are supported because the existing interface uses both workflows.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Session_Participants
{
    /**
     * Add selected event participants to a session.
     *
     * @return void
     */
    public function handle_add_session_participants()
    {
        if (!isset($_POST['ecm_add_session_participants_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_add_session_participants_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['ecm_add_session_participants_nonce'])
                ),
                'ecm_add_session_participants'
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

        if (!$event_id || !$session_id) {
            wp_die('Invalid session.');
        }

        if (empty($participant_ids)) {
            wp_safe_redirect(
                admin_url(
                    'admin.php?page=ecm-events'
                    . '&action=manage'
                    . '&event_id=' . $event_id
                    . '&tab=sessions'
                    . '&session_action=participants'
                    . '&session_id=' . $session_id
                    . '&no_participants_selected=1'
                )
            );

            exit;
        }

        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        /*
         * The session must belong to the submitted event.
         */
        $session_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$sessions_table}
                WHERE id = %d
                AND event_id = %d
                LIMIT 1",
                $session_id,
                $event_id
            )
        );

        if (!$session_exists) {
            wp_die('Session not found for this event.');
        }

        foreach ($participant_ids as $participant_id) {
            /*
             * A participant may enter a session only if they
             * are already associated with this event.
             */
            $participant_exists = $wpdb->get_var(
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

            if (!$participant_exists) {
                continue;
            }

            $already_assigned = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$session_participants_table}
                    WHERE session_id = %d
                    AND participant_id = %d
                    LIMIT 1",
                    $session_id,
                    $participant_id
                )
            );

            if ($already_assigned) {
                continue;
            }

            $wpdb->insert(
                $session_participants_table,
                [
                    'session_id'     => $session_id,
                    'participant_id' => $participant_id,
                ],
                [
                    '%d',
                    '%d',
                ]
            );
        }

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events'
                . '&action=manage'
                . '&event_id=' . $event_id
                . '&tab=sessions'
                . '&session_action=participants'
                . '&session_id=' . $session_id
                . '&session_participants_added=1'
            )
        );

        exit;
    }

    /**
     * Search event participants that are available for assignment
     * to a session.
     *
     * @return void
     */
    public function ajax_search_session_available_participants()
    {
        check_ajax_referer(
            'ecm_session_participant_ajax',
            'nonce'
        );

        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                'Permission denied.'
            );
        }

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $session_id = isset($_POST['session_id'])
            ? absint($_POST['session_id'])
            : 0;

        $search = isset($_POST['search'])
            ? sanitize_text_field(
                wp_unslash($_POST['search'])
            )
            : '';

        if (!$event_id || !$session_id) {
            wp_send_json_error(
                'Invalid event or session.'
            );
        }

        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        /*
         * Prevent a session from being queried through
         * an unrelated event.
         */
        $session_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$sessions_table}
                WHERE id = %d
                AND event_id = %d
                LIMIT 1",
                $session_id,
                $event_id
            )
        );

        if (!$session_exists) {
            wp_send_json_error(
                'Session not found for this event.'
            );
        }

        $fields = $this->get_event_fields(
            $event_id
        );

        if (empty($fields)) {
            wp_send_json_error(
                'Participant fields not configured.'
            );
        }

        $like =
            '%' . $wpdb->esc_like($search) . '%';

        if (!empty($search)) {
            $participants = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT p.*
                    FROM {$participants_table} p

                    INNER JOIN {$event_participants_table} ep
                        ON ep.participant_id = p.id

                    LEFT JOIN {$meta_table} m
                        ON p.id = m.participant_id

                    WHERE ep.event_id = %d

                    AND p.id NOT IN (
                        SELECT participant_id
                        FROM {$session_participants_table}
                        WHERE session_id = %d
                    )

                    AND (
                        p.member_id LIKE %s
                        OR m.meta_value LIKE %s
                    )

                    ORDER BY p.id DESC
                    LIMIT 50",
                    $event_id,
                    $session_id,
                    $like,
                    $like
                )
            );
        } else {
            $participants = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT p.*
                    FROM {$participants_table} p

                    INNER JOIN {$event_participants_table} ep
                        ON ep.participant_id = p.id

                    WHERE ep.event_id = %d

                    AND p.id NOT IN (
                        SELECT participant_id
                        FROM {$session_participants_table}
                        WHERE session_id = %d
                    )

                    ORDER BY p.id DESC
                    LIMIT 50",
                    $event_id,
                    $session_id
                )
            );
        }

        ob_start();

        if (empty($participants)) {
            echo '<p>No available participants found.</p>';
        } else {
            ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th width="35"></th>

                        <?php foreach ($fields as $field) : ?>
                            <th>
                                <?php
                                echo esc_html(
                                    $field->field_label
                                );
                                ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($participants as $participant) : ?>
                        <?php
                        $meta_rows = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT meta_key, meta_value
                                FROM {$meta_table}
                                WHERE participant_id = %d",
                                $participant->id
                            ),
                            OBJECT_K
                        );
                        ?>

                        <tr>
                            <td>
                                <input
                                    type="checkbox"
                                    class="ecm-session-participant-select"
                                    value="<?php echo esc_attr($participant->id); ?>"
                                >
                            </td>

                            <?php foreach ($fields as $field) : ?>
                                <?php
                                if (
                                    $field->field_key ===
                                    'member_id'
                                ) {
                                    $value =
                                        $participant->member_id;
                                } else {
                                    $value = isset(
                                        $meta_rows[
                                            $field->field_key
                                        ]
                                    )
                                        ? $meta_rows[
                                            $field->field_key
                                        ]->meta_value
                                        : '';
                                }
                                ?>

                                <td>
                                    <?php
                                    echo esc_html($value);
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }

        $html = ob_get_clean();

        wp_send_json_success(
            [
                'html' => $html,
            ]
        );
    }

    /**
     * Add selected event participants to a session through AJAX.
     *
     * @return void
     */
    public function ajax_add_session_participants()
    {
        check_ajax_referer(
            'ecm_session_participant_ajax',
            'nonce'
        );

        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                'Permission denied.'
            );
        }

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $session_id = isset($_POST['session_id'])
            ? absint($_POST['session_id'])
            : 0;

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

        if (
            !$event_id ||
            !$session_id ||
            empty($participant_ids)
        ) {
            wp_send_json_error(
                'Please select at least one participant.'
            );
        }

        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        /*
         * Verify that the session belongs to this event.
         */
        $session_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$sessions_table}
                WHERE id = %d
                AND event_id = %d
                LIMIT 1",
                $session_id,
                $event_id
            )
        );

        if (!$session_exists) {
            wp_send_json_error(
                'Session not found for this event.'
            );
        }

        $inserted = 0;

        foreach ($participant_ids as $participant_id) {
            /*
             * Session membership is subordinate to
             * event membership.
             */
            $participant_exists = $wpdb->get_var(
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

            if (!$participant_exists) {
                continue;
            }

            $already_assigned = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM {$session_participants_table}
                    WHERE session_id = %d
                    AND participant_id = %d
                    LIMIT 1",
                    $session_id,
                    $participant_id
                )
            );

            if ($already_assigned) {
                continue;
            }

            $result = $wpdb->insert(
                $session_participants_table,
                [
                    'session_id' =>
                        $session_id,

                    'participant_id' =>
                        $participant_id,
                ],
                [
                    '%d',
                    '%d',
                ]
            );

            if ($result) {
                $inserted++;
            }
        }

        wp_send_json_success(
            [
                'message' =>
                    $inserted
                    . ' participant(s) added to session.',

                'inserted' =>
                    $inserted,
            ]
        );
    }

    /**
     * Remove a participant from a session.
     *
     * This removes only the session association. The participant's
     * event membership and global identity remain untouched.
     *
     * @return void
     */
    public function handle_remove_session_participant()
    {
        if (
            !isset(
                $_GET['page'],
                $_GET['action'],
                $_GET['event_id'],
                $_GET['session_id'],
                $_GET['participant_id']
            ) ||
            sanitize_key(
                wp_unslash($_GET['page'])
            ) !== 'ecm-events' ||
            sanitize_key(
                wp_unslash($_GET['action'])
            ) !== 'remove_session_participant'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }

        $event_id =
            absint($_GET['event_id']);

        $session_id =
            absint($_GET['session_id']);

        $participant_id =
            absint($_GET['participant_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_GET['_wpnonce'])
                ),
                'ecm_remove_session_participant_'
                . $session_id
                . '_'
                . $participant_id
            )
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        /*
         * Verify that the session belongs to this event.
         */
        $session_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$sessions_table}
                WHERE id = %d
                AND event_id = %d
                LIMIT 1",
                $session_id,
                $event_id
            )
        );

        if (!$session_exists) {
            wp_die(
                'Session not found for this event.'
            );
        }

        /*
         * Verify current event membership through the
         * association table.
         */
        $participant_exists = $wpdb->get_var(
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

        if (!$participant_exists) {
            wp_die(
                'Participant not found for this event.'
            );
        }

        /*
         * Remove only the session association.
         */
        $wpdb->delete(
            $session_participants_table,
            [
                'session_id' =>
                    $session_id,

                'participant_id' =>
                    $participant_id,
            ],
            [
                '%d',
                '%d',
            ]
        );

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events'
                . '&action=manage'
                . '&event_id=' . $event_id
                . '&tab=sessions'
                . '&session_action=participants'
                . '&session_id=' . $session_id
                . '&session_participant_removed=1'
            )
        );

        exit;
    }
}