<?php

/**
 * Template Builder Variables
 *
 * Provides the dynamic placeholders available to certificate
 * templates in the visual Builder.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Builder_Variables
{
    private function get_template_variables($event, $template)
    {
        $variables = [
            'Participant Fields' => [],
            'Event Fields' => [
                '{event_name}',
                '{event_type}',
                '{event_venue}',
                '{event_start_date}',
                '{event_end_date}',
            ],
            'Session Fields' => [
                '{session_name}',
                '{session_code}',
                '{tutor_name}',
                '{session_date}',
            ],
            'System Fields' => [
                '{issue_date}',
                '{certificate_id}',
                '{qr_code}',
            ],
        ];

        $fields = $this->get_event_fields($event->id);

        foreach ($fields as $field) {
            $variables['Participant Fields'][] = '{' . $field->field_key . '}';
        }

        return $variables;
    }
}
