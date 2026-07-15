<?php

/**
 * ECM Event Logs
 *
 * Renders the Logs tab inside an event workspace.
 *
 * This event-scoped view will later show certificate generation,
 * email delivery, downloads, verification activity, and failures
 * belonging only to the selected event.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Logs
{
    /**
     * Render the Logs tab for one event.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function tab_logs($event)
    {
        ?>
        <div class="ecm-tab-header">
            <div>
                <h2>Certificate Logs</h2>

                <p>
                    Review certificate activity recorded for this event.
                </p>
            </div>
        </div>

        <div class="ecm-panel ecm-panel-full">
            <h3>Event Activity</h3>

            <p>
                Generation history, email status, downloads, failures,
                verification activity, and resend actions will appear here.
            </p>
        </div>
        <?php
    }
}