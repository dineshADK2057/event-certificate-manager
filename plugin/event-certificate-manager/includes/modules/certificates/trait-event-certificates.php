<?php

/**
 * ECM Event Certificates
 *
 * Renders and manages certificates belonging to one event.
 *
 * Certificate rendering itself remains inside the dedicated
 * certificate engine. This trait is responsible only for the
 * event-scoped certificate management interface.
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
        $page_data =
            $this->get_event_certificate_page_data(
                $event
            );

        $event_templates =
            $page_data['event_templates'];

        $event_participants =
            $page_data['event_participants'];

        $total_certificates =
            $page_data['total_certificates'];

        $generated_certificates =
            $page_data['generated_certificates'];

        $emailed_certificates =
            $page_data['emailed_certificates'];

        $certificate_participants =
            $page_data['certificate_participants'];

        $certificate_details =
            $page_data['certificate_details'];

?>


        
        <?php
        // notices
        $this->render_certificate_notices();

        // certificate generation ui
        $this->render_certificate_generation_ui(
            $event,
            $event_participants,
            $event_templates
        );

        // certificate stat ui

        $this->render_certificate_statistics_ui(
            $total_certificates,
            $generated_certificates,
            $emailed_certificates
        );

        // certificate generated lists ui

        $this->render_certificate_recipients_ui(
            $event,
            $certificate_participants,
            $certificate_details
        );

    }
}
