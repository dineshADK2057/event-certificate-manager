<?php

/**
 * ECM Global Logs
 *
 * Renders system-wide certificate and operational logs.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Global_Logs
{
    /**
     * Render logs recorded across all ECM events.
     *
     * @return void
     */
    public function global_logs_page()
    {
        ?>
        <div class="wrap ecm-wrap">
            <div class="ecm-page-header">
                <div>
                    <h1>Logs</h1>

                    <p class="ecm-subtitle">
                        Review certificate and system activity
                        across all events.
                    </p>
                </div>
            </div>

            <div class="ecm-panel ecm-panel-full">
                <h2>System Activity</h2>

                <p>
                    Certificate generation, email delivery,
                    verification activity, downloads, batch jobs,
                    warnings, and errors will appear here.
                </p>
            </div>
        </div>
        <?php
    }
}