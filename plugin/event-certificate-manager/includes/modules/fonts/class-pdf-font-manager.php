<?php

/**
 * ECM PDF Font Manager
 *
 * Handles conversion of locally installed font files into
 * tc-lib-pdf compatible font assets.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_PDF_Font_Manager
{
    /**
     * Convert a TTF font for tc-lib-pdf.
     *
     * @param string $font_path Absolute path to the TTF file.
     * @param string $output_dir Absolute output directory.
     *
     * @return string|WP_Error
     */
    public static function import_font($font_path)
    {
        $output_dir = ECM_Font_Manager::get_pdf_fonts_directory();

        if (!$output_dir) {
            return new WP_Error(
                'ecm_pdf_font_directory_unavailable',
                'The ECM PDF font directory is unavailable.'
            );
        }

        if (!class_exists('\Com\Tecnick\Pdf\Font\Import')) {
            return new WP_Error(
                'ecm_pdf_font_import_unavailable',
                'The tc-lib-pdf font importer is unavailable.'
            );
        }

        $font_path = realpath($font_path);

        if (!$font_path || !is_file($font_path)) {
            return new WP_Error(
                'ecm_pdf_font_missing',
                'The PDF font source file does not exist.'
            );
        }

        if (!is_dir($output_dir)) {
            wp_mkdir_p($output_dir);
        }

        if (!is_writable($output_dir)) {
            return new WP_Error(
                'ecm_pdf_font_directory_not_writable',
                'The PDF font directory is not writable.'
            );
        }

        try {
            $import = new \Com\Tecnick\Pdf\Font\Import(
                $font_path,
                trailingslashit($output_dir),
                '',
                ''
            );

            return $import->getFontName();
        } catch (Throwable $exception) {
            return new WP_Error(
                'ecm_pdf_font_import_failed',
                $exception->getMessage()
            );
        }
    }

    /**
     * Prepare the ECM runtime PDF font directory.
     *
     * Copies tc-lib-pdf core font definitions into ECM's persistent
     * PDF font directory so built-in fonts and converted ECM fonts
     * can share one runtime font root.
     *
     * @return true|WP_Error
     */
    public static function initialize_runtime_fonts()
    {
        if (!class_exists('ECM_Font_Manager')) {
            return new WP_Error(
                'ecm_font_manager_missing',
                'The ECM Font Manager is unavailable.'
            );
        }

        $runtime_directory =
            ECM_Font_Manager::get_pdf_fonts_directory();

        if (!$runtime_directory) {
            return new WP_Error(
                'ecm_pdf_font_directory_unavailable',
                'The ECM PDF font directory is unavailable.'
            );
        }

        if (
            !is_dir($runtime_directory) &&
            !wp_mkdir_p($runtime_directory)
        ) {
            return new WP_Error(
                'ecm_pdf_font_directory_failed',
                'The ECM PDF font directory could not be created.'
            );
        }

        /*
     * Copy tc-lib-pdf's lightweight Core14 font definitions.
     *
     * These provide Helvetica, Times, Courier, Symbol,
     * and Zapf Dingbats as fallbacks.
     */
        $source_directory =
            ECM_PLUGIN_PATH
            . 'vendor/tecnickcom/tc-lib-pdf-font/target/fonts/core/';

        $target_directory =
            trailingslashit($runtime_directory) . 'core/';

        if (!is_dir($source_directory)) {
            return new WP_Error(
                'ecm_pdf_core_fonts_missing',
                'The tc-lib-pdf core font directory is unavailable.'
            );
        }

        if (
            !is_dir($target_directory) &&
            !wp_mkdir_p($target_directory)
        ) {
            return new WP_Error(
                'ecm_pdf_core_font_directory_failed',
                'The ECM core PDF font directory could not be created.'
            );
        }

        $files = glob($source_directory . '*');

        if (is_array($files)) {
            foreach ($files as $source_file) {
                if (!is_file($source_file)) {
                    continue;
                }

                $target_file =
                    $target_directory . basename($source_file);

                /*
             * Existing files do not need to be recopied.
             */
                if (is_file($target_file)) {
                    continue;
                }

                if (!copy($source_file, $target_file)) {
                    return new WP_Error(
                        'ecm_pdf_core_font_copy_failed',
                        sprintf(
                            'Could not prepare PDF font file: %s',
                            basename($source_file)
                        )
                    );
                }
            }
        }

        return true;
    }
}
