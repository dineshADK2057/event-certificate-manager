<?php

/**
 * ECM Certificate Renderer
 *
 * Coordinates the complete certificate rendering pipeline.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Certificate_Renderer
{
    /**
     * Render an entire certificate.
     *
     * @param \Com\Tecnick\Pdf\Tcpdf $pdf
     * @param ECM_Render_Context     $context
     *
     * @return true|WP_Error
     */
    public function render(
        $pdf,
        ECM_Render_Context $context
    ) {
        $background = new ECM_Background_Renderer(
            $context
        );

        $result = $background->render($pdf);

        if (is_wp_error($result)) {
            return $result;
        }

        $elements = new ECM_Element_Renderer();

        $result = $elements->render(
            $pdf,
            $context
        );

        if (is_wp_error($result)) {
            return $result;
        }

        return true;
    }
}