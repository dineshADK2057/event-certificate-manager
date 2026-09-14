<?php

/**
 * Template Builder Sidebar
 *
 * Handles the Builder element list and selected-element
 * properties interface.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Builder_Sidebar
{
    /**
     * Render the Builder sidebar.
     *
     * @param object $event
     * @param object $template
     * @param array  $elements
     * @param array  $font_groups
     *
     * @return void
     */
    private function render_builder_sidebar(
        $event,
        $template,
        $elements,
        $font_groups
    ) {
?>
        <div class="ecm-builder-sidebar ecm-builder-properties-sidebar">
            <!-- Element list view -->
            <div id="ecm-elements-list-view">
                <div class="ecm-builder-sidebar-tools">
                    <div class="ecm-builder-zoom-controls">
                        <button
                            type="button"
                            class="ecm-button button"
                            id="ecm-toolbar-zoom-out"
                            title="Zoom out">
                            −
                        </button>

                        <button
                            type="button"
                            class="ecm-button button ecm-builder-zoom-value"
                            id="ecm-toolbar-zoom-value"
                            title="Reset zoom to 100%">
                            100%
                        </button>

                        <button
                            type="button"
                            class="ecm-button button"
                            id="ecm-toolbar-zoom-in"
                            title="Zoom in">
                            +
                        </button>

                        <button
                            type="button"
                            class="ecm-button button"
                            id="ecm-toolbar-fit-width">
                            Fit Width
                        </button>
                    </div>


                    <div class="ecm-builder-panel-header">
                        <h3>Elements</h3>

                        <button
                            type="button"
                            class="button button-primary ecm-open-element-modal">
                            + Add Element
                        </button>
                    </div>
                </div>

                <?php
                $this->render_builder_participant_preview(
                    $event,
                    $template
                );
                ?>

                <?php if (empty($elements)) : ?>

                    <div class="ecm-elements-empty-state">
                        <div class="ecm-elements-empty-icon" aria-hidden="true">
                            +
                        </div>

                        <h4>No placeholders yet</h4>

                        <p>
                            Add your first dynamic element to begin designing
                            this certificate.
                        </p>

                        <button
                            type="button"
                            class="button button-primary ecm-open-element-modal">
                            + Add Element
                        </button>
                    </div>


                <?php else : ?>
                    <ul class="ecm-elements-list">
                        <?php foreach ($elements as $element) : ?>
                            <?php
                            $delete_element_url = wp_nonce_url(
                                admin_url(
                                    'admin.php?page=ecm-events&action=delete_template_element' .
                                        '&event_id=' . absint($event->id) .
                                        '&template_id=' . absint($template->id) .
                                        '&element_id=' . absint($element->id)
                                ),
                                'ecm_delete_template_element_' . absint($element->id)
                            );
                            ?>

                            <li
                                class="ecm-element-list-item"
                                data-element-id="<?php echo esc_attr($element->id); ?>">
                                <button
                                    type="button"
                                    class="ecm-select-element-from-list"
                                    data-element-id="<?php echo esc_attr($element->id); ?>">
                                    <strong>
                                        <?php echo esc_html('{' . $element->placeholder_key . '}'); ?>
                                    </strong>

                                    <span class="description">
                                        X: <?php echo esc_html($element->x_position); ?>,
                                        Y: <?php echo esc_html($element->y_position); ?>,
                                        Size: <?php echo esc_html($element->font_size); ?>
                                    </span>
                                </button>

                                <div class="ecm-element-list-actions">
                                    <a
                                        href="#"
                                        class="ecm-select-element-from-list"
                                        data-element-id="<?php echo esc_attr($element->id); ?>">

                                        Edit
                                    </a>

                                    <span aria-hidden="true">|</span>

                                    <a
                                        href="<?php echo esc_url($delete_element_url); ?>"
                                        onclick="return confirm('Delete this template element?');"
                                        class="ecm-danger-link">
                                        Delete
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Selected element properties view -->
            <div id="ecm-element-properties-view" style="display:none;">
                <div class="ecm-builder-panel-header">
                    <button type="button" class="button-link ecm-back-to-elements">
                        ← Elements
                    </button>

                    <span class="ecm-selected-element-badge">Selected</span>
                </div>


                <input
                    type="hidden"
                    id="ecm_properties_element_id"
                    value="">

                <!-- Content -->

                <div class="ecm-property-field">
                    <label for="ecm_properties_placeholder">
                        Placeholder
                    </label>

                    <input
                        type="text"
                        id="ecm_properties_placeholder"
                        class="widefat"
                        readonly>
                </div>


                <!-- Typography -->
                <section
                    class="ecm-property-card ecm-text-element-properties"
                    data-element-properties-for="text">
                    <div class="ecm-property-card-header">
                        <h4>Typography</h4>
                        <p>Control the appearance of this text.</p>

                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_font_search">
                            Font Family
                        </label>

                        <div
                            class="ecm-font-picker"
                            id="ecm-properties-font-picker">
                            <?php
                            $local_font_stylesheets =
                                class_exists('ECM_Font_Manager')
                                ? ECM_Font_Manager::get_local_stylesheet_urls()
                                : [];

                            foreach ($local_font_stylesheets as $stylesheet_url) :
                            ?>
                                <link
                                    rel="stylesheet"
                                    href="<?php echo esc_url($stylesheet_url); ?>">
                            <?php endforeach; ?>

                            <input
                                type="hidden"
                                id="ecm_properties_font_family"
                                value="Arial">

                            <button
                                type="button"
                                class="ecm-font-picker-trigger"
                                aria-haspopup="listbox"
                                aria-expanded="false">
                                <span
                                    class="ecm-font-picker-current"
                                    style="font-family: Arial, sans-serif;">
                                    Arial
                                </span>

                                <span
                                    class="ecm-font-picker-arrow"
                                    aria-hidden="true">
                                    ▾
                                </span>
                            </button>

                            <div
                                class="ecm-font-picker-dropdown"
                                role="dialog"
                                aria-label="Choose font"
                                hidden>
                                <div class="ecm-font-picker-search-wrap">
                                    <input
                                        type="search"
                                        id="ecm_properties_font_search"
                                        class="ecm-font-picker-search"
                                        placeholder="Search fonts..."
                                        autocomplete="off">
                                </div>

                                <div
                                    class="ecm-font-picker-options"
                                    role="listbox">
                                    <?php
                                    $has_builtin_fonts = !empty($font_groups['builtin']);
                                    $has_google_fonts = !empty($font_groups['google']);
                                    ?>

                                    <?php if (!$has_builtin_fonts && !$has_google_fonts) : ?>

                                        <div class="ecm-font-picker-empty">
                                            No fonts are available.
                                        </div>

                                    <?php else : ?>

                                        <?php if ($has_builtin_fonts) : ?>

                                            <div
                                                class="ecm-font-picker-group-label"
                                                data-font-group="builtin">
                                                Built-in Fonts
                                            </div>

                                            <?php foreach ($font_groups['builtin'] as $font) : ?>
                                                <?php
                                                $family = $font['family'] ?? '';

                                                if ($family === '') {
                                                    continue;
                                                }
                                                ?>

                                                <button
                                                    type="button"
                                                    class="ecm-font-picker-option"
                                                    role="option"
                                                    data-font-family="<?php echo esc_attr($family); ?>"
                                                    data-font-source="builtin"
                                                    data-font-installed="1"
                                                    data-font-preview-url=""
                                                    style="font-family:'<?php echo esc_attr($family); ?>', sans-serif;">
                                                    <span class="ecm-font-option-preview">
                                                        <?php echo esc_html($family); ?>
                                                    </span>

                                                    <span class="ecm-font-option-source">
                                                        Built-in
                                                    </span>
                                                </button>
                                            <?php endforeach; ?>

                                        <?php endif; ?>

                                        <?php if ($has_google_fonts) : ?>

                                            <div
                                                class="ecm-font-picker-group-label"
                                                data-font-group="google">
                                                Google Fonts
                                            </div>

                                            <?php foreach ($font_groups['google'] as $category_label => $category_fonts) : ?>

                                                <?php if (empty($category_fonts)) : ?>
                                                    <?php continue; ?>
                                                <?php endif; ?>

                                                <div
                                                    class="ecm-font-picker-category-label"
                                                    data-font-category="<?php echo esc_attr(
                                                                            sanitize_title($category_label)
                                                                        ); ?>">
                                                    <?php echo esc_html($category_label); ?>
                                                </div>

                                                <?php foreach ($category_fonts as $font) : ?>
                                                    <?php
                                                    $family = $font['family'] ?? '';

                                                    if ($family === '') {
                                                        continue;
                                                    }

                                                    $installed = !empty($font['installed']);
                                                    ?>

                                                    <button
                                                        type="button"
                                                        class="ecm-font-picker-option"
                                                        role="option"
                                                        data-font-family="<?php echo esc_attr($family); ?>"
                                                        data-font-source="google"
                                                        data-font-installed="<?php echo $installed ? '1' : '0'; ?>"
                                                        data-font-preview-url="<?php echo esc_url(
                                                                                    $font['preview_url'] ?? ''
                                                                                ); ?>"
                                                        style="font-family:'<?php echo esc_attr($family); ?>', sans-serif;">
                                                        <span class="ecm-font-option-preview">
                                                            <?php echo esc_html($family); ?>
                                                        </span>

                                                        <span class="ecm-font-option-source">
                                                            <?php echo $installed ? 'Installed' : 'Google'; ?>
                                                        </span>
                                                    </button>

                                                <?php endforeach; ?>

                                            <?php endforeach; ?>

                                        <?php endif; ?>

                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_font_size">
                            Font Size
                        </label>

                        <div class="ecm-number-stepper">
                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_font_size"
                                data-stepper-direction="-1"
                                aria-label="Decrease font size">
                                −
                            </button>

                            <input
                                type="number"
                                id="ecm_properties_font_size"
                                min="1"
                                step="1">

                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_font_size"
                                data-stepper-direction="1"
                                aria-label="Increase font size">
                                +
                            </button>
                        </div>
                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_font_color">
                            Font Color
                        </label>

                        <div class="ecm-color-control">
                            <div class="ecm-color-presets">
                                <?php
                                $preset_colors = [
                                    '#000000',
                                    '#FFFFFF',
                                    '#1E3A5F',
                                    '#C62828',
                                    '#2E7D32',
                                    '#C99700',
                                    '#6A1B9A',
                                ];

                                foreach ($preset_colors as $preset_color) :
                                ?>
                                    <button
                                        type="button"
                                        class="ecm-color-preset"
                                        data-color="<?php echo esc_attr($preset_color); ?>"
                                        style="--ecm-preset-color:<?php echo esc_attr($preset_color); ?>;"
                                        aria-label="Use color <?php echo esc_attr($preset_color); ?>"
                                        title="<?php echo esc_attr($preset_color); ?>"></button>
                                <?php endforeach; ?>
                            </div>

                            <label class="ecm-custom-color">
                                <span>Custom</span>

                                <input
                                    type="color"
                                    id="ecm_properties_font_color"
                                    value="#000000">
                            </label>
                        </div>
                    </div>

                    <div class="ecm-property-field">
                        <label>
                            Text Alignment
                        </label>

                        <input
                            type="hidden"
                            id="ecm_properties_alignment"
                            value="left">

                        <div
                            class="ecm-alignment-control"
                            role="group"
                            aria-label="Text alignment">

                            <button
                                type="button"
                                class="ecm-alignment-button is-active"
                                data-text-alignment="left"
                                aria-label="Align text left"
                                title="Align Left">

                                <span class="dashicons dashicons-editor-alignleft"></span>
                            </button>

                            <button
                                type="button"
                                class="ecm-alignment-button"
                                data-text-alignment="center"
                                aria-label="Align text center"
                                title="Align Center">

                                <span class="dashicons dashicons-editor-aligncenter"></span>
                            </button>

                            <button
                                type="button"
                                class="ecm-alignment-button"
                                data-text-alignment="right"
                                aria-label="Align text right"
                                title="Align Right">

                                <span class="dashicons dashicons-editor-alignright"></span>
                            </button>

                        </div>
                    </div>
                </section>

                <!-- QR Code -->

                <section
                    class="ecm-property-card ecm-qr-element-properties"
                    data-element-properties-for="qr"
                    style="display:none;">

                    <div class="ecm-property-card-header">
                        <h4>QR Code</h4>
                        <p>Control the dimensions of the verification QR code.</p>
                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_width">
                            Width
                        </label>

                        <div class="ecm-number-stepper">
                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_width"
                                data-stepper-direction="-1"
                                aria-label="Decrease QR width">
                                −
                            </button>

                            <input
                                type="number"
                                id="ecm_properties_width"
                                min="20"
                                step="1"
                                value="120">

                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_width"
                                data-stepper-direction="1"
                                aria-label="Increase QR width">
                                +
                            </button>
                        </div>
                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_height">
                            Height
                        </label>

                        <div class="ecm-number-stepper">
                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_height"
                                data-stepper-direction="-1"
                                aria-label="Decrease QR height">
                                −
                            </button>

                            <input
                                type="number"
                                id="ecm_properties_height"
                                min="20"
                                step="1"
                                value="120">

                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_height"
                                data-stepper-direction="1"
                                aria-label="Increase QR height">
                                +
                            </button>
                        </div>
                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_qr_foreground_color">
                            QR Color
                        </label>

                        <input
                            type="color"
                            id="ecm_properties_qr_foreground_color"
                            value="#000000">
                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_qr_background_color">
                            Background
                        </label>

                        <input
                            type="color"
                            id="ecm_properties_qr_background_color"
                            value="#ffffff">
                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_qr_border_color">
                            Border Color
                        </label>

                        <input
                            type="color"
                            id="ecm_properties_qr_border_color"
                            value="#000000">
                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_qr_border_width">
                            Border Width
                        </label>

                        <div class="ecm-number-stepper">

                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_qr_border_width"
                                data-stepper-direction="-1"
                                aria-label="Decrease QR border width">
                                −
                            </button>

                            <input
                                type="number"
                                id="ecm_properties_qr_border_width"
                                min="0"
                                step="1"
                                value="0">

                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_qr_border_width"
                                data-stepper-direction="1"
                                aria-label="Increase QR border width">
                                +
                            </button>

                        </div>
                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_qr_border_radius">
                            Border Radius
                        </label>

                        <div class="ecm-number-stepper">
                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_qr_border_radius"
                                data-stepper-direction="-1">
                                −
                            </button>

                            <input
                                type="number"
                                id="ecm_properties_qr_border_radius"
                                value="0"
                                min="0"
                                step="1">

                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_qr_border_radius"
                                data-stepper-direction="1">
                                +
                            </button>
                        </div>
                    </div>

                </section>

                <!-- Position -->
                <section class="ecm-property-card">
                    <div class="ecm-property-card-header">
                        <h4>Position</h4>
                        <p>Fine-tune placement on the certificate.</p>
                    </div>

                    <div class="ecm-property-field">
                        <div class="ecm-property-label-row">
                            <label for="ecm_properties_x_position">
                                Horizontal Position
                            </label>

                            <div
                                class="ecm-inline-alignment-control"
                                role="group"
                                aria-label="Horizontal element alignment">

                                <button
                                    type="button"
                                    class="ecm-inline-alignment-button"
                                    data-element-align="left"
                                    aria-label="Align element left"
                                    title="Align Left">
                                    <span class="dashicons dashicons-align-left"></span>
                                </button>

                                <button
                                    type="button"
                                    class="ecm-inline-alignment-button"
                                    data-element-align="center"
                                    aria-label="Align element center"
                                    title="Align Center">
                                    <span class="dashicons dashicons-align-center"></span>
                                </button>

                                <button
                                    type="button"
                                    class="ecm-inline-alignment-button"
                                    data-element-align="right"
                                    aria-label="Align element right"
                                    title="Align Right">
                                    <span class="dashicons dashicons-align-right"></span>
                                </button>
                            </div>
                        </div>

                        <div class="ecm-number-stepper">
                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_x_position"
                                data-stepper-direction="-1"
                                aria-label="Move left">
                                −
                            </button>

                            <input
                                type="number"
                                id="ecm_properties_x_position"
                                step="1">

                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_x_position"
                                data-stepper-direction="1"
                                aria-label="Move right">
                                +
                            </button>
                        </div>
                    </div>

                    <div class="ecm-property-field">
                        <div class="ecm-property-label-row">
                            <label for="ecm_properties_y_position">
                                Vertical Position
                            </label>

                            <div
                                class="ecm-inline-alignment-control"
                                role="group"
                                aria-label="Vertical element alignment">

                                <button
                                    type="button"
                                    class="ecm-inline-alignment-button"
                                    data-element-align="top"
                                    aria-label="Align element top"
                                    title="Align Top">
                                    <span class="ecm-vertical-align-icon ecm-align-top-icon"></span>
                                </button>

                                <button
                                    type="button"
                                    class="ecm-inline-alignment-button"
                                    data-element-align="middle"
                                    aria-label="Align element middle"
                                    title="Align Middle">
                                    <span class="ecm-vertical-align-icon ecm-align-middle-icon"></span>
                                </button>

                                <button
                                    type="button"
                                    class="ecm-inline-alignment-button"
                                    data-element-align="bottom"
                                    aria-label="Align element bottom"
                                    title="Align Bottom">
                                    <span class="ecm-vertical-align-icon ecm-align-bottom-icon"></span>
                                </button>
                            </div>
                        </div>

                        <div class="ecm-number-stepper">
                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_y_position"
                                data-stepper-direction="-1"
                                aria-label="Move up">
                                −
                            </button>

                            <input
                                type="number"
                                id="ecm_properties_y_position"
                                step="1">

                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_y_position"
                                data-stepper-direction="1"
                                aria-label="Move down">
                                +
                            </button>
                        </div>
                    </div>

                    <div class="ecm-property-field">
                        <label for="ecm_properties_rotation">
                            Rotation
                        </label>

                        <div class="ecm-number-stepper">
                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_rotation"
                                data-stepper-direction="-1"
                                aria-label="Rotate counter-clockwise">
                                −
                            </button>

                            <input
                                type="number"
                                id="ecm_properties_rotation"
                                step="1">

                            <button
                                type="button"
                                class="ecm-stepper-button"
                                data-stepper-target="ecm_properties_rotation"
                                data-stepper-direction="1"
                                aria-label="Rotate clockwise">
                                +
                            </button>
                        </div>
                    </div>
                </section>

                <div class="ecm-auto-save-status" aria-live="polite">
                    <span class="ecm-auto-save-dot"></span>
                    <span id="ecm-element-save-status">
                        Changes save automatically
                    </span>
                </div>

            </div>

        </div>
<?php
    }
}
