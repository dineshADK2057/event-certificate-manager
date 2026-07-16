<?php

/**
 * ECM Global Participants
 *
 * Renders the global participant directory across all events.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Global_Participants
{
    /**
     * Render the global Participants admin page.
     *
     * @return void
     */
    public function global_participants_page()
    {
        ?>
        <div class="wrap ecm-wrap">
            <div class="ecm-page-header">
                <div>
                    <h1>Participants</h1>

                    <p class="ecm-subtitle">
                        View participants registered across all events.
                    </p>
                </div>
            </div>

            <div class="ecm-panel ecm-panel-full">
                <h2>Global Participant Directory</h2>

                <p>
                    This page will provide cross-event participant
                    search, event filtering, session history, and
                    certificate status.
                </p>

                <p>
                    Participants are currently managed from the
                    Participants tab inside each event.
                </p>
            </div>
        </div>
        <?php
    }
}