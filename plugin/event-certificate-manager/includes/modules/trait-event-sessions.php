<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Sessions
{

    private function tab_sessions($event)
    {
        $session_action = isset($_GET['session_action']) ? sanitize_text_field($_GET['session_action']) : '';
        $session_id     = isset($_GET['session_id']) ? absint($_GET['session_id']) : 0;

        if ($session_action === 'participants' && $session_id > 0) {
            $this->render_session_participants_page($event, $session_id);
            return;
        } ?>

        <div class="ecm-tab-header">
            <div>
                <h2>Sessions</h2>
                <p>Create and manage sessions under this event.</p>
            </div>

            <div class="ecm-tab-actions">
                <button type="button" class="button button-primary ecm-open-session-modal">
                    + Add Session
                </button>
            </div>
        </div>

        <?php if (isset($_GET['session_added'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Session added successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['session_updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Session updated successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['session_deleted'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Session deleted successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php $this->render_sessions_list_section($event); ?>
        <?php $this->render_add_session_modal($event); ?>
    <?php
    }

    private function render_sessions_list_section($event)
    {
        global $wpdb;

        $sessions_table = $wpdb->prefix . 'ecm_sessions';

        $sessions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $sessions_table WHERE event_id = %d ORDER BY id DESC",
                $event->id
            )
        ); ?>

        <h3>Session List</h3>

        <?php if (empty($sessions)) : ?>
            <p>No sessions created yet.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Session Code</th>
                        <th>Session Name</th>
                        <th>Tutor / Speaker</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th width="130">Actions</th>
                        <th width="120">Participants</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($session->session_code); ?></strong></td>
                            <td><?php echo esc_html($session->session_name); ?></td>
                            <td><?php echo esc_html($session->tutor_name); ?></td>
                            <td><?php echo esc_html($session->session_date); ?></td>
                            <td>
                                <span class="ecm-status ecm-status-<?php echo esc_attr($session->status); ?>">
                                    <?php echo esc_html(ucfirst($session->status)); ?>
                                </span>
                            </td>
                            <?php
                            $delete_url = wp_nonce_url(
                                admin_url(
                                    'admin.php?page=ecm-events&action=delete_session&event_id=' . absint($event->id) . '&session_id=' . absint($session->id)
                                ),
                                'ecm_delete_session_' . absint($session->id)
                            );
                            ?>
                            <?php
                            $participants_url = admin_url(
                                'admin.php?page=ecm-events&action=manage&event_id=' . absint($event->id) .
                                    '&tab=sessions&session_action=participants&session_id=' . absint($session->id)
                            );

                            $delete_url = wp_nonce_url(
                                admin_url(
                                    'admin.php?page=ecm-events&action=delete_session&event_id=' . absint($event->id) . '&session_id=' . absint($session->id)
                                ),
                                'ecm_delete_session_' . absint($session->id)
                            );
                            ?>

                            <td>
                                <a href="<?php echo esc_url($participants_url); ?>">Participants</a>
                                |
                                <a href="#"
                                    class="ecm-edit-session"
                                    data-session-id="<?php echo esc_attr($session->id); ?>"
                                    data-session-name="<?php echo esc_attr($session->session_name); ?>"
                                    data-tutor-name="<?php echo esc_attr($session->tutor_name); ?>"
                                    data-session-date="<?php echo esc_attr($session->session_date); ?>"
                                    data-status="<?php echo esc_attr($session->status); ?>">
                                    Edit
                                </a>
                                |
                                <a href="<?php echo esc_url($delete_url); ?>"
                                    onclick="return confirm('Are you sure you want to delete this session?');"
                                    class="ecm-danger-link">
                                    Delete
                                </a>
                            </td>
                            <?php
                            $session_participants_table = $wpdb->prefix . 'ecm_session_participants';

                            $participant_count = (int) $wpdb->get_var(
                                $wpdb->prepare(
                                    "SELECT COUNT(*) 
                                        FROM $session_participants_table
                                        WHERE session_id = %d",
                                    $session->id
                                )
                            );
                            ?>
                            <td>
                                <?php echo esc_html($participant_count); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php
    }

    private function render_add_session_modal($event)
    {
    ?>
        <div id="ecm-add-session-modal" class="ecm-modal" style="display:none;">
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2 id="ecm-session-modal-title">Add Session</h2>
                    <button type="button" class="ecm-modal-close">&times;</button>
                </div>

                <form method="post">
                    <?php wp_nonce_field('ecm_add_session', 'ecm_add_session_nonce'); ?>
                    <?php wp_nonce_field('ecm_update_session', 'ecm_update_session_nonce'); ?>

                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                    <input type="hidden" name="session_id" id="ecm_session_id" value="">

                    <div class="ecm-modal-body">
                        <p>
                            <label for="session_name">
                                <strong>Session Name</strong>
                            </label>
                            <input type="text" id="session_name" name="session_name" class="widefat" required>
                        </p>

                        <p>
                            <label for="tutor_name">
                                <strong>Tutor / Speaker</strong>
                            </label>
                            <input type="text" id="tutor_name" name="tutor_name" class="widefat">
                        </p>

                        <p>
                            <label for="session_date">
                                <strong>Session Date</strong>
                            </label>
                            <input type="date" id="session_date" name="session_date" class="widefat">
                        </p>

                        <p>
                            <label for="session_status">
                                <strong>Status</strong>
                            </label>
                            <select id="session_status" name="status" class="widefat">
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="closed">Closed</option>
                            </select>
                        </p>
                    </div>

                    <div class="ecm-modal-footer">
                        <button type="submit" name="ecm_add_session_submit" id="ecm_add_session_submit" class="button button-primary">
                            Save Session
                        </button>

                        <button type="submit" name="ecm_update_session_submit" id="ecm_update_session_submit" class="button button-primary" style="display:none;">
                            Update Session
                        </button>

                        <button type="button" class="button ecm-modal-cancel">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php
    }

    public function handle_add_session_participants()
    {
        if (!isset($_POST['ecm_add_session_participants_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_add_session_participants_nonce']) ||
            !wp_verify_nonce($_POST['ecm_add_session_participants_nonce'], 'ecm_add_session_participants')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id   = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;

        $participant_ids = isset($_POST['participant_ids']) && is_array($_POST['participant_ids'])
            ? array_map('absint', $_POST['participant_ids'])
            : [];

        if (!$event_id || !$session_id) {
            wp_die('Invalid session.');
        }

        if (empty($participant_ids)) {
            wp_safe_redirect(
                admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=sessions&session_action=participants&session_id=' . $session_id . '&no_participants_selected=1')
            );
            exit;
        }

        global $wpdb;

        $participants_table         = $wpdb->prefix . 'ecm_participants';
        $session_participants_table = $wpdb->prefix . 'ecm_session_participants';

        foreach ($participant_ids as $participant_id) {
            $participant_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $participants_table WHERE id = %d AND event_id = %d",
                    $participant_id,
                    $event_id
                )
            );

            if (!$participant_exists) {
                continue;
            }

            $already_assigned = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $session_participants_table WHERE session_id = %d AND participant_id = %d",
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
                ['%d', '%d']
            );
        }

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=sessions&session_action=participants&session_id=' . $session_id . '&session_participants_added=1')
        );
        exit;
    }

    public function handle_update_session()
    {
        if (!isset($_POST['ecm_update_session_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_update_session_nonce']) ||
            !wp_verify_nonce($_POST['ecm_update_session_nonce'], 'ecm_update_session')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id     = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $session_id   = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
        $session_name = sanitize_text_field($_POST['session_name'] ?? '');
        $tutor_name   = sanitize_text_field($_POST['tutor_name'] ?? '');
        $session_date = sanitize_text_field($_POST['session_date'] ?? '');
        $status       = sanitize_text_field($_POST['status'] ?? 'active');

        if (!$event_id || !$session_id) {
            wp_die('Invalid session.');
        }

        if (empty($session_name)) {
            wp_die('Session name is required.');
        }

        $allowed_statuses = ['draft', 'active', 'closed'];

        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'active';
        }

        global $wpdb;

        $sessions_table = $wpdb->prefix . 'ecm_sessions';

        $updated = $wpdb->update(
            $sessions_table,
            [
                'session_name' => $session_name,
                'tutor_name'   => $tutor_name,
                'session_date' => $session_date ?: null,
                'status'       => $status,
                'updated_at'   => current_time('mysql'),
            ],
            [
                'id'       => $session_id,
                'event_id' => $event_id,
            ],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d', '%d']
        );

        if ($updated === false) {
            wp_die('Failed to update session.');
        }

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=sessions&session_updated=1')
        );
        exit;
    }

    public function handle_delete_session()
    {
        if (
            !isset($_GET['page'], $_GET['action'], $_GET['event_id'], $_GET['session_id']) ||
            $_GET['page'] !== 'ecm-events' ||
            $_GET['action'] !== 'delete_session'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id   = absint($_GET['event_id']);
        $session_id = absint($_GET['session_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'ecm_delete_session_' . $session_id)
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $sessions_table = $wpdb->prefix . 'ecm_sessions';

        $wpdb->delete(
            $sessions_table,
            [
                'id'       => $session_id,
                'event_id' => $event_id,
            ],
            ['%d', '%d']
        );

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=sessions&session_deleted=1')
        );
        exit;
    }

    private function generate_session_code($event_id)
    {
        global $wpdb;

        $sessions_table = $wpdb->prefix . 'ecm_sessions';

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $sessions_table WHERE event_id = %d",
                $event_id
            )
        );

        $number = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return 'SES-' . $number;
    }

    private function render_session_participants_page($event, $session_id)
    {
        global $wpdb;

        $sessions_table = $wpdb->prefix . 'ecm_sessions';

        $session = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $sessions_table WHERE id = %d AND event_id = %d",
                $session_id,
                $event->id
            )
        );

        if (!$session) {
            echo '<div class="notice notice-error"><p>Session not found.</p></div>';
            return;
        }

        $back_url = admin_url(
            'admin.php?page=ecm-events&action=manage&event_id=' . absint($event->id) . '&tab=sessions'
        );
    ?>

        <div class="ecm-form-header">
            <a href="<?php echo esc_url($back_url); ?>" class="button">
                ← Back to Sessions
            </a>
        </div>

        <div class="ecm-event-heading">
            <div>
                <h2><?php echo esc_html($session->session_name); ?></h2>
                <p>
                    <strong>Session Code:</strong> <?php echo esc_html($session->session_code); ?>
                    &nbsp; | &nbsp;
                    <strong>Tutor / Speaker:</strong> <?php echo esc_html($session->tutor_name); ?>
                    &nbsp; | &nbsp;
                    <strong>Status:</strong>
                    <span class="ecm-status ecm-status-<?php echo esc_attr($session->status); ?>">
                        <?php echo esc_html(ucfirst($session->status)); ?>
                    </span>
                </p>
            </div>
        </div>

        <?php
        global $wpdb;

        $session_participants_table = $wpdb->prefix . 'ecm_session_participants';

        $total_assigned = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM $session_participants_table
                WHERE session_id = %d",
                $session->id
            )
        );
        ?>

        <div class="ecm-panel">
            <h3>Session Statistics</h3>

            <div class="ecm-stat-grid">
                <div class="ecm-stat-card">
                    <span class="ecm-stat-number">
                        <?php echo esc_html($total_assigned); ?>
                    </span>

                    <span class="ecm-stat-label">
                        Participants Assigned
                    </span>
                </div>
            </div>
        </div>
        <?php $this->render_session_settings_panel($event, $session); ?>

        <?php if (isset($_GET['session_participants_added'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Selected participants added to session.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['session_participant_removed'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Participant removed from session.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['session_settings_saved'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Session settings saved successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php $this->render_assigned_session_participants($event, $session); ?>
        <?php $this->render_session_participant_modal($event, $session); ?>

    <?php
    }

    private function render_assigned_session_participants($event, $session)
    {
        global $wpdb;

        $session_participants_table = $wpdb->prefix . 'ecm_session_participants';
        $participants_table         = $wpdb->prefix . 'ecm_participants';
        $meta_table                 = $wpdb->prefix . 'ecm_participant_meta';

        $assigned = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.*
             FROM $participants_table p
             INNER JOIN $session_participants_table sp ON p.id = sp.participant_id
             WHERE sp.session_id = %d
             AND p.event_id = %d
             ORDER BY p.id DESC",
                $session->id,
                $event->id
            )
        );

        $fields = $this->get_event_fields($event->id);
    ?>
        <div class="ecm-panel ecm-panel-full">
            <h3>Assigned Participants</h3>
            <p>
                <button type="button"
                    class="button button-primary ecm-open-session-participants-modal"
                    data-event-id="<?php echo esc_attr($event->id); ?>"
                    data-session-id="<?php echo esc_attr($session->id); ?>">
                    + Add Participants
                </button>
            </p>

            <?php if (empty($assigned)) : ?>
                <p>No participants assigned to this session yet.</p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <?php foreach ($fields as $field) : ?>
                                <th><?php echo esc_html($field->field_label); ?></th>
                            <?php endforeach; ?>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assigned as $participant) : ?>
                            <?php
                            $meta_rows = $wpdb->get_results(
                                $wpdb->prepare(
                                    "SELECT meta_key, meta_value FROM $meta_table WHERE participant_id = %d",
                                    $participant->id
                                ),
                                OBJECT_K
                            );
                            ?>
                            <tr>
                                <?php foreach ($fields as $field) : ?>
                                    <?php
                                    if ($field->field_key === 'member_id') {
                                        $value = $participant->member_id;
                                    } else {
                                        $value = isset($meta_rows[$field->field_key])
                                            ? $meta_rows[$field->field_key]->meta_value
                                            : '';
                                    }
                                    ?>
                                    <td><?php echo esc_html($value); ?></td>
                                <?php endforeach; ?>
                                <?php
                                $remove_url = wp_nonce_url(
                                    admin_url(
                                        'admin.php?page=ecm-events&action=remove_session_participant&event_id=' . absint($event->id) .
                                            '&tab=sessions&session_action=participants&session_id=' . absint($session->id) .
                                            '&participant_id=' . absint($participant->id)
                                    ),
                                    'ecm_remove_session_participant_' . absint($session->id) . '_' . absint($participant->id)
                                );
                                ?>
                                <td>
                                    <a href="<?php echo esc_url($remove_url); ?>"
                                        onclick="return confirm('Remove this participant from this session?');"
                                        class="ecm-danger-link">
                                        Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php
    }

    private function render_session_participant_modal($event, $session)
    {
    ?>
        <div id="ecm-session-participants-modal" class="ecm-modal" style="display:none;">
            <div class="ecm-modal-content ecm-modal-large">
                <div class="ecm-modal-header">
                    <h2>Add Participants to Session</h2>
                    <button type="button" class="ecm-modal-close">&times;</button>
                </div>

                <div class="ecm-modal-body">
                    <?php wp_nonce_field('ecm_session_participant_ajax', 'ecm_session_participant_ajax_nonce'); ?>

                    <input type="hidden" id="ecm_session_modal_event_id" value="<?php echo esc_attr($event->id); ?>">
                    <input type="hidden" id="ecm_session_modal_session_id" value="<?php echo esc_attr($session->id); ?>">

                    <div class="ecm-session-search-bar">
                        <input type="search"
                            id="ecm-session-participant-search"
                            class="regular-text"
                            placeholder="Search by member ID, name, club...">

                        <button type="button" class="button" id="ecm-session-participant-search-btn">
                            Search
                        </button>
                    </div>

                    <p>
                        Selected:
                        <strong id="ecm-session-selected-count">0</strong>
                    </p>

                    <div id="ecm-session-participant-results">
                        <p class="description">Search participants to add them to this session.</p>
                    </div>
                </div>

                <div class="ecm-modal-footer">
                    <button type="button" class="button button-primary" id="ecm-add-selected-session-participants">
                        Add Selected to Session
                    </button>

                    <button type="button" class="button ecm-modal-cancel">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_search_session_available_participants()
    {
        check_ajax_referer('ecm_session_participant_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
        }

        $event_id   = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
        $search     = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';

        if (!$event_id || !$session_id) {
            wp_send_json_error('Invalid event or session.');
        }

        global $wpdb;

        $participants_table         = $wpdb->prefix . 'ecm_participants';
        $meta_table                 = $wpdb->prefix . 'ecm_participant_meta';
        $session_participants_table = $wpdb->prefix . 'ecm_session_participants';

        $fields = $this->get_event_fields($event_id);

        if (empty($fields)) {
            wp_send_json_error('Participant fields not configured.');
        }

        $like = '%' . $wpdb->esc_like($search) . '%';

        if (!empty($search)) {
            $participants = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT p.*
                 FROM $participants_table p
                 LEFT JOIN $meta_table m ON p.id = m.participant_id
                 WHERE p.event_id = %d
                 AND p.id NOT IN (
                    SELECT participant_id
                    FROM $session_participants_table
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
                 FROM $participants_table p
                 WHERE p.event_id = %d
                 AND p.id NOT IN (
                    SELECT participant_id
                    FROM $session_participants_table
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
                            <th><?php echo esc_html($field->field_label); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $participant) : ?>
                        <?php
                        $meta_rows = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT meta_key, meta_value FROM $meta_table WHERE participant_id = %d",
                                $participant->id
                            ),
                            OBJECT_K
                        );
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox"
                                    class="ecm-session-participant-select"
                                    value="<?php echo esc_attr($participant->id); ?>">
                            </td>

                            <?php foreach ($fields as $field) : ?>
                                <?php
                                if ($field->field_key === 'member_id') {
                                    $value = $participant->member_id;
                                } else {
                                    $value = isset($meta_rows[$field->field_key])
                                        ? $meta_rows[$field->field_key]->meta_value
                                        : '';
                                }
                                ?>
                                <td><?php echo esc_html($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php
        }

        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
        ]);
    }

    public function ajax_add_session_participants()
    {
        check_ajax_referer('ecm_session_participant_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
        }

        $event_id   = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;

        $participant_ids = isset($_POST['participant_ids']) && is_array($_POST['participant_ids'])
            ? array_map('absint', $_POST['participant_ids'])
            : [];

        if (!$event_id || !$session_id || empty($participant_ids)) {
            wp_send_json_error('Please select at least one participant.');
        }

        global $wpdb;

        $participants_table         = $wpdb->prefix . 'ecm_participants';
        $session_participants_table = $wpdb->prefix . 'ecm_session_participants';

        $inserted = 0;

        foreach ($participant_ids as $participant_id) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $participants_table WHERE id = %d AND event_id = %d",
                    $participant_id,
                    $event_id
                )
            );

            if (!$exists) {
                continue;
            }

            $already = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $session_participants_table WHERE session_id = %d AND participant_id = %d",
                    $session_id,
                    $participant_id
                )
            );

            if ($already) {
                continue;
            }

            $result = $wpdb->insert(
                $session_participants_table,
                [
                    'session_id'     => $session_id,
                    'participant_id' => $participant_id,
                ],
                ['%d', '%d']
            );

            if ($result) {
                $inserted++;
            }
        }

        wp_send_json_success([
            'message'  => $inserted . ' participant(s) added to session.',
            'inserted' => $inserted,
        ]);
    }

    public function handle_remove_session_participant()
    {
        if (
            !isset($_GET['page'], $_GET['action'], $_GET['event_id'], $_GET['session_id'], $_GET['participant_id']) ||
            $_GET['page'] !== 'ecm-events' ||
            $_GET['action'] !== 'remove_session_participant'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id       = absint($_GET['event_id']);
        $session_id     = absint($_GET['session_id']);
        $participant_id = absint($_GET['participant_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce(
                $_GET['_wpnonce'],
                'ecm_remove_session_participant_' . $session_id . '_' . $participant_id
            )
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $participants_table         = $wpdb->prefix . 'ecm_participants';
        $session_participants_table = $wpdb->prefix . 'ecm_session_participants';

        $participant_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $participants_table WHERE id = %d AND event_id = %d",
                $participant_id,
                $event_id
            )
        );

        if (!$participant_exists) {
            wp_die('Participant not found for this event.');
        }

        $wpdb->delete(
            $session_participants_table,
            [
                'session_id'     => $session_id,
                'participant_id' => $participant_id,
            ],
            ['%d', '%d']
        );

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events&action=manage&event_id=' . $event_id .
                    '&tab=sessions&session_action=participants&session_id=' . $session_id .
                    '&session_participant_removed=1'
            )
        );
        exit;
    }

    private function render_session_settings_panel($event, $session)
    {
        $settings = get_option('ecm_session_settings_' . $session->id, []);

        $certificate_enabled = isset($settings['certificate_enabled']) ? (int) $settings['certificate_enabled'] : 0;
        $qr_enabled          = isset($settings['qr_enabled']) ? (int) $settings['qr_enabled'] : 1;
        $capacity            = isset($settings['capacity']) ? absint($settings['capacity']) : '';
        ?>
        <div class="ecm-panel ecm-panel-full">
            <h3>Session Settings</h3>
            <p class="description">Configure certificate and verification settings for this session.</p>

            <form method="post">
                <?php wp_nonce_field('ecm_save_session_settings', 'ecm_session_settings_nonce'); ?>

                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                <input type="hidden" name="session_id" value="<?php echo esc_attr($session->id); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row">Certificate Generation</th>
                        <td>
                            <label>
                                <input type="checkbox" name="certificate_enabled" value="1" <?php checked($certificate_enabled, 1); ?>>
                                Enable certificate generation for this session
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">QR Verification</th>
                        <td>
                            <label>
                                <input type="checkbox" name="qr_enabled" value="1" <?php checked($qr_enabled, 1); ?>>
                                Enable QR verification for certificates generated from this session
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="session_capacity">Session Capacity</label>
                        </th>
                        <td>
                            <input type="number"
                                id="session_capacity"
                                name="capacity"
                                value="<?php echo esc_attr($capacity); ?>"
                                min="0"
                                class="small-text">
                            <p class="description">Optional. Leave empty or 0 for unlimited capacity.</p>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="ecm_save_session_settings_submit" class="button button-primary">
                        Save Session Settings
                    </button>
                </p>
            </form>
        </div>
    <?php
    }

    public function handle_save_session_settings()
    {
        if (!isset($_POST['ecm_save_session_settings_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_session_settings_nonce']) ||
            !wp_verify_nonce($_POST['ecm_session_settings_nonce'], 'ecm_save_session_settings')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id   = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;

        if (!$event_id || !$session_id) {
            wp_die('Invalid session.');
        }

        $settings = [
            'certificate_enabled' => isset($_POST['certificate_enabled']) ? 1 : 0,
            'qr_enabled'          => isset($_POST['qr_enabled']) ? 1 : 0,
            'capacity'            => isset($_POST['capacity']) ? absint($_POST['capacity']) : 0,
        ];

        update_option('ecm_session_settings_' . $session_id, $settings, false);

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events&action=manage&event_id=' . $event_id .
                    '&tab=sessions&session_action=participants&session_id=' . $session_id .
                    '&session_settings_saved=1'
            )
        );
        exit;
    }

    private function render_available_session_participants($event, $session)
    {
        global $wpdb;

        $participants_table         = $wpdb->prefix . 'ecm_participants';
        $session_participants_table = $wpdb->prefix . 'ecm_session_participants';
        $meta_table                 = $wpdb->prefix . 'ecm_participant_meta';

        $available = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.*
             FROM $participants_table p
             WHERE p.event_id = %d
             AND p.id NOT IN (
                SELECT participant_id
                FROM $session_participants_table
                WHERE session_id = %d
             )
             ORDER BY p.id DESC",
                $event->id,
                $session->id
            )
        );

        $fields = $this->get_event_fields($event->id);
    ?>
        <div class="ecm-panel ecm-panel-full">
            <h3>Available Event Participants</h3>
            <p>Select participants to add to this session.</p>

            <?php if (empty($available)) : ?>
                <p>No available participants found. All participants may already be assigned to this session.</p>
            <?php else : ?>
                <form method="post">
                    <?php wp_nonce_field('ecm_add_session_participants', 'ecm_add_session_participants_nonce'); ?>
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                    <input type="hidden" name="session_id" value="<?php echo esc_attr($session->id); ?>">

                    <p>
                        <button type="submit" name="ecm_add_session_participants_submit" class="button button-primary">
                            Add Selected to Session
                        </button>
                    </p>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th width="35">
                                    <input type="checkbox" id="ecm-select-all-session-available">
                                </th>
                                <?php foreach ($fields as $field) : ?>
                                    <th><?php echo esc_html($field->field_label); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available as $participant) : ?>
                                <?php
                                $meta_rows = $wpdb->get_results(
                                    $wpdb->prepare(
                                        "SELECT meta_key, meta_value FROM $meta_table WHERE participant_id = %d",
                                        $participant->id
                                    ),
                                    OBJECT_K
                                );
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                            name="participant_ids[]"
                                            value="<?php echo esc_attr($participant->id); ?>"
                                            class="ecm-session-available-checkbox">
                                    </td>

                                    <?php foreach ($fields as $field) : ?>
                                        <?php
                                        if ($field->field_key === 'member_id') {
                                            $value = $participant->member_id;
                                        } else {
                                            $value = isset($meta_rows[$field->field_key])
                                                ? $meta_rows[$field->field_key]->meta_value
                                                : '';
                                        }
                                        ?>
                                        <td><?php echo esc_html($value); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            <?php endif; ?>
        </div>
<?php
    }
}
