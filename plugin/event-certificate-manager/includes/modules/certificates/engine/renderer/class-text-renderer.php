<?php

/**
 * ECM Text Renderer
 *
 * Renders text elements onto the certificate.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Text_Renderer
{
    /**
     * Render a text element.
     *
     * @param \Com\Tecnick\Pdf\Tcpdf $pdf     PDF document.
     * @param object                 $element Template element record.
     * @param ECM_Render_Context     $context Render context.
     *
     * @return true|WP_Error
     */
    public function render(
        $pdf,
        $element,
        ECM_Render_Context $context
    ) {
        /*
         * Resolve the final text value.
         *
         * Static text takes priority. If no static text exists,
         * resolve the configured dynamic placeholder.
         */
        $text = $this->resolve_text(
            $element,
            $context
        );

        if ($text === '') {
            return true;
        }

        /*
         * Resolve the PDF font.
         */
        $font_family = $this->resolve_pdf_font(
            $element
        );

        $font_style = !empty($element->font_style)
            ? strtoupper(trim((string) $element->font_style))
            : '';

        $font_size = !empty($element->font_size)
            ? (float) $element->font_size
            : 12;

        try {
            /*
             * Register the selected font with tc-lib-pdf.
             */
            $font = $pdf->font->insert(
                $pdf->pon,
                $font_family,
                $font_style,
                $font_size
            );

            $pdf->page->addContent(
                $font['out']
            );


            /*
            * Apply the configured text color.
            */
            $font_color = !empty($element->font_color)
                ? trim((string) $element->font_color)
                : '#000000';

            try {
                $color_command = $pdf->color->getPdfFillColor(
                    $font_color
                );

                $pdf->page->addContent(
                    $color_command
                );
            } catch (Throwable $exception) {
                /*
                * Invalid or unsupported colors fall back to black.
                */
                $pdf->page->addContent(
                    $pdf->color->getPdfFillColor(
                        '#000000'
                    )
                );
            }

            /*
             * Template Builder coordinates are stored in CSS pixels.
             * tc-lib-pdf currently uses millimetres.
             *
             * 96 CSS pixels = 25.4 millimetres.
             */
            $pixel_to_mm = 25.4 / 96;

            $x = isset($element->x_position)
                ? (float) $element->x_position * $pixel_to_mm
                : 0;

            $y = isset($element->y_position)
                ? (float) $element->y_position * $pixel_to_mm
                : 0;

            $width = !empty($element->width)
                ? (float) $element->width * $pixel_to_mm
                : 180;

            $height = !empty($element->height)
                ? (float) $element->height * $pixel_to_mm
                : 20;

            /*
             * Convert Builder alignment values:
             *
             * left   -> L
             * center -> C
             * right  -> R
             */
            $alignment = !empty($element->alignment)
                ? strtoupper(
                    substr(
                        trim((string) $element->alignment),
                        0,
                        1
                    )
                )
                : 'L';

            if (
                !in_array(
                    $alignment,
                    ['L', 'C', 'R', 'J'],
                    true
                )
            ) {
                $alignment = 'L';
            }

            /*
             * Render the text using absolute positioning.
             */
            $pid = (int) $pdf->page->getPageID();

            /*
            * Apply element rotation around the center of its bounding box.
            */
            $rotation = isset($element->rotation)
                ? (float) $element->rotation
                : 0;

            if ($rotation != 0) {
                $rotation_center_x = $x + ($width / 2);
                $rotation_center_y = $y + ($height / 2);

                $pdf->page->addContent(
                    $pdf->graph->getStartTransform(),
                    $pid
                );

                $pdf->page->addContent(
                    $pdf->graph->getRotation(
                        -$rotation,
                        $rotation_center_x,
                        $rotation_center_y
                    ),
                    $pid
                );
            }

            $pdf->addTextCell(
                $text,
                $pid,
                $x,
                $y,
                $width,
                $height,
                0,
                0,
                'T',
                $alignment,
                null,
                [],
                0,
                0,
                0,
                0,
                true,
                true,
                false,
                false,
                false,
                false,
                false,
                false
            );

            /*
            * Close the transformation so rotation does not affect
            * any following certificate elements.
            */
            if ($rotation != 0) {
                $pdf->page->addContent(
                    $pdf->graph->getStopTransform(),
                    $pid
                );
            }

            return true;
        } catch (Throwable $exception) {
            return new WP_Error(
                'ecm_text_render_failed',
                $exception->getMessage()
            );
        }
    }

    /**
     * Resolve the final text value for one element.
     *
     * @param object             $element Template element record.
     * @param ECM_Render_Context $context Render context.
     *
     * @return string
     */
    private function resolve_text(
        $element,
        ECM_Render_Context $context
    ) {
        if (
            isset($element->text_value) &&
            trim((string) $element->text_value) !== ''
        ) {
            return (string) $element->text_value;
        }

        if (
            empty($element->placeholder_key) ||
            !class_exists('ECM_Placeholder_Resolver')
        ) {
            return '';
        }

        $resolver = new ECM_Placeholder_Resolver();

        return (string) $resolver->resolve(
            $element->placeholder_key,
            isset($element->source_type)
                ? $element->source_type
                : 'participant',
            $context
        );
    }

    /**
     * Resolve the tc-lib-pdf font name for one element.
     *
     * If a selected Google font has not yet been prepared for PDF use,
     * ECM prepares and caches its tc-lib-pdf assets automatically.
     *
     * @param object $element Template element record.
     *
     * @return string
     */
    private function resolve_pdf_font($element)
    {
        /*
     * Safe built-in fallback.
     */
        $fallback_font = 'helvetica';

        if (
            empty($element->font_family) ||
            !class_exists('ECM_Font_Manager')
        ) {
            return $fallback_font;
        }

        $font_family = trim(
            (string) $element->font_family
        );

        $font_slug = sanitize_title(
            $font_family
        );

        if ($font_slug === '') {
            return $fallback_font;
        }

        /*
     * Read the current font definition from the ECM manifest.
     */
        $font_definition = ECM_Font_Manager::get_font(
            $font_slug
        );

        if (!$font_definition) {
            return $fallback_font;
        }

        /*
     * If PDF assets already exist, use them immediately.
     */
        if (
            !empty($font_definition['files']) &&
            is_array($font_definition['files']) &&
            !empty($font_definition['files']['pdf-font-name'])
        ) {
            return sanitize_key(
                $font_definition['files']['pdf-font-name']
            );
        }

        /*
     * Automatically prepare an installed Google Font
     * when its PDF assets are still missing.
     */
        if (
            ($font_definition['source'] ?? '') === 'google' &&
            class_exists('ECM_Google_Fonts')
        ) {
            $prepared_font = ECM_Google_Fonts::prepare_pdf_font(
                $font_family
            );

            if (!is_wp_error($prepared_font)) {
                return sanitize_key(
                    $prepared_font
                );
            }
        }

        /*
     * Unsupported or unavailable fonts fall back safely.
     */
        return $fallback_font;
    }
}
