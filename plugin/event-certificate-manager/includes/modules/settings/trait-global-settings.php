<?php

/**
 * ECM Global Settings
 *
 * Renders plugin-wide Event Certificate Manager settings.
 *
 * Event-specific participant fields and session settings remain
 * inside the Settings tab of each event.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Global_Settings
{
    /**
     * Render the global ECM Settings page.
     *
     * @return void
     */
    public function global_settings_page()
    {
        ?>
        <div class="wrap ecm-wrap">
            <div class="ecm-page-header">
                <div>
                    <h1>Settings</h1>

                    <p class="ecm-subtitle">
                        Configure global certificate, storage,
                        verification, email, and processing defaults.
                    </p>
                </div>
            </div>

            <div class="ecm-panel ecm-panel-full">
                <h2>Global Configuration</h2>

                <p>
                    Global PDF defaults, storage rules, sender
                    details, verification settings, batch limits,
                    retention settings, and diagnostics will be
                    configured here.
                </p>

                <p>
                    Event participant fields and session settings
                    remain inside each event’s Settings tab.
                </p>
            </div>
        </div>
        <?php
    }
}