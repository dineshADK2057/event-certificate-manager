<?php

/**
 * ECM Event Certificates
 *
 * Renders the Certificates tab inside an event workspace.
 *
 * The tab will later display generated certificates, certificate
 * status, downloads, regeneration actions, email delivery status,
 * and verification information.
 *
 * Certificate rendering itself belongs to the dedicated certificate
 * engine and must not be implemented directly inside this UI trait.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Certificates
{
    /**
     * Render the Certificates tab for one event.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function tab_certificates($event)
    {
        ?>
        <div class="ecm-tab-header">
            <div>
                <h2>Certificates</h2>

                <p>
                    Manage generated certificates for this event.
                </p>
            </div>
        </div>

        <div class="ecm-panel ecm-panel-full">
            <h3>Certificate Engine</h3>

            <p>
                Certificate generation is being developed in Sprint 06.
            </p>

            <ul class="ul-disc">
                <li>Generated certificate list</li>
                <li>Certificate ID management</li>
                <li>Certificate PDF downloads</li>
                <li>Certificate regeneration</li>
                <li>Email delivery and resend actions</li>
                <li>QR verification status</li>
            </ul>
        </div>
        <?php
    }
}