<?php

/**
 * ECM Event Sessions
 *
 * Coordinates the Sessions tab inside an event workspace.
 *
 * Detailed session UI, CRUD operations, and participant assignment
 * features are handled by dedicated Sessions traits.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Sessions
{
    /**
     * Render the Sessions tab for one event.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function tab_sessions($event)
    {
        $session_action = isset($_GET['session_action'])
            ? sanitize_key(wp_unslash($_GET['session_action']))
            : '';

        $session_id = isset($_GET['session_id'])
            ? absint($_GET['session_id'])
            : 0;

        /*
         * The session-participants screen is displayed inside
         * the Sessions tab instead of creating a separate admin page.
         */
        if (
            $session_action === 'participants' &&
            $session_id > 0
        ) {
            $this->render_session_participants_page(
                $event,
                $session_id
            );

            return;
        }
        ?>

        <div class="ecm-tab-header">
            <div>
                <h2>Sessions</h2>

                <p>
                    Create and manage sessions under this event.
                </p>
            </div>

            <div class="ecm-tab-actions">
                <button
                    type="button"
                    class="button button-primary ecm-open-session-modal"
                >
                    + Add Session
                </button>
            </div>
        </div>

        <?php $this->render_session_notices(); ?>

        <?php $this->render_sessions_list_section($event); ?>

        <?php $this->render_add_session_modal($event); ?>
        <?php
    }

    /**
     * Render session success and error notices.
     *
     * @return void
     */
    private function render_session_notices()
    {
        if (isset($_GET['session_added'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Session added successfully.</strong>
                </p>
            </div>
            <?php
        }

        if (isset($_GET['session_updated'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Session updated successfully.</strong>
                </p>
            </div>
            <?php
        }

        if (isset($_GET['session_deleted'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Session deleted successfully.</strong>
                </p>
            </div>
            <?php
        }
    }
}