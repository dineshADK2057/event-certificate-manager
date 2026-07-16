<?php

/**
 * ECM Event Settings
 *
 * Coordinates the Settings tab inside an event workspace.
 *
 * Participant-field management and session-specific settings are
 * delegated to dedicated Settings traits.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Settings
{
    /**
     * Render the Settings tab for one event.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function tab_settings($event)
    {
        ?>
        <div class="ecm-tab-header">
            <div>
                <h2>Event Settings</h2>

                <p>
                    Configure participant fields and event-specific rules.
                </p>
            </div>
        </div>

        <?php $this->render_event_settings_notices(); ?>

        <?php $this->render_participant_fields_section($event); ?>

        <?php $this->render_event_session_settings_section($event); ?>
        <?php
    }

    /**
     * Render Settings success notices.
     *
     * @return void
     */
    private function render_event_settings_notices()
    {
        $notices = [
            'fields_added' => 'Default participant fields added.',
            'field_added' => 'Custom participant field added.',
            'field_updated' => 'Participant field updated successfully.',
            'field_deleted' => 'Participant field deleted successfully.',
            'session_settings_saved' =>
                'Session settings saved successfully.',
        ];

        foreach ($notices as $query_key => $message) {
            if (!isset($_GET[$query_key])) {
                continue;
            }
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        <?php echo esc_html($message); ?>
                    </strong>
                </p>
            </div>
            <?php
        }
    }
}