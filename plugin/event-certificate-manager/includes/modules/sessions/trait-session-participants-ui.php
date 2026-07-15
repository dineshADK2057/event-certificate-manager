<?php

/**
 * ECM Session Participants UI
 *
 * Renders session participant statistics, assigned-participant lists,
 * participant assignment controls, and participant selection modals.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Session_Participants_UI
{
    /*
     * Paste these four existing methods here unchanged:
     *
     * private function render_session_participants_page(...)


     */


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
