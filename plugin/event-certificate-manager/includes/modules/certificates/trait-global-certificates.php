<?php

/**
 * ECM Global Certificates
 *
 * Renders the global generated-certificate directory.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Global_Certificates
{
    /**
     * Render certificates generated across all events.
     *
     * @return void
     */
    public function global_certificates_page()
    {
        ?>
        <div class="wrap ecm-wrap">
            <div class="ecm-page-header">
                <div>
                    <h1>Certificates</h1>

                    <p class="ecm-subtitle">
                        Review generated certificates across all events.
                    </p>
                </div>
            </div>

            <div class="ecm-panel ecm-panel-full">
                <h2>Certificate Directory</h2>

                <p>
                    Generated certificate IDs, recipients, events,
                    sessions, generation status, email status, and
                    download actions will appear here.
                </p>

                <p>
                    This page will become operational after the
                    Certificate Generation Engine is completed.
                </p>
            </div>
        </div>
        <?php
    }
}