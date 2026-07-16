<?php

/**
 * ECM Dashboard Page
 *
 * Renders the global Event Certificate Manager dashboard.
 *
 * The dashboard provides cross-event statistics and a quick
 * overview of the complete ECM installation.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Dashboard_Page
{
    /**
     * Render the global ECM dashboard.
     *
     * @return void
     */
    public function dashboard_page()
    {
        global $wpdb;

        $events_table =
            $wpdb->prefix . 'ecm_events';

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $templates_table =
            $wpdb->prefix . 'ecm_templates';

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        $events_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$events_table}"
        );

        $participants_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$participants_table}"
        );

        $templates_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$templates_table}"
        );

        $certificates_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$certificates_table}"
        );
        ?>

        <div class="wrap ecm-wrap">
            <div class="ecm-page-header">
                <div>
                    <h1>Event Certificate Manager</h1>

                    <p class="ecm-subtitle">
                        Manage events, participants, templates,
                        certificates, verification, and delivery.
                    </p>
                </div>
            </div>

            <div class="ecm-dashboard-grid">
                <div class="ecm-card">
                    <span class="ecm-card-label">
                        Events
                    </span>

                    <strong>
                        <?php echo esc_html($events_count); ?>
                    </strong>
                </div>

                <div class="ecm-card">
                    <span class="ecm-card-label">
                        Participants
                    </span>

                    <strong>
                        <?php echo esc_html(
                            $participants_count
                        ); ?>
                    </strong>
                </div>

                <div class="ecm-card">
                    <span class="ecm-card-label">
                        Templates
                    </span>

                    <strong>
                        <?php echo esc_html($templates_count); ?>
                    </strong>
                </div>

                <div class="ecm-card">
                    <span class="ecm-card-label">
                        Certificates
                    </span>

                    <strong>
                        <?php echo esc_html(
                            $certificates_count
                        ); ?>
                    </strong>
                </div>
            </div>

            <div class="ecm-panel">
                <h2>Getting Started</h2>

                <ol>
                    <li>Create an event.</li>
                    <li>Configure participant fields.</li>
                    <li>Add participants or import a CSV.</li>
                    <li>Create and design certificate templates.</li>
                    <li>Generate and distribute certificates.</li>
                </ol>
            </div>
        </div>
        <?php
    }
}