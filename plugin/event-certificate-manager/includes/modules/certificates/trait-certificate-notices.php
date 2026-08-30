<?php

/**
 * ECM Certificate Notices
 *
 * Renders success, warning, and error notices for
 * event-scoped certificate operations.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Certificate_Notices
{
    /**
     * Render certificate operation notices.
     *
     * @return void
     */
    private function render_certificate_notices()
    {
        ?>
        <!-- notice if certificate is generated successfully -->

        <?php if (isset($_GET['certificate_generated'])) : ?>

            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        Certificate generated successfully.
                    </strong>

                    <?php if (!empty($_GET['certificate_id'])) : ?>
                        Certificate ID:
                        <code>
                            <?php
                            echo esc_html(
                                sanitize_text_field(
                                    wp_unslash(
                                        $_GET['certificate_id']
                                    )
                                )
                            );
                            ?>
                        </code>
                    <?php endif; ?>
                </p>
            </div>

        <?php endif; ?>

        <!-- certificate generation error notices -->

        <?php if (
            isset($_GET['certificate_generation_error'])
        ) : ?>

            <?php
            $generation_error =
                sanitize_key(
                    wp_unslash(
                        $_GET['certificate_generation_error']
                    )
                );

            $generation_error_messages = [
                'ecm_certificate_already_exists' =>
                'This participant already has a certificate generated with the selected template.',

                'ecm_generation_participant_not_found' =>
                'The selected participant does not belong to this event.',

                'ecm_generation_template_not_found' =>
                'The selected template could not be loaded.',

                'ecm_certificate_pdf_write_failed' =>
                'The certificate was rendered but could not be saved.',

                'ecm_certificate_record_creation_failed' =>
                'The PDF was generated, but the certificate database record could not be created.',

                'ecm_certificate_generation_failed' =>
                'Certificate generation failed unexpectedly.',

                'ecm_certificate_session_participant_required' =>
                'This participant is not assigned to the session required by the selected certificate template.',
            ];

            $generation_error_message =
                isset(
                    $generation_error_messages[$generation_error]
                )
                ? $generation_error_messages[$generation_error]
                : 'The certificate could not be generated.';
            ?>

            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>
                        <?php
                        echo esc_html(
                            $generation_error_message
                        );
                        ?>
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <!-- notice after certificate regenerated successfully -->
        <?php if (isset($_GET['certificate_regenerated'])
        ) : ?>

            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        Certificate regenerated successfully.
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <!-- notice if regeneration failed -->

        <?php if (
            isset($_GET['certificate_regeneration_error'])
        ) : ?>

            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>
                        The certificate could not be regenerated.
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <!-- notice if certificate is mailed -->


        <?php if (
            isset($_GET['certificate_emailed'])
        ) : ?>

            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        Certificate email sent successfully.
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <!-- certificate email failed notices -->

        <?php if (
            isset($_GET['certificate_email_error'])
        ) : ?>

            <?php
            $email_error =
                sanitize_key(
                    wp_unslash(
                        $_GET['certificate_email_error']
                    )
                );

            $email_error_messages = [
                'ecm_email_recipient_missing' =>
                'This certificate does not have a valid recipient email address.',

                'ecm_email_pdf_missing' =>
                'This certificate does not have a generated PDF.',

                'ecm_email_pdf_not_found' =>
                'The generated certificate PDF could not be found.',

                'ecm_email_delivery_failed' =>
                'WordPress could not send the certificate email.',

                'ecm_email_snapshot_update_failed' =>
                'The participant email was found, but the certificate recipient email could not be updated.',

                'ecm_email_database_update_failed' =>
                'The email was sent, but the delivery status could not be updated.',
            ];

            $email_error_message =
                isset(
                    $email_error_messages[$email_error]
                )
                ? $email_error_messages[$email_error]
                : 'The certificate email could not be sent.';
            ?>

            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>
                        <?php
                        echo esc_html(
                            $email_error_message
                        );
                        ?>
                    </strong>
                </p>
            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET['bulk_certificates_regenerated'])
        ) : ?>

            <?php
            $bulk_regenerated =
                absint(
                    $_GET['bulk_certificates_regenerated']
                );

            $bulk_failed =
                isset($_GET['bulk_certificates_failed'])
                ? absint(
                    $_GET['bulk_certificates_failed']
                )
                : 0;
            ?>

            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        <?php
                        echo esc_html(
                            sprintf(
                                '%d certificate(s) regenerated successfully.',
                                $bulk_regenerated
                            )
                        );
                        ?>
                    </strong>

                    <?php if ($bulk_failed > 0) : ?>

                        <?php
                        echo esc_html(
                            sprintf(
                                ' %d certificate(s) could not be regenerated.',
                                $bulk_failed
                            )
                        );
                        ?>

                    <?php endif; ?>
                </p>
            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET['bulk_certificate_no_selection'])
        ) : ?>

            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>
                        Select at least one certificate recipient.
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET['bulk_certificate_none_found'])
        ) : ?>

            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>
                        No certificates were found for the selected recipients.
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET['bulk_certificate_emails_sent'])
        ) : ?>

            <?php
            $bulk_sent =
                absint(
                    $_GET['bulk_certificate_emails_sent']
                );

            $bulk_failed =
                isset($_GET['bulk_certificate_emails_failed'])
                ? absint(
                    $_GET['bulk_certificate_emails_failed']
                )
                : 0;
            ?>

            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        <?php
                        echo esc_html(
                            sprintf(
                                '%d certificate email(s) sent successfully.',
                                $bulk_sent
                            )
                        );
                        ?>
                    </strong>

                    <?php if ($bulk_failed > 0) : ?>

                        <?php
                        echo esc_html(
                            sprintf(
                                ' %d certificate email(s) failed.',
                                $bulk_failed
                            )
                        );
                        ?>

                    <?php endif; ?>
                </p>
            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET['bulk_certificate_email_no_selection'])
        ) : ?>

            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>
                        Select at least one certificate recipient.
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET['bulk_certificate_email_none_found'])
        ) : ?>

            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>
                        No certificates were found for the selected recipients.
                    </strong>
                </p>
            </div>

        <?php endif; ?>
        
        
        
        <?php
    }
}