<?php

/**
 * Template Builder Canvas
 *
 * Handles the certificate workspace, background preview,
 * and visual rendering of template elements.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Builder_Canvas
{
    /**
     * Render the Builder certificate canvas.
     *
     * @param object $event
     * @param object $template
     * @param array  $elements
     * @param string $background_url
     * @param string $background_error
     *
     * @return void
     */
    private function render_builder_canvas(
        $event,
        $template,
        $elements,
        $background_url,
        $background_error
    ) {
?>
<div class="ecm-builder-workspace">

    <div class="ecm-builder-zoom-wrapper">

        <div
            class="ecm-builder-canvas
                    ecm-builder-<?php echo esc_attr($template->orientation); ?>
                    ecm-page-<?php echo esc_attr(strtolower($template->page_size)); ?>">

            <?php if (!empty($background_url)) : ?>
                <img
                    src="<?php echo esc_url($background_url); ?>"
                    class="ecm-builder-bg"
                    alt="<?php echo esc_attr($template->template_name); ?>">
            <?php else : ?>
                <div class="ecm-empty-canvas">
                    <?php if (!empty($background_error)) : ?>
                        <div class="ecm-builder-error">
                            <strong>Preview unavailable</strong>
                            <span>
                                <?php echo esc_html($background_error); ?>
                            </span>
                        </div>
                    <?php elseif (!empty($template->background_file)) : ?>
                        Preview could not be generated.
                    <?php else : ?>
                        No background uploaded.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php foreach ($elements as $element) : ?>
                <?php
                $sample_value = $this->get_template_element_sample_value(
                    $element,
                    $event,
                    $template
                );

                $element_type = !empty($element->element_type)
                    ? strtolower((string) $element->element_type)
                    : 'text';

                $width = isset($element->width) && $element->width !== null
                    ? (float) $element->width
                    : null;

                $height = isset($element->height) && $element->height !== null
                    ? (float) $element->height
                    : null;

                $style_parts = [
                    'left:' . esc_attr($element->x_position) . 'px',
                    'top:' . esc_attr($element->y_position) . 'px',
                    'transform:rotate(' . esc_attr($element->rotation) . 'deg)',
                ];

                if ($element_type === 'text') {
                    $style_parts[] =
                        'font-family:' . esc_attr($element->font_family);

                    $style_parts[] =
                        'font-size:' . esc_attr($element->font_size) . 'px';

                    $style_parts[] =
                        'color:' . esc_attr($element->font_color);

                    $style_parts[] =
                        'text-align:' . esc_attr($element->alignment);
                }

                if ($width !== null && $width > 0) {
                    $style_parts[] =
                        'width:' . esc_attr($width) . 'px';
                }

                if ($height !== null && $height > 0) {
                    $style_parts[] =
                        'height:' . esc_attr($height) . 'px';
                }

                $style = implode('; ', $style_parts) . ';';
                ?>

                <div
                    class="ecm-builder-element
                    ecm-builder-element-frame
                    ecm-selectable-builder-element"

                    data-element-id="<?php echo esc_attr($element->id); ?>"

                    data-element-type="<?php echo esc_attr($element_type); ?>"
                    data-width="<?php echo esc_attr($width !== null ? $width : ''); ?>"
                    data-height="<?php echo esc_attr($height !== null ? $height : ''); ?>"
                    data-qr-foreground-color="<?php
                                                echo esc_attr(
                                                    !empty($element->qr_foreground_color)
                                                        ? $element->qr_foreground_color
                                                        : '#000000'
                                                );
                                                ?>"

                    data-qr-background-color="<?php
                                                echo esc_attr(
                                                    !empty($element->qr_background_color)
                                                        ? $element->qr_background_color
                                                        : '#FFFFFF'
                                                );
                                                ?>"

                    data-qr-border-color="<?php
                                            echo esc_attr(
                                                !empty($element->qr_border_color)
                                                    ? $element->qr_border_color
                                                    : '#000000'
                                            );
                                            ?>"

                    data-qr-border-width="<?php
                                            echo esc_attr(
                                                isset($element->qr_border_width)
                                                    ? (float) $element->qr_border_width
                                                    : 0
                                            );
                                            ?>"

                    data-qr-border-radius="<?php
                                            echo esc_attr(
                                                isset($element->qr_border_radius)
                                                    ? (float) $element->qr_border_radius
                                                    : 0
                                            );
                                            ?>"

                    data-placeholder-key="<?php echo esc_attr($element->placeholder_key); ?>"
                    data-source-type="<?php echo esc_attr($element->source_type); ?>"
                    data-font-family="<?php echo esc_attr($element->font_family); ?>"
                    data-font-size="<?php echo esc_attr($element->font_size); ?>"
                    data-font-color="<?php echo esc_attr($element->font_color); ?>"
                    data-alignment="<?php echo esc_attr($element->alignment); ?>"
                    data-x-position="<?php echo esc_attr($element->x_position); ?>"
                    data-y-position="<?php echo esc_attr($element->y_position); ?>"
                    data-rotation="<?php echo esc_attr($element->rotation); ?>"
                    tabindex="0"
                    role="button"
                    aria-label="<?php echo esc_attr(
                                    'Select {' . $element->placeholder_key . '} element'
                                ); ?>"
                    style="<?php echo esc_attr($style); ?>">
                    <div class="ecm-builder-element-content">
                        <?php echo esc_html($sample_value); ?>
                    </div>

                    
                </div>
            <?php endforeach; ?>

        </div>

    </div>

</div>

<?php
    }
}
