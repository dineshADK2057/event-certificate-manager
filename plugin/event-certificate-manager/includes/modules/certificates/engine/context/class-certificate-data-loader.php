<?php

/**
 * ECM Certificate Data Loader
 *
 * Validates one certificate-generation request and loads all
 * event, participant, session, template, metadata, and element
 * records required to create an ECM_Render_Context.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Certificate_Data_Loader
{
    /**
     * Load one complete certificate rendering context.
     *
     * Required request values:
     *
     * - event_id
     * - participant_id
     * - template_id
     *
     * Optional:
     *
     * - session_id
     * - force
     *
     * @param array $request Generation request.
     *
     * @return ECM_Render_Context|WP_Error
     */
    public static function load($request)
    {
        $request = self::normalize_request($request);

        if (is_wp_error($request)) {
            return $request;
        }

        global $wpdb;

        $event = self::load_event(
            $wpdb,
            $request['event_id']
        );

        if (is_wp_error($event)) {
            return $event;
        }

        $participant = self::load_participant(
            $wpdb,
            $request['participant_id'],
            $request['event_id']
        );

        if (is_wp_error($participant)) {
            return $participant;
        }

        $session = self::load_session(
            $wpdb,
            $request['session_id'],
            $request['event_id']
        );

        if (is_wp_error($session)) {
            return $session;
        }

        $template = self::load_template(
            $wpdb,
            $request['template_id'],
            $request['event_id'],
            $request['session_id']
        );

        if (is_wp_error($template)) {
            return $template;
        }

        $participant_meta = self::load_participant_meta(
            $wpdb,
            $request['participant_id']
        );

        $elements = self::load_template_elements(
            $wpdb,
            $request['template_id']
        );

        return new ECM_Render_Context(
            $request,
            $event,
            $participant,
            $participant_meta,
            $session,
            $template,
            $elements
        );
    }

    /**
     * Normalize and validate a generation request.
     *
     * @param array $request Raw request.
     *
     * @return array|WP_Error
     */
    private static function normalize_request($request)
    {
        if (!is_array($request)) {
            return new WP_Error(
                'ecm_invalid_generation_request',
                'The certificate generation request must be an array.'
            );
        }

        $normalized = [
            'event_id' => isset($request['event_id'])
                ? absint($request['event_id'])
                : 0,

            'participant_id' => isset($request['participant_id'])
                ? absint($request['participant_id'])
                : 0,

            'template_id' => isset($request['template_id'])
                ? absint($request['template_id'])
                : 0,

            'session_id' => !empty($request['session_id'])
                ? absint($request['session_id'])
                : null,

            'force' => !empty($request['force']),
        ];

        if (!$normalized['event_id']) {
            return new WP_Error(
                'ecm_generation_event_required',
                'A valid event ID is required.'
            );
        }

        if (!$normalized['participant_id']) {
            return new WP_Error(
                'ecm_generation_participant_required',
                'A valid participant ID is required.'
            );
        }

        if (!$normalized['template_id']) {
            return new WP_Error(
                'ecm_generation_template_required',
                'A valid template ID is required.'
            );
        }

        return $normalized;
    }

    /**
     * Load an event.
     *
     * @param wpdb $wpdb     WordPress database instance.
     * @param int  $event_id Event ID.
     *
     * @return object|WP_Error
     */
    private static function load_event($wpdb, $event_id)
    {
        $table = $wpdb->prefix . 'ecm_events';

        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$table}
                WHERE id = %d",
                $event_id
            )
        );

        if (!$event) {
            return new WP_Error(
                'ecm_generation_event_not_found',
                'The selected event could not be found.'
            );
        }

        return $event;
    }

    /**
     * Load a participant belonging to an event.
     *
     * @param wpdb $wpdb           WordPress database instance.
     * @param int  $participant_id Participant ID.
     * @param int  $event_id       Event ID.
     *
     * @return object|WP_Error
     */
    private static function load_participant(
        $wpdb,
        $participant_id,
        $event_id
    ) {
        $table = $wpdb->prefix . 'ecm_participants';

        $participant = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$table}
                WHERE id = %d
                AND event_id = %d",
                $participant_id,
                $event_id
            )
        );

        if (!$participant) {
            return new WP_Error(
                'ecm_generation_participant_not_found',
                'The selected participant does not belong to this event.'
            );
        }

        return $participant;
    }

    /**
     * Load an optional session belonging to an event.
     *
     * @param wpdb     $wpdb       WordPress database instance.
     * @param int|null $session_id Session ID.
     * @param int      $event_id   Event ID.
     *
     * @return object|null|WP_Error
     */
    private static function load_session(
        $wpdb,
        $session_id,
        $event_id
    ) {
        if (!$session_id) {
            return null;
        }

        $table = $wpdb->prefix . 'ecm_sessions';

        $session = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$table}
                WHERE id = %d
                AND event_id = %d",
                $session_id,
                $event_id
            )
        );

        if (!$session) {
            return new WP_Error(
                'ecm_generation_session_not_found',
                'The selected session does not belong to this event.'
            );
        }

        return $session;
    }

    /**
     * Load and validate a certificate template.
     *
     * @param wpdb     $wpdb        WordPress database instance.
     * @param int      $template_id Template ID.
     * @param int      $event_id    Event ID.
     * @param int|null $session_id  Optional session ID.
     *
     * @return object|WP_Error
     */
    private static function load_template(
        $wpdb,
        $template_id,
        $event_id,
        $session_id
    ) {
        $table = $wpdb->prefix . 'ecm_templates';

        $template = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$table}
                WHERE id = %d
                AND event_id = %d",
                $template_id,
                $event_id
            )
        );

        if (!$template) {
            return new WP_Error(
                'ecm_generation_template_not_found',
                'The selected template does not belong to this event.'
            );
        }

        $template_session_id = !empty($template->session_id)
            ? (int) $template->session_id
            : null;

        /*
         * A session-specific template must be used only with its
         * assigned session.
         */
        if (
            $template_session_id !== null &&
            $template_session_id !== (int) $session_id
        ) {
            return new WP_Error(
                'ecm_generation_template_session_mismatch',
                'The selected template does not belong to this session.'
            );
        }

        return $template;
    }

    /**
     * Load participant metadata indexed by metadata key.
     *
     * @param wpdb $wpdb           WordPress database instance.
     * @param int  $participant_id Participant ID.
     *
     * @return array
     */
    private static function load_participant_meta(
        $wpdb,
        $participant_id
    ) {
        $table = $wpdb->prefix . 'ecm_participant_meta';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_key, meta_value
                FROM {$table}
                WHERE participant_id = %d",
                $participant_id
            )
        );

        $metadata = [];

        foreach ($rows as $row) {
            $metadata[
                sanitize_key($row->meta_key)
            ] = $row->meta_value;
        }

        return $metadata;
    }

    /**
     * Load ordered template elements.
     *
     * @param wpdb $wpdb        WordPress database instance.
     * @param int  $template_id Template ID.
     *
     * @return array
     */
    private static function load_template_elements(
        $wpdb,
        $template_id
    ) {
        $table = $wpdb->prefix . 'ecm_template_elements';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$table}
                WHERE template_id = %d
                ORDER BY element_order ASC, id ASC",
                $template_id
            )
        );
    }
}