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


    private function render_assigned_session_participants(
        $event,
        $session
    ) {
        global $wpdb;

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        $fields = $this->get_event_fields(
            $event->id
        );

        /*
     * Determine the requested sorting configuration.
     */
        $orderby = isset($_GET['orderby'])
            ? sanitize_key(
                wp_unslash($_GET['orderby'])
            )
            : '';

        $order = (
            isset($_GET['order']) &&
            strtolower(
                sanitize_text_field(
                    wp_unslash($_GET['order'])
                )
            ) === 'asc'
        )
            ? 'ASC'
            : 'DESC';

        /*
     * Only configured participant fields are sortable.
     */
        $sortable_fields = [];

        foreach ($fields as $field) {
            $sortable_fields[$field->field_key] = $field;
        }

        if (
            $orderby !== '' &&
            !isset($sortable_fields[$orderby])
        ) {
            $orderby = '';
        }

        /*
     * Member ID exists directly on the participant table.
     *
     * Dynamic participant fields are stored in
     * ecm_participant_meta and therefore require a
     * dedicated metadata join for sorting.
     */
        if ($orderby === 'member_id') {

            $sql = $wpdb->prepare(
                "SELECT DISTINCT p.*
            FROM {$participants_table} p

            INNER JOIN {$event_participants_table} ep
                ON ep.participant_id = p.id

            INNER JOIN {$session_participants_table} sp
                ON sp.participant_id = p.id

            WHERE ep.event_id = %d
            AND sp.session_id = %d

            ORDER BY p.member_id {$order},
                     p.id DESC",
                $event->id,
                $session->id
            );
        } elseif ($orderby !== '') {

            $sql = $wpdb->prepare(
                "SELECT DISTINCT p.*
            FROM {$participants_table} p

            INNER JOIN {$event_participants_table} ep
                ON ep.participant_id = p.id

            INNER JOIN {$session_participants_table} sp
                ON sp.participant_id = p.id

            LEFT JOIN {$meta_table} sort_meta
                ON sort_meta.participant_id = p.id
                AND sort_meta.meta_key = %s

            WHERE ep.event_id = %d
            AND sp.session_id = %d

            ORDER BY sort_meta.meta_value {$order},
                     p.id DESC",
                $orderby,
                $event->id,
                $session->id
            );
        } else {

            $sql = $wpdb->prepare(
                "SELECT DISTINCT p.*
            FROM {$participants_table} p

            INNER JOIN {$event_participants_table} ep
                ON ep.participant_id = p.id

            INNER JOIN {$session_participants_table} sp
                ON sp.participant_id = p.id

            WHERE ep.event_id = %d
            AND sp.session_id = %d

            ORDER BY p.id DESC",
                $event->id,
                $session->id
            );
        }

        $assigned = $wpdb->get_results($sql);

        /*
     * Base URL used by sortable column headings.
     */
        $sort_base_url = admin_url(
            'admin.php?page=ecm-events'
                . '&action=manage'
                . '&event_id=' . absint($event->id)
                . '&tab=sessions'
                . '&session_action=participants'
                . '&session_id=' . absint($session->id)
        );
    ?>

        <div class="ecm-panel ecm-panel-full">

            <h3>Assigned Participants</h3>

            <?php if (isset($_GET['bulk_session_removed'])) : ?>

                <div class="notice notice-success is-dismissible">
                    <p>
                        <strong>
                            <?php
                            $removed = isset($_GET['removed'])
                                ? absint($_GET['removed'])
                                : 0;

                            echo esc_html(
                                $removed
                                    . ' participant(s) removed from this session.'
                            );
                            ?>
                        </strong>
                    </p>
                </div>

            <?php endif; ?>

            <?php if (isset($_GET['bulk_session_no_selection'])) : ?>

                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong>
                            Please select at least one participant.
                        </strong>
                    </p>
                </div>

            <?php endif; ?>

            <p>
                <button
                    type="button"
                    class="button button-primary ecm-open-session-participants-modal"
                    data-event-id="<?php echo esc_attr($event->id); ?>"
                    data-session-id="<?php echo esc_attr($session->id); ?>">
                    + Add Participants
                </button>
            </p>

            <?php if (empty($assigned)) : ?>

                <p>
                    No participants assigned to this session yet.
                </p>

            <?php else : ?>

                <form
                    method="post"
                    class="ecm-session-assigned-form"
                    onsubmit="return confirm('Remove the selected participants from this session?');">

                    <?php
                    wp_nonce_field(
                        'ecm_bulk_remove_session_participants',
                        'ecm_bulk_session_participant_nonce'
                    );
                    ?>

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?php echo esc_attr($event->id); ?>">

                    <input
                        type="hidden"
                        name="session_id"
                        value="<?php echo esc_attr($session->id); ?>">

                    <div class="ecm-table-actions">

                        <button
                            type="submit"
                            name="ecm_bulk_session_participant_submit"
                            class="button">
                            Remove Selected
                        </button>

                    </div>

                    <table class="widefat striped">

                        <thead>
                            <tr>

                                <th
                                    class="check-column"
                                    style="width:35px;">
                                    <input
                                        type="checkbox"
                                        id="ecm-select-all-session-assigned">
                                </th>

                                <?php foreach ($fields as $field) : ?>

                                    <?php
                                    $is_active =
                                        $orderby ===
                                        $field->field_key;

                                    /*
                                 * Clicking an inactive column begins
                                 * with ascending order.
                                 *
                                 * Clicking the active column toggles
                                 * its current direction.
                                 */
                                    if ($is_active) {
                                        $next_order =
                                            $order === 'ASC'
                                            ? 'desc'
                                            : 'asc';
                                    } else {
                                        $next_order = 'asc';
                                    }

                                    $sort_url = add_query_arg(
                                        [
                                            'orderby' =>
                                            $field->field_key,

                                            'order' =>
                                            $next_order,
                                        ],
                                        $sort_base_url
                                    );

                                    $sort_class =
                                        'ecm-sort-icon';

                                    if (
                                        $is_active &&
                                        $order === 'ASC'
                                    ) {
                                        $sort_class .= ' is-asc';
                                    }
                                    ?>

                                    <th>
                                        <a
                                            href="<?php echo esc_url($sort_url); ?>"
                                            class="ecm-sort-heading <?php echo $is_active ? 'is-active' : ''; ?>">
                                            <span>
                                                <?php
                                                echo esc_html(
                                                    $field->field_label
                                                );
                                                ?>
                                            </span>

                                            <span
                                                class="dashicons dashicons-arrow-down-alt2 <?php echo esc_attr($sort_class); ?>"
                                                aria-hidden="true"></span>
                                        </a>
                                    </th>

                                <?php endforeach; ?>

                                <th width="100">
                                    Actions
                                </th>

                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($assigned as $participant) : ?>

                                <?php
                                $meta_rows =
                                    $wpdb->get_results(
                                        $wpdb->prepare(
                                            "SELECT meta_key, meta_value
                                        FROM {$meta_table}
                                        WHERE participant_id = %d",
                                            $participant->id
                                        ),
                                        OBJECT_K
                                    );

                                $remove_url = wp_nonce_url(
                                    admin_url(
                                        'admin.php?page=ecm-events'
                                            . '&action=remove_session_participant'
                                            . '&event_id=' . absint($event->id)
                                            . '&tab=sessions'
                                            . '&session_action=participants'
                                            . '&session_id=' . absint($session->id)
                                            . '&participant_id=' . absint($participant->id)
                                    ),
                                    'ecm_remove_session_participant_'
                                        . absint($session->id)
                                        . '_'
                                        . absint($participant->id)
                                );
                                ?>

                                <tr>

                                    <th
                                        scope="row"
                                        class="check-column">
                                        <input
                                            type="checkbox"
                                            name="participant_ids[]"
                                            value="<?php echo esc_attr($participant->id); ?>"
                                            class="ecm-session-assigned-checkbox">
                                    </th>

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
                                                $meta_rows[$field->field_key]
                                            )
                                                ? $meta_rows[$field->field_key]->meta_value
                                                : '';
                                        }
                                        ?>

                                        <td>
                                            <?php echo esc_html($value); ?>
                                        </td>

                                    <?php endforeach; ?>

                                    <td>
                                        <a
                                            href="<?php echo esc_url($remove_url); ?>"
                                            onclick="return confirm('Remove this participant from this session?');"
                                            class="ecm-danger-link">
                                            Remove
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </form>

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
        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $available = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT p.*
        FROM {$participants_table} p

        INNER JOIN {$event_participants_table} ep
            ON ep.participant_id = p.id

        WHERE ep.event_id = %d

        AND p.id NOT IN (
            SELECT participant_id
            FROM {$session_participants_table}
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

    /**
     * Bulk remove selected participants from a session.
     *
     * Only session associations are removed. Global participant
     * records and event associations remain untouched.
     *
     * @return void
     */
    public function handle_bulk_remove_session_participants()
    {
        if (
            !isset(
                $_POST['ecm_bulk_session_participant_submit']
            )
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to perform this action.'
            );
        }

        if (
            !isset($_POST['ecm_bulk_session_participant_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['ecm_bulk_session_participant_nonce']
                    )
                ),
                'ecm_bulk_remove_session_participants'
            )
        ) {
            wp_die('Security check failed.');
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
            ? array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'absint',
                            wp_unslash(
                                $_POST['participant_ids']
                            )
                        )
                    )
                )
            )
            : [];

        if (!$event_id || !$session_id) {
            wp_die('Invalid event or session.');
        }

        if (empty($participant_ids)) {
            wp_safe_redirect(
                add_query_arg(
                    'bulk_session_no_selection',
                    1,
                    admin_url(
                        'admin.php?page=ecm-events'
                            . '&action=manage'
                            . '&event_id=' . $event_id
                            . '&tab=sessions'
                            . '&session_action=participants'
                            . '&session_id=' . $session_id
                    )
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
     * Ensure the supplied session genuinely belongs
     * to the supplied event.
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

        $removed = 0;

        foreach ($participant_ids as $participant_id) {

            /*
         * The participant must still belong to this event.
         */
            $event_association = $wpdb->get_var(
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

            if (!$event_association) {
                continue;
            }

            /*
         * Remove only the session association.
         */
            $deleted = $wpdb->delete(
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

            if ($deleted) {
                $removed++;
            }
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'bulk_session_removed' => 1,
                    'removed' => $removed,
                ],
                admin_url(
                    'admin.php?page=ecm-events'
                        . '&action=manage'
                        . '&event_id=' . $event_id
                        . '&tab=sessions'
                        . '&session_action=participants'
                        . '&session_id=' . $session_id
                )
            )
        );

        exit;
    }
}
