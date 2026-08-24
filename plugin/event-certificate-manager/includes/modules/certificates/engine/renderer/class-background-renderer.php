<?php

/**
 * Certificate Background Renderer
 *
 * Resolves and validates the background assigned to a certificate template.
 *
 * @package EventCertificateManager
 */

defined('ABSPATH') || exit;

class ECM_Background_Renderer
{
    /**
     * Certificate render context.
     *
     * @var ECM_Render_Context
     */
    private $context;

    /**
     * Constructor.
     *
     * @param ECM_Render_Context $context Render context.
     */
    public function __construct(ECM_Render_Context $context)
    {
        $this->context = $context;
    }

    /**
     * Resolve the template background file.
     *
     * @return string|WP_Error Absolute background file path or error.
     */
    public function resolve_background_path()
    {
        $template = $this->context->get_template();

        if (!is_object($template)) {
            return new WP_Error(
                'ecm_background_template_missing',
                'The certificate template could not be loaded.'
            );
        }

        $background_value = $this->get_background_value($template);

        if ($background_value === '') {
            return new WP_Error(
                'ecm_background_not_assigned',
                'No background has been assigned to this certificate template.'
            );
        }

        $background_path = $this->convert_to_absolute_path(
            $background_value
        );

        if ($background_path === '') {
            return new WP_Error(
                'ecm_background_path_invalid',
                'The certificate background path could not be resolved.'
            );
        }

        if (!file_exists($background_path)) {
            return new WP_Error(
                'ecm_background_file_missing',
                sprintf(
                    'The certificate background file does not exist: %s',
                    $background_path
                )
            );
        }

        if (!is_readable($background_path)) {
            return new WP_Error(
                'ecm_background_file_unreadable',
                'The certificate background file is not readable.'
            );
        }

        if (!$this->is_supported_background($background_path)) {
            return new WP_Error(
                'ecm_background_type_unsupported',
                'The certificate background must be a PDF, PNG, JPEG or WEBP file.'
            );
        }

        return $background_path;
    }

    /**
     * Find the background value stored on the template record.
     *
     * Supports the possible database property names used by ECM.
     *
     * @param object $template Template database record.
     *
     * @return string
     */
    private function get_background_value($template)
    {
        $properties = [
            'background_path',
            'background_file',
            'background_url',
            'background',
        ];

        foreach ($properties as $property) {
            if (
                isset($template->{$property}) &&
                trim((string) $template->{$property}) !== ''
            ) {
                return trim((string) $template->{$property});
            }
        }

        return '';
    }

    /**
     * Convert a stored URL or relative upload path into an absolute path.
     *
     * @param string $background_value Stored background value.
     *
     * @return string
     */
    private function convert_to_absolute_path($background_value)
    {
        $background_value = trim($background_value);

        if ($background_value === '') {
            return '';
        }

        /*
         * Already an absolute local file path.
         */
        if (
            strpos($background_value, ABSPATH) === 0 ||
            strpos($background_value, WP_CONTENT_DIR) === 0
        ) {
            return wp_normalize_path($background_value);
        }

        $uploads = wp_upload_dir();

        if (!empty($uploads['error'])) {
            return '';
        }

        $base_url = untrailingslashit($uploads['baseurl']);
        $base_dir = untrailingslashit($uploads['basedir']);

        /*
         * Upload URL stored in the database.
         */
        if (strpos($background_value, $base_url) === 0) {
            $relative_path = substr(
                $background_value,
                strlen($base_url)
            );

            return wp_normalize_path(
                $base_dir . '/' . ltrim($relative_path, '/')
            );
        }

        /*
         * Relative uploads path stored in the database.
         */
        if (
            strpos($background_value, '/wp-content/uploads/') !== false
        ) {
            $relative_path = strstr(
                $background_value,
                '/wp-content/uploads/'
            );

            return wp_normalize_path(
                ABSPATH . ltrim($relative_path, '/')
            );
        }

        /*
         * Plain relative path such as:
         * ecm/templates/background.pdf
         */
        return wp_normalize_path(
            $base_dir . '/' . ltrim($background_value, '/')
        );
    }

    /**
     * Determine whether the background file type is supported.
     *
     * @param string $background_path Absolute file path.
     *
     * @return bool
     */
    private function is_supported_background($background_path)
    {
        $extension = strtolower(
            pathinfo($background_path, PATHINFO_EXTENSION)
        );

        return in_array(
            $extension,
            [
                'pdf',
                'png',
                'jpg',
                'jpeg',
                'webp',
            ],
            true
        );
    }

    /**
     * Get the render context.
     *
     * @return ECM_Render_Context
     */
    public function get_context()
    {
        return $this->context;
    }


    /**
     * Render the validated certificate background.
     *
     * Currently this is only a placeholder.
     * The actual rendering logic will be added
     * in the next step.
     *
     * @param Com\Tecnick\Pdf\Tcpdf $pdf PDF document.
     *
     * @return true|WP_Error
     */
    /**
     * Render the validated certificate background into the PDF.
     *
     * @param \Com\Tecnick\Pdf\Tcpdf $pdf PDF document.
     *
     * @return true|WP_Error
     */
    public function render($pdf)
    {
        if (!($pdf instanceof \Com\Tecnick\Pdf\Tcpdf)) {
            return new WP_Error(
                'ecm_invalid_pdf_document',
                'A valid tc-lib-pdf document is required.'
            );
        }

        $background_path = $this->resolve_background_path();

        if (is_wp_error($background_path)) {
            return $background_path;
        }

        try {
            $source_id = $pdf->setImportSourceFile(
                $background_path
            );

            $page_count = $pdf->getSourcePageCount(
                $source_id
            );

            if ($page_count < 1) {
                return new WP_Error(
                    'ecm_background_pdf_empty',
                    'The certificate background PDF contains no pages.'
                );
            }

            $pdf->addPageFromImport(
                $source_id,
                1
            );

            return true;
        } catch (Throwable $exception) {
            return new WP_Error(
                'ecm_background_render_failed',
                $exception->getMessage()
            );
        }
    }
}
