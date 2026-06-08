<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Helpers {

     private function get_event_fields($event_id)
    {
        global $wpdb;

        $fields_table = $wpdb->prefix . 'ecm_event_fields';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $fields_table WHERE event_id = %d ORDER BY field_order ASC, id ASC",
                $event_id
            )
        );
    }

}