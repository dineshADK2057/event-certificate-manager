<?php

/**
 * ECM Element Renderer
 *
 * Renders template elements onto the certificate.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Element_Renderer
{
    /**
     * Render all template elements.
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
        $elements = $context->get_elements();

        if (empty($elements)) {
            return true;
        }

        foreach ($elements as $element) {

            $element_type = isset($element->element_type)
                ? strtolower(trim($element->element_type))
                : 'text';

            switch ($element_type) {

                case 'text':

                    $renderer = new ECM_Text_Renderer();

                    $result = $renderer->render(
                        $pdf,
                        $element,
                        $context
                    );

                    if (is_wp_error($result)) {
                        return $result;
                    }

                    break;

                default:
                    /*
             * Unknown element type.
             * Ignore it for now.
             */
                    break;
            }
        }

        return true;
    }
}
