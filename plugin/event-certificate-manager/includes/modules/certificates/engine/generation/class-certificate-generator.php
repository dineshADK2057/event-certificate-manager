<?php

/**
 * ECM Certificate Generator
 *
 * Generates and persists one production certificate.
 *
 * This service coordinates the existing certificate rendering
 * pipeline, stores the generated PDF, and creates the corresponding
 * certificate database record.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Certificate_Generator
{
    /**
     * Generate and persist one certificate.
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
     *
     * @param array $request Certificate generation request.
     *
     * @return object|WP_Error Certificate database record on success.
     */
    public static function generate($request)
    {
        if (!class_exists('ECM_Certificate_Data_Loader')) {
            return new WP_Error(
                'ecm_generator_data_loader_missing',
                'The certificate data loader is unavailable.'
            );
        }

        if (!class_exists('ECM_PDF_Bootstrap')) {
            return new WP_Error(
                'ecm_generator_pdf_bootstrap_missing',
                'The PDF bootstrap is unavailable.'
            );
        }

        if (!class_exists('ECM_Certificate_Renderer')) {
            return new WP_Error(
                'ecm_generator_renderer_missing',
                'The certificate renderer is unavailable.'
            );
        }

        /*
         * Build and validate the complete rendering context.
         */
        $context = ECM_Certificate_Data_Loader::load(
            $request
        );

        if (is_wp_error($context)) {
            return $context;
        }

        if (!$context instanceof ECM_Render_Context) {
            return new WP_Error(
                'ecm_generator_invalid_context',
                'The certificate data loader returned an invalid render context.'
            );
        }

        $event =
            $context->get_event();

        $participant =
            $context->get_participant();

        $session =
            $context->get_session();

        $template =
            $context->get_template();

        /*
        * Session-specific certificates may only be generated
        * for participants assigned to that session.
        *
        * Event-wide certificates do not require a session
        * participant association.
        */
        if ($session) {
            $session_eligibility =
                self::validate_session_participant(
                    (int) $session->id,
                    (int) $participant->id
                );

            if (is_wp_error($session_eligibility)) {
                return $session_eligibility;
            }
        }

        /*
        * Prevent duplicate certificate records.
        *
        * New certificate generation must always create only one
        * certificate for the same event, participant, template,
        * and session identity.
        *
        * Existing certificates must be updated through regenerate()
        * rather than bypassing duplicate protection.
        */
        global $wpdb;

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        $existing_certificate =
            self::find_existing_certificate(
                $wpdb,
                $certificates_table,
                (int) $event->id,
                (int) $participant->id,
                (int) $template->id,
                $session
                    ? (int) $session->id
                    : null
            );

        if ($existing_certificate) {
            return new WP_Error(
                'ecm_certificate_already_exists',
                'A certificate has already been generated for this participant using this template.'
            );
        }

        /*
         * Generate a unique public certificate identifier.
         */
        $certificate_id =
            self::generate_certificate_id(
                $wpdb,
                $certificates_table
            );

        if (is_wp_error($certificate_id)) {
            return $certificate_id;
        }

        /*
         * Prepare the PDF document.
         */
        $pdf =
            ECM_PDF_Bootstrap::create_document();

        if (is_wp_error($pdf)) {
            return $pdf;
        }

        try {
            /*
             * Reuse the canonical certificate renderer.
             */
            $renderer =
                new ECM_Certificate_Renderer();

            $render_result =
                $renderer->render(
                    $pdf,
                    $context
                );

            if (is_wp_error($render_result)) {
                return $render_result;
            }

            /*
             * Obtain the completed PDF binary.
             */
            $raw_pdf =
                $pdf->getOutPDFString();

            if (
                !is_string($raw_pdf) ||
                $raw_pdf === ''
            ) {
                return new WP_Error(
                    'ecm_certificate_pdf_empty',
                    'The certificate renderer produced an empty PDF.'
                );
            }

            /*
             * Resolve the ECM generated-certificate directory.
             */
            $upload_dir =
                wp_upload_dir();

            if (!empty($upload_dir['error'])) {
                return new WP_Error(
                    'ecm_certificate_upload_directory_error',
                    $upload_dir['error']
                );
            }

            $generated_directory =
                trailingslashit(
                    $upload_dir['basedir']
                )
                . 'ecm/generated/';

            if (
                !is_dir($generated_directory) &&
                !wp_mkdir_p($generated_directory)
            ) {
                return new WP_Error(
                    'ecm_certificate_directory_creation_failed',
                    'The generated certificate directory could not be created.'
                );
            }

            /*
             * Keep the filename filesystem-safe while preserving
             * the public certificate identifier.
             */
            $filename =
                sanitize_file_name(
                    strtolower($certificate_id)
                        . '.pdf'
                );

            $absolute_path =
                $generated_directory
                . $filename;

            /*
             * Store a relative uploads path in the database so the
             * record remains portable between WordPress installations.
             */
            $relative_path =
                'ecm/generated/'
                . $filename;

            $written =
                file_put_contents(
                    $absolute_path,
                    $raw_pdf,
                    LOCK_EX
                );

            if ($written === false) {
                return new WP_Error(
                    'ecm_certificate_pdf_write_failed',
                    'The generated certificate PDF could not be saved.'
                );
            }

            /*
             * Resolve recipient snapshot values.
             *
             * Participant metadata is copied into the certificate
             * record so historical certificates remain identifiable
             * even if participant data changes later.
             */
            $recipient_name =
                trim(
                    (string)
                    $context->get_participant_meta_value(
                        'member_name',
                        ''
                    )
                );

            if ($recipient_name === '') {
                $recipient_name =
                    (string) $participant->member_id;
            }

            $recipient_email =
                trim(
                    (string)
                    $context->get_participant_meta_value(
                        'email',
                        ''
                    )
                );

            if (
                $recipient_email !== '' &&
                !is_email($recipient_email)
            ) {
                $recipient_email = '';
            }

            /*
             * Persist the certificate record only after the PDF
             * has been generated successfully.
             */
            $inserted =
                $wpdb->insert(
                    $certificates_table,
                    [
                        'certificate_id' =>
                        $certificate_id,

                        'event_id' =>
                        (int) $event->id,

                        'session_id' =>
                        $session
                            ? (int) $session->id
                            : null,

                        'participant_id' =>
                        (int) $participant->id,

                        'template_id' =>
                        (int) $template->id,

                        'recipient_name' =>
                        $recipient_name,

                        'recipient_email' =>
                        $recipient_email !== ''
                            ? $recipient_email
                            : null,

                        'pdf_file' =>
                        $relative_path,

                        'qr_file' =>
                        null,

                        'status' =>
                        'generated',

                        'generated_at' =>
                        current_time('mysql'),

                        'emailed_at' =>
                        null,

                        'verification_count' =>
                        0,
                    ],
                    [
                        '%s',
                        '%d',
                        '%d',
                        '%d',
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                    ]
                );

            if (!$inserted) {
                /*
                 * Avoid leaving an orphaned PDF when the database
                 * record could not be created.
                 */
                if (!$inserted) {
                    if (file_exists($absolute_path)) {
                        unlink($absolute_path);
                    }

                    error_log(
                        'ECM certificate insert error: '
                            . $wpdb->last_error
                    );

                    error_log(
                        'ECM certificate last query: '
                            . $wpdb->last_query
                    );

                    return new WP_Error(
                        'ecm_certificate_record_creation_failed',
                        'Certificate database insert failed: '
                            . $wpdb->last_error
                    );
                }
            }

            $certificate_record_id =
                (int) $wpdb->insert_id;

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
                    'ecm_certificate_record_unavailable',
                    'The generated certificate record could not be loaded.'
                );
            }

            return $certificate;
        } catch (Throwable $exception) {
            return new WP_Error(
                'ecm_certificate_generation_failed',
                $exception->getMessage()
            );
        }
    }

    /**
     * Validate that a participant is assigned to a session.
     *
     * @param int $session_id     Session ID.
     * @param int $participant_id Participant ID.
     *
     * @return true|WP_Error
     */
    private static function validate_session_participant(
        $session_id,
        $participant_id
    ) {
        global $wpdb;

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        $association_id =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                FROM {$session_participants_table}
                WHERE session_id = %d
                AND participant_id = %d
                LIMIT 1",
                    $session_id,
                    $participant_id
                )
            );

        if (!$association_id) {
            return new WP_Error(
                'ecm_certificate_session_participant_required',
                'The selected participant is not assigned to the session required by this certificate template.'
            );
        }

        return true;
    }

    /**
     * Regenerate an existing certificate.
     *
     * Regeneration preserves the existing certificate record and
     * public certificate ID while replacing the persisted PDF with
     * a freshly rendered version.
     *
     * @param int $certificate_record_id Internal certificate record ID.
     *
     * @return object|WP_Error Updated certificate record.
     */
    public static function regenerate($certificate_record_id)
    {
        global $wpdb;

        $certificate_record_id =
            absint($certificate_record_id);

        if (!$certificate_record_id) {
            return new WP_Error(
                'ecm_regeneration_invalid_certificate',
                'A valid certificate record is required.'
            );
        }

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        /*
     * Load the existing canonical certificate record.
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
                'ecm_regeneration_certificate_not_found',
                'The certificate could not be found.'
            );
        }

        /*
     * Rebuild the rendering context from the certificate's
     * authoritative relationships.
     */
        $context =
            ECM_Certificate_Data_Loader::load(
                [
                    'event_id' =>
                    (int) $certificate->event_id,

                    'participant_id' =>
                    (int) $certificate->participant_id,

                    'template_id' =>
                    (int) $certificate->template_id,

                    'session_id' =>
                    !empty($certificate->session_id)
                        ? (int) $certificate->session_id
                        : null,
                ]
            );

        if (is_wp_error($context)) {
            return $context;
        }

        if (!$context instanceof ECM_Render_Context) {
            return new WP_Error(
                'ecm_regeneration_invalid_context',
                'The certificate data loader returned an invalid render context.'
            );
        }

        $participant =
            $context->get_participant();

        $session =
            $context->get_session();

        /*
     * Revalidate session eligibility because participant/session
     * assignments may have changed since initial generation.
     */
        if ($session) {
            $session_eligibility =
                self::validate_session_participant(
                    (int) $session->id,
                    (int) $participant->id
                );

            if (is_wp_error($session_eligibility)) {
                return $session_eligibility;
            }
        }

        /*
     * Create a new PDF document and render the current certificate.
     */
        $pdf =
            ECM_PDF_Bootstrap::create_document();

        if (is_wp_error($pdf)) {
            return $pdf;
        }

        try {
            $renderer =
                new ECM_Certificate_Renderer();

            $render_result =
                $renderer->render(
                    $pdf,
                    $context
                );

            if (is_wp_error($render_result)) {
                return $render_result;
            }

            $raw_pdf =
                $pdf->getOutPDFString();

            if (
                !is_string($raw_pdf) ||
                $raw_pdf === ''
            ) {
                return new WP_Error(
                    'ecm_regeneration_empty_pdf',
                    'Certificate regeneration produced an empty PDF.'
                );
            }

            $upload_dir =
                wp_upload_dir();

            if (!empty($upload_dir['error'])) {
                return new WP_Error(
                    'ecm_regeneration_upload_error',
                    $upload_dir['error']
                );
            }

            /*
         * Preserve the existing PDF path whenever possible.
         */
            $relative_path =
                !empty($certificate->pdf_file)
                ? ltrim(
                    (string) $certificate->pdf_file,
                    '/'
                )
                : 'ecm/generated/'
                . sanitize_file_name(
                    strtolower(
                        $certificate->certificate_id
                    )
                        . '.pdf'
                );

            $absolute_path =
                trailingslashit(
                    $upload_dir['basedir']
                )
                . $relative_path;

            $directory =
                dirname($absolute_path);

            if (
                !is_dir($directory) &&
                !wp_mkdir_p($directory)
            ) {
                return new WP_Error(
                    'ecm_regeneration_directory_failed',
                    'The certificate directory could not be created.'
                );
            }

            /*
         * Write to a temporary file first.
         *
         * This prevents the existing valid certificate from being
         * destroyed if the new write fails midway.
         */
            $temporary_path =
                $absolute_path . '.tmp';

            $written =
                file_put_contents(
                    $temporary_path,
                    $raw_pdf,
                    LOCK_EX
                );

            if ($written === false) {
                return new WP_Error(
                    'ecm_regeneration_write_failed',
                    'The regenerated certificate PDF could not be saved.'
                );
            }

            /*
         * Atomically replace the existing PDF.
         */
            if (
                !rename(
                    $temporary_path,
                    $absolute_path
                )
            ) {
                if (file_exists($temporary_path)) {
                    unlink($temporary_path);
                }

                return new WP_Error(
                    'ecm_regeneration_replace_failed',
                    'The existing certificate PDF could not be replaced.'
                );
            }


            /*
            * Update only regeneration-related information.
            *
            * emailed_at and verification_count deliberately remain
            * untouched because they represent certificate history.
            */
            $updated =
                $wpdb->update(
                    $certificates_table,
                    [
                        'pdf_file' =>
                        $relative_path,

                        'status' =>
                        !empty($certificate->emailed_at)
                            ? 'emailed'
                            : 'generated',

                        'generated_at' =>
                        current_time('mysql'),
                    ],
                    [
                        'id' =>
                        $certificate_record_id,
                    ],
                    [
                        '%s',
                        '%s',
                        '%s',
                    ],
                    [
                        '%d',
                    ]
                );

            if ($updated === false) {
                return new WP_Error(
                    'ecm_regeneration_database_failed',
                    'The certificate PDF was regenerated, but the certificate record could not be updated.'
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
        } catch (Throwable $exception) {
            return new WP_Error(
                'ecm_certificate_regeneration_failed',
                $exception->getMessage()
            );
        }
    }

    /**
     * Find an existing certificate for the same generation identity.
     *
     * Event-wide certificates use a NULL session_id.
     *
     * @param wpdb        $wpdb
     * @param string      $table
     * @param int         $event_id
     * @param int         $participant_id
     * @param int         $template_id
     * @param int|null    $session_id
     *
     * @return object|null
     */
    private static function find_existing_certificate(
        $wpdb,
        $table,
        $event_id,
        $participant_id,
        $template_id,
        $session_id
    ) {
        if ($session_id) {
            return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$table}
                    WHERE event_id = %d
                    AND participant_id = %d
                    AND template_id = %d
                    AND session_id = %d
                    LIMIT 1",
                    $event_id,
                    $participant_id,
                    $template_id,
                    $session_id
                )
            );
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$table}
                WHERE event_id = %d
                AND participant_id = %d
                AND template_id = %d
                AND session_id IS NULL
                LIMIT 1",
                $event_id,
                $participant_id,
                $template_id
            )
        );
    }

    /**
     * Generate a unique public certificate ID.
     *
     * Example:
     *
     * ECM-A4F82C91D73B4E20
     *
     * @param wpdb   $wpdb
     * @param string $table
     *
     * @return string|WP_Error
     */
    private static function generate_certificate_id(
        $wpdb,
        $table
    ) {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $uuid =
                str_replace(
                    '-',
                    '',
                    wp_generate_uuid4()
                );

            $certificate_id =
                'ECM-'
                . strtoupper(
                    substr($uuid, 0, 16)
                );

            $exists =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id
                        FROM {$table}
                        WHERE certificate_id = %s
                        LIMIT 1",
                        $certificate_id
                    )
                );

            if (!$exists) {
                return $certificate_id;
            }
        }

        return new WP_Error(
            'ecm_certificate_id_generation_failed',
            'A unique certificate ID could not be generated.'
        );
    }
}
