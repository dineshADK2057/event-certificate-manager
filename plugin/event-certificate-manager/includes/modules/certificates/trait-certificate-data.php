<?php

/**
 * ECM Certificate Data
 *
 * Prepares event-scoped certificate data used by the
 * Event Certificates interface.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Certificate_Data
{
    /**
     * Prepare certificate page data for an event.
     *
     * @param object $event Event record.
     *
     * @return array
     */
    private function get_event_certificate_page_data($event)
    {
        global $wpdb;

        $event_id = absint($event->id);

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        $templates_table =
            $wpdb->prefix . 'ecm_templates';

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $participant_meta_table =
            $wpdb->prefix . 'ecm_participant_meta';


        /*
        * Load templates available for this event.
        */
        $event_templates = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
            t.*,
            s.session_name

        FROM {$templates_table} t

        LEFT JOIN {$sessions_table} s
            ON s.id = t.session_id

        WHERE t.event_id = %d

        ORDER BY t.id DESC",
                $event->id
            )
        );

        /*
        * Load participants associated with this event.
        *
        * member_name is obtained from participant metadata for
        * administrator-friendly selection labels.
        */
        $event_participants = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
            p.id,
            p.member_id,
            name_meta.meta_value AS member_name

            FROM {$participants_table} p

            INNER JOIN {$event_participants_table} ep
                ON ep.participant_id = p.id

            LEFT JOIN {$participant_meta_table} name_meta
                ON name_meta.participant_id = p.id
                AND name_meta.meta_key = 'member_name'

            WHERE ep.event_id = %d

            ORDER BY
            name_meta.meta_value ASC,
            p.member_id ASC",
                $event->id
            )
        );



        /*
         * Event certificate statistics.
         */
        $total_certificates = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$certificates_table}
                WHERE event_id = %d",
                $event->id
            )
        );

        $generated_certificates = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$certificates_table}
                WHERE event_id = %d
                AND status = %s",
                $event->id,
                'generated'
            )
        );

        $emailed_certificates = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$certificates_table}
                WHERE event_id = %d
                AND emailed_at IS NOT NULL",
                $event->id
            )
        );

        /*
        * Load one participant-centric certificate summary row.
        *
        * Individual certificates remain independent database records.
        * The Event Certificates UI aggregates those records by participant
        * to avoid displaying duplicate participant rows.
        */
        $certificate_participants = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
            p.id AS participant_id,
            p.member_id,

            COALESCE(
                name_meta.meta_value,
                MAX(c.recipient_name)
            ) AS member_name,

            club_meta.meta_value AS home_club,

            COALESCE(
                email_meta.meta_value,
                MAX(c.recipient_email)
            ) AS email,

            COUNT(c.id) AS certificate_count,

            GROUP_CONCAT(
                DISTINCT
                CASE
                    WHEN c.session_id IS NULL
                        THEN 'Event-wide'
                    ELSE s.session_name
                END
                ORDER BY
                    CASE
                        WHEN c.session_id IS NULL THEN 0
                        ELSE 1
                    END,
                    s.session_name ASC
                SEPARATOR ', '
            ) AS certificate_scopes,

            MAX(
                CASE
                    WHEN c.emailed_at IS NOT NULL THEN 1
                    ELSE 0
                END
            ) AS has_emailed,

            MAX(
                CASE
                    WHEN c.verification_count > 0 THEN 1
                    ELSE 0
                END
            ) AS has_verified

            FROM {$certificates_table} c

            INNER JOIN {$participants_table} p
                ON p.id = c.participant_id

            LEFT JOIN {$sessions_table} s
                ON s.id = c.session_id

            LEFT JOIN {$participant_meta_table} name_meta
                ON name_meta.participant_id = p.id
                AND name_meta.meta_key = 'member_name'

            LEFT JOIN {$participant_meta_table} club_meta
                ON club_meta.participant_id = p.id
                AND club_meta.meta_key = 'home_club'

            LEFT JOIN {$participant_meta_table} email_meta
                ON email_meta.participant_id = p.id
                AND email_meta.meta_key = 'email'

            WHERE c.event_id = %d

            GROUP BY
                p.id,
                p.member_id,
                name_meta.meta_value,
                club_meta.meta_value,
                email_meta.meta_value

            ORDER BY
                name_meta.meta_value ASC,
                p.member_id ASC",
                $event->id
            )
        );



        /*
        * Load the individual certificate records used by the
        * participant certificate-detail modal.
        */
        $individual_certificates = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
            c.*,
            s.session_name

            FROM {$certificates_table} c

            LEFT JOIN {$sessions_table} s
                ON s.id = c.session_id

            WHERE c.event_id = %d

            ORDER BY
                c.participant_id ASC,
                c.id DESC",
                $event->id
            )
        );

        /*
        * Build participant-indexed certificate data for the
        * client-side certificate detail viewer.
        */
        $certificate_details = [];

        $upload_dir =
            wp_upload_dir();

        foreach ($individual_certificates as $certificate) {

            $participant_id =
                (int) $certificate->participant_id;

            if (
                !isset(
                    $certificate_details[$participant_id]
                )
            ) {
                $certificate_details[$participant_id] = [];
            }

            $pdf_url = '';

            if (
                !empty($certificate->pdf_file) &&
                empty($upload_dir['error'])
            ) {
                $relative_pdf_path =
                    ltrim(
                        (string) $certificate->pdf_file,
                        '/'
                    );

                $absolute_pdf_path =
                    trailingslashit(
                        $upload_dir['basedir']
                    )
                    . $relative_pdf_path;

                if (
                    is_file($absolute_pdf_path) &&
                    is_readable($absolute_pdf_path)
                ) {
                    $pdf_url =
                        trailingslashit(
                            $upload_dir['baseurl']
                        )
                        . $relative_pdf_path;
                }
            }

            $certificate_details[$participant_id][] = [
                'id' =>
                (int) $certificate->id,

                'certificate_id' =>
                (string) $certificate->certificate_id,

                'scope' =>
                !empty($certificate->session_name)
                    ? (string) $certificate->session_name
                    : 'Event-wide',

                'status' =>
                !empty($certificate->status)
                    ? (string) $certificate->status
                    : 'generated',

                'generated_at' =>
                !empty($certificate->generated_at)
                    ? (string) $certificate->generated_at
                    : '',

                'emailed_at' =>
                !empty($certificate->emailed_at)
                    ? (string) $certificate->emailed_at
                    : '',

                'verification_count' =>
                (int) $certificate->verification_count,

                'pdf_url' =>
                $pdf_url,

                // 'regenerate_url' =>
                // wp_nonce_url(
                //     admin_url(
                //         'admin-post.php'
                //             . '?action=ecm_regenerate_event_certificate'
                //             . '&certificate_id='
                //             . (int) $certificate->id
                //             . '&event_id='
                //             . (int) $certificate->event_id
                //     ),
                //     'ecm_regenerate_certificate_'
                //         . (int) $certificate->id,
                //     'ecm_regenerate_certificate_nonce'
                // ),

                'record_id' =>
                (int) $certificate->id,

                'regenerate_nonce' =>
                wp_create_nonce(
                    'ecm_regenerate_certificate_'
                        . (int) $certificate->id
                ),

                'email_nonce' =>
                wp_create_nonce(
                    'ecm_send_certificate_email_'
                        . (int) $certificate->id
                ),
            ];
        }



        return [
            'event_templates' =>
            $event_templates,

            'event_participants' =>
            $event_participants,

            'total_certificates' =>
            $total_certificates,

            'generated_certificates' =>
            $generated_certificates,

            'emailed_certificates' =>
            $emailed_certificates,

            'certificate_participants' =>
            $certificate_participants,

            'certificate_details' =>
            $certificate_details,
        ];
    }
}
