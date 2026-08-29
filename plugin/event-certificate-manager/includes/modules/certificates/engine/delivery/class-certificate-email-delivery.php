<?php

/**
 * ECM Certificate Email Delivery
 *
 * Handles delivery of generated certificate PDFs by email.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Certificate_Email_Delivery
{
    /**
     * Send an existing generated certificate by email.
     *
     * This method does not regenerate the certificate.
     * It sends the currently persisted PDF.
     *
     * @param int $certificate_record_id Internal certificate record ID.
     *
     * @return object|WP_Error Updated certificate record.
     */
    public static function send($certificate_record_id)
    {
        global $wpdb;

        $certificate_record_id =
            absint($certificate_record_id);

        if (!$certificate_record_id) {
            return new WP_Error(
                'ecm_email_invalid_certificate',
                'A valid certificate record is required.'
            );
        }

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        /*
         * Load the canonical certificate record.
         */
        $certificate =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$certificates_table}
                    WHERE id = %d
                    LIMIT 1",
                    $certificate_record_id
                )
            );

        if (!$certificate) {
            return new WP_Error(
                'ecm_email_certificate_not_found',
                'The certificate could not be found.'
            );
        }

        /*
        * Resolve the recipient email.
        *
        * Prefer the email snapshot stored on the certificate.
        *
        * If the certificate was generated before the participant had
        * an email address, fall back to the participant's current email
        * metadata and synchronize that value back to the certificate.
        */
        $recipient_email =
            sanitize_email(
                (string) $certificate->recipient_email
            );

        if (
            !$recipient_email ||
            !is_email($recipient_email)
        ) {
            $participant_meta_table =
                $wpdb->prefix . 'ecm_participant_meta';

            $participant_email =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT meta_value
                FROM {$participant_meta_table}
                WHERE participant_id = %d
                AND meta_key = %s
                LIMIT 1",
                        (int) $certificate->participant_id,
                        'email'
                    )
                );

            $participant_email =
                sanitize_email(
                    (string) $participant_email
                );

            if (
                !$participant_email ||
                !is_email($participant_email)
            ) {
                return new WP_Error(
                    'ecm_email_recipient_missing',
                    'This certificate does not have a valid recipient email address.'
                );
            }

            $recipient_email =
                $participant_email;

            /*
            * Synchronize the newly discovered email into the certificate
            * snapshot so subsequent sends do not require another lookup.
            */
            $email_updated =
                $wpdb->update(
                    $certificates_table,
                    [
                        'recipient_email' =>
                        $recipient_email,
                    ],
                    [
                        'id' =>
                        $certificate_record_id,
                    ],
                    [
                        '%s',
                    ],
                    [
                        '%d',
                    ]
                );

            if ($email_updated === false) {
                return new WP_Error(
                    'ecm_email_snapshot_update_failed',
                    'The participant email was found, but the certificate recipient email could not be updated.'
                );
            }

            /*
     * Keep the in-memory certificate record synchronized too.
     */
            $certificate->recipient_email =
                $recipient_email;
        }

        /*
         * Resolve the persisted PDF.
         */
        if (empty($certificate->pdf_file)) {
            return new WP_Error(
                'ecm_email_pdf_missing',
                'This certificate does not have a generated PDF.'
            );
        }

        $upload_dir =
            wp_upload_dir();

        if (!empty($upload_dir['error'])) {
            return new WP_Error(
                'ecm_email_upload_error',
                $upload_dir['error']
            );
        }

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
            !is_file($absolute_pdf_path) ||
            !is_readable($absolute_pdf_path)
        ) {
            return new WP_Error(
                'ecm_email_pdf_not_found',
                'The generated certificate PDF could not be found.'
            );
        }

        /*
         * Build the initial delivery message.
         *
         * Later this will move into configurable email templates.
         */
        $recipient_name =
            !empty($certificate->recipient_name)
            ? (string) $certificate->recipient_name
            : 'Participant';

        $subject =
            sprintf(
                'Your Certificate - %s',
                $recipient_name
            );

        $message =
            sprintf(
                "Dear %s,\n\n"
                    . "Please find your certificate attached to this email.\n\n"
                    . "Certificate ID: %s\n\n"
                    . "Regards,\n"
                    . "%s",
                $recipient_name,
                (string) $certificate->certificate_id,
                wp_specialchars_decode(
                    get_bloginfo('name'),
                    ENT_QUOTES
                )
            );

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $attachments = [
            $absolute_pdf_path,
        ];

        /*
         * wp_mail() deliberately remains our application-level
         * mail API.
         *
         * SMTP/mail plugins can transport the message underneath
         * without coupling ECM to a specific provider.
         */
        $sent =
            wp_mail(
                $recipient_email,
                $subject,
                $message,
                $headers,
                $attachments
            );

        if (!$sent) {
            return new WP_Error(
                'ecm_email_delivery_failed',
                'WordPress could not send the certificate email.'
            );
        }

        /*
         * Mark the certificate as emailed only after wp_mail()
         * reports successful handoff.
         */
        $emailed_at =
            current_time('mysql');

        $updated =
            $wpdb->update(
                $certificates_table,
                [
                    'status' =>
                    'emailed',

                    'emailed_at' =>
                    $emailed_at,
                ],
                [
                    'id' =>
                    $certificate_record_id,
                ],
                [
                    '%s',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

        if ($updated === false) {
            return new WP_Error(
                'ecm_email_database_update_failed',
                'The email was sent, but the certificate delivery status could not be updated.'
            );
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$certificates_table}
                WHERE id = %d
                LIMIT 1",
                $certificate_record_id
            )
        );
    }
}
