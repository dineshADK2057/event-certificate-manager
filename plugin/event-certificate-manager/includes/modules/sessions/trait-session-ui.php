<?php

/**
 * ECM Session UI
 *
 * Renders the event Sessions list and shared Add/Edit Session modal.
 *
 * This trait contains presentation logic only. Session database
 * mutations are handled by the Session CRUD trait.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Session_UI
{
    /* -------------------------------------------------------------------------
     * Sessions List
     * ---------------------------------------------------------------------- */

    /**
     * Render sessions belonging to one event.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_sessions_list_section($event)
    {
        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        $sessions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$sessions_table}
                WHERE event_id = %d
                ORDER BY id DESC",
                $event->id
            )
        );

        if (empty($sessions)) {
            ?>
            <div class="ecm-elements-empty-state">
                <h4>No sessions created yet</h4>

                <p>
                    Add the first session for this event to begin
                    assigning participants and preparing certificates.
                </p>

                <button
                    type="button"
                    class="button button-primary ecm-open-session-modal"
                >
                    + Add Session
                </button>
            </div>
            <?php

            return;
        }
        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Session Code</th>
                    <th>Session Name</th>
                    <th>Tutor / Speaker</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th width="180">Actions</th>
                    <th width="120">Participants</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($sessions as $session) : ?>
                    <?php
                    $participants_url =
                        $this->get_session_participants_url(
                            $event->id,
                            $session->id
                        );

                    $delete_url = wp_nonce_url(
                        admin_url(
                            'admin.php?page=ecm-events'
                            . '&action=delete_session'
                            . '&event_id='
                            . absint($event->id)
                            . '&session_id='
                            . absint($session->id)
                        ),
                        'ecm_delete_session_'
                        . absint($session->id)
                    );

                    $participant_count = (int) $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*)
                            FROM {$session_participants_table}
                            WHERE session_id = %d",
                            $session->id
                        )
                    );
                    ?>

                    <tr>
                        <td>
                            <strong>
                                <?php echo esc_html(
                                    $session->session_code
                                ); ?>
                            </strong>
                        </td>

                        <td>
                            <?php echo esc_html(
                                $session->session_name
                            ); ?>
                        </td>

                        <td>
                            <?php echo esc_html(
                                $session->tutor_name
                            ); ?>
                        </td>

                        <td>
                            <?php echo esc_html(
                                $session->session_date
                            ); ?>
                        </td>

                        <td>
                            <span
                                class="ecm-status ecm-status-<?php
                                echo esc_attr($session->status);
                                ?>"
                            >
                                <?php echo esc_html(
                                    ucfirst($session->status)
                                ); ?>
                            </span>
                        </td>

                        <td>
                            <a
                                href="<?php echo esc_url(
                                    $participants_url
                                ); ?>"
                            >
                                Participants
                            </a>

                            |

                            <a
                                href="#"
                                class="ecm-edit-session"
                                data-session-id="<?php
                                echo esc_attr($session->id);
                                ?>"
                                data-session-name="<?php
                                echo esc_attr($session->session_name);
                                ?>"
                                data-tutor-name="<?php
                                echo esc_attr($session->tutor_name);
                                ?>"
                                data-session-date="<?php
                                echo esc_attr($session->session_date);
                                ?>"
                                data-status="<?php
                                echo esc_attr($session->status);
                                ?>"
                            >
                                Edit
                            </a>

                            |

                            <a
                                href="<?php echo esc_url($delete_url); ?>"
                                onclick="return confirm('Are you sure you want to delete this session?');"
                                class="ecm-danger-link"
                            >
                                Delete
                            </a>
                        </td>

                        <td>
                            <?php echo esc_html($participant_count); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /* -------------------------------------------------------------------------
     * Add/Edit Session Modal
     * ---------------------------------------------------------------------- */

    /**
     * Render the shared Add/Edit Session modal.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_add_session_modal($event)
    {
        ?>
        <div
            id="ecm-add-session-modal"
            class="ecm-modal"
            style="display:none;"
        >
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2 id="ecm-session-modal-title">
                        Add Session
                    </h2>

                    <button
                        type="button"
                        class="ecm-modal-close"
                        aria-label="Close session form"
                    >
                        &times;
                    </button>
                </div>

                <form method="post">
                    <?php
                    wp_nonce_field(
                        'ecm_add_session',
                        'ecm_add_session_nonce'
                    );

                    wp_nonce_field(
                        'ecm_update_session',
                        'ecm_update_session_nonce'
                    );
                    ?>

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?php echo esc_attr($event->id); ?>"
                    >

                    <input
                        type="hidden"
                        name="session_id"
                        id="ecm_session_id"
                        value=""
                    >

                    <div class="ecm-modal-body">
                        <p>
                            <label for="session_name">
                                <strong>Session Name</strong>
                            </label>

                            <input
                                type="text"
                                id="session_name"
                                name="session_name"
                                class="widefat"
                                required
                            >
                        </p>

                        <p>
                            <label for="tutor_name">
                                <strong>Tutor / Speaker</strong>
                            </label>

                            <input
                                type="text"
                                id="tutor_name"
                                name="tutor_name"
                                class="widefat"
                            >
                        </p>

                        <p>
                            <label for="session_date">
                                <strong>Session Date</strong>
                            </label>

                            <input
                                type="date"
                                id="session_date"
                                name="session_date"
                                class="widefat"
                            >
                        </p>

                        <p>
                            <label for="session_status">
                                <strong>Status</strong>
                            </label>

                            <select
                                id="session_status"
                                name="status"
                                class="widefat"
                            >
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="closed">Closed</option>
                            </select>
                        </p>
                    </div>

                    <div class="ecm-modal-footer">
                        <button
                            type="submit"
                            name="ecm_add_session_submit"
                            id="ecm_add_session_submit"
                            class="button button-primary"
                        >
                            Save Session
                        </button>

                        <button
                            type="submit"
                            name="ecm_update_session_submit"
                            id="ecm_update_session_submit"
                            class="button button-primary"
                            style="display:none;"
                        >
                            Update Session
                        </button>

                        <button
                            type="button"
                            class="button ecm-modal-cancel"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}