<?php

/**
 * ECM PDF Bootstrap
 *
 * Validates and creates tc-lib-pdf document instances.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_PDF_Bootstrap
{
    /**
     * Return the generated tc-lib-pdf fonts directory.
     *
     * @return string
     */
    public static function get_fonts_directory()
    {
        return ECM_PLUGIN_PATH
            . 'vendor/tecnickcom/tc-lib-pdf-font/target/fonts';
    }

    /**
     * Validate the tc-lib-pdf installation.
     *
     * @return true|WP_Error
     */
    public static function validate()
    {
        if (!class_exists('\Com\Tecnick\Pdf\Tcpdf')) {
            return new WP_Error(
                'ecm_pdf_library_missing',
                'tc-lib-pdf is not installed or Composer was not loaded.'
            );
        }

        $fonts_directory = self::get_fonts_directory();

        if (!is_dir($fonts_directory)) {
            return new WP_Error(
                'ecm_pdf_fonts_missing',
                'The generated tc-lib-pdf fonts directory is missing.'
            );
        }

        if (!is_readable($fonts_directory)) {
            return new WP_Error(
                'ecm_pdf_fonts_unreadable',
                'The generated tc-lib-pdf fonts directory is not readable.'
            );
        }

        return true;
    }

    /**
     * Create a configured tc-lib-pdf document.
     *
     * @return \Com\Tecnick\Pdf\Tcpdf|WP_Error
     */
    public static function create_document()
    {
        $validation = self::validate();

        if (is_wp_error($validation)) {
            return $validation;
        }

        /*
         * tc-lib-pdf reads prepared font definitions from this path.
         */
        if (!defined('K_PATH_FONTS')) {
            $fonts_directory = realpath(
                self::get_fonts_directory()
            );

            if (!$fonts_directory) {
                return new WP_Error(
                    'ecm_pdf_fonts_path_invalid',
                    'The tc-lib-pdf fonts path could not be resolved.'
                );
            }

            define(
                'K_PATH_FONTS',
                $fonts_directory
            );
        }

        try {
            $pdf = new \Com\Tecnick\Pdf\Tcpdf();

            $pdf->setCreator('Event Certificate Manager');
            $pdf->setAuthor(
                wp_strip_all_tags(get_bloginfo('name'))
            );
            $pdf->setTitle('ECM PDF Compatibility Test');
            $pdf->setSubject('tc-lib-pdf compatibility test');
            $pdf->setKeywords(
                'ECM, certificate, PDF, tc-lib-pdf'
            );
            $pdf->setPDFFilename(
                'ecm-pdf-engine-test.pdf'
            );

            return $pdf;
        } catch (Throwable $exception) {
            return new WP_Error(
                'ecm_pdf_initialization_failed',
                $exception->getMessage()
            );
        }
    }
}