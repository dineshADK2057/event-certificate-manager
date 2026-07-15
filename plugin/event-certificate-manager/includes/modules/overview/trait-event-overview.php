<?php

/**
 * ECM Event Overview
 *
 * Renders the Overview tab inside an event workspace.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Overview
{
    /**
     * Render the event Overview tab.
     *
     * @param object $event Event record.
     *
     * @return void
     */
    private function tab_overview($event)
    {
        ?>
        <h2>Overview</h2>

        <p>
            This is the main control center for this event.
        </p>

        <table class="widefat striped ecm-details-table">
            <tbody>
                <tr>
                    <th>Event Name</th>
                    <td>
                        <?php echo esc_html(
                            $event->event_name
                        ); ?>
                    </td>
                </tr>

                <tr>
                    <th>Event Code</th>
                    <td>
                        <?php echo esc_html(
                            $event->event_code
                        ); ?>
                    </td>
                </tr>

                <tr>
                    <th>Type</th>
                    <td>
                        <?php echo esc_html(
                            $event->event_type
                        ); ?>
                    </td>
                </tr>

                <tr>
                    <th>Venue</th>
                    <td>
                        <?php echo esc_html(
                            $event->venue
                        ); ?>
                    </td>
                </tr>

                <tr>
                    <th>Start Date</th>
                    <td>
                        <?php echo esc_html(
                            $event->start_date
                        ); ?>
                    </td>
                </tr>

                <tr>
                    <th>End Date</th>
                    <td>
                        <?php echo esc_html(
                            $event->end_date
                        ); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}