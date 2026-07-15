<?php

/**
 * ECM Certificate PDF Test
 *
 * Temporary admin-only compatibility test for tc-lib-pdf.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Certificate_PDF_Test
{
    /**
     * Generate and output one basic test PDF.
     *
     * @return void
     */
    public function handle_pdf_compatibility_test()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You are not allowed to run this PDF test.',
                    'event-certificate-manager'
                ),
                esc_html__(
                    'Permission denied',
                    'event-certificate-manager'
                ),
                [
                    'response' => 403,
                ]
            );
        }

        check_admin_referer(
            'ecm_pdf_compatibility_test'
        );

        if (!class_exists('ECM_PDF_Bootstrap')) {
            wp_die(
                esc_html__(
                    'The ECM PDF bootstrap class is unavailable.',
                    'event-certificate-manager'
                )
            );
        }

        $pdf = ECM_PDF_Bootstrap::create_document();

        if (is_wp_error($pdf)) {
            wp_die(
                esc_html($pdf->get_error_message()),
                esc_html__(
                    'PDF initialization failed',
                    'event-certificate-manager'
                )
            );
        }

        try {
            /*
             * Insert the built-in Helvetica font.
             */
            $font = $pdf->font->insert(
                $pdf->pon,
                'helvetica',
                '',
                12
            );

            /*
             * Add one PDF page.
             */
            $pdf->addPage();

            /*
             * Register the font resource on the page.
             */
            $pdf->page->addContent(
                $font['out']
            );

            /*
             * Draw a basic HTML block.
             */
            $pdf->addHTMLCell(
                html: '<h1>ECM PDF Engine Working</h1>'
                    . '<p>Generated successfully with tc-lib-pdf.</p>',
                posx: 15,
                posy: 20,
                width: 180
            );

            /*
             * Build the final PDF binary.
             */
            $raw_pdf = $pdf->getOutPDFString();

            /*
             * Prevent WordPress or PHP output from corrupting the PDF.
             */
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $pdf->renderPDF($raw_pdf);
            exit;
        } catch (Throwable $exception) {
            wp_die(
                esc_html($exception->getMessage()),
                esc_html__(
                    'PDF generation failed',
                    'event-certificate-manager'
                )
            );
        }
    }
}