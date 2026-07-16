<?php

/**
 * ECM Event Helpers
 *
 * Provides reusable event-level helper methods shared across
 * Participants, Sessions, Templates, and Settings modules.
 *
 * This trait must contain only helpers that are genuinely required
 * by multiple event modules.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Helpers
{
    /**
     * Return participant fields configured for an event.
     *
     * Fields are returned in their configured display order.
     *
     * @param int $event_id Event ID.
     *
     * @return array
     */
    private function get_event_fields($event_id)
    {
        global $wpdb;

        $event_id = absint($event_id);

        if (!$event_id) {
            return [];
        }

        $fields_table =
            $wpdb->prefix . 'ecm_event_fields';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$fields_table}
                WHERE event_id = %d
                ORDER BY field_order ASC, id ASC",
                $event_id
            )
        );
    }
}