<?php

/**
 * ECM Global Templates
 *
 * Renders the global certificate-template overview.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Global_Templates
{
    /**
     * Render templates belonging to all events.
     *
     * @return void
     */
    public function global_templates_page()
    {
        ?>
        <div class="wrap ecm-wrap">
            <div class="ecm-page-header">
                <div>
                    <h1>Templates</h1>

                    <p class="ecm-subtitle">
                        View certificate templates across all events.
                    </p>
                </div>
            </div>

            <div class="ecm-panel ecm-panel-full">
                <h2>Global Template Overview</h2>

                <p>
                    This page will display template name, event,
                    session, certificate type, orientation, page
                    size, and Builder access.
                </p>

                <p>
                    Templates are currently created and managed from
                    the Templates tab inside each event.
                </p>
            </div>
        </div>
        <?php
    }
}