<?php

/**
 * ECM Certificate PDF Test
 *
 * Provides a temporary administrator-only compatibility test for
 * the tc-lib-pdf installation.
 *
 * This trait will be removed after the PDF bootstrap has been
 * validated successfully.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Certificate_PDF_Test
{
    /**
     * Generate and stream one basic compatibility PDF.
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
                ),
                esc_html__(
                    'PDF initialization failed',
                    'event-certificate-manager'
                )
            );
        }

        $pdf = ECM_PDF_Bootstrap::create_document(
            [
                'filename' => 'ecm-pdf-engine-test.pdf',
                'title'    => 'ECM PDF Compatibility Test',
                'subject'  => 'tc-lib-pdf compatibility test',
            ]
        );

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
             * Register a built-in PDF font.
             */
            $font = $pdf->font->insert(
                $pdf->pon,
                'helvetica',
                '',
                12
            );

            /*
             * Create the first document page.
             */
            $pdf->addPage();

            /*
             * Register the font resource for the active page.
             */
            $pdf->page->addContent(
                $font['out']
            );

            /*
             * Render basic compatibility-test content.
             */
            $pdf->addHTMLCell(
                html:
                    '<h1>ECM PDF Engine Working</h1>'
                    . '<p>tc-lib-pdf loaded successfully inside WordPress.</p>'
                    . '<p>Generated font assets were detected and loaded.</p>',
                posx: 15,
                posy: 20,
                width: 180
            );

            /*
             * Build the raw PDF binary.
             */
            $raw_pdf = $pdf->getOutPDFString();

            /*
             * Remove any WordPress or PHP output that could corrupt
             * the PDF response.
             */
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            /*
             * Stream the completed PDF to the browser.
             */
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