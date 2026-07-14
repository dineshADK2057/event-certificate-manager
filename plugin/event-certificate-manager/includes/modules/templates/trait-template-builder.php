<?php

/**
 * Template Builder Module
 *
 * Handles the visual certificate builder screen, builder layout,
 * available variables, canvas presentation, and builder routing.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Template_Builder
{



    private function render_template_builder_page($event_id, $template_id)
    {

        global $wpdb;

        $events_table    = $wpdb->prefix . 'ecm_events';
        $templates_table = $wpdb->prefix . 'ecm_templates';
        $elements_table  = $wpdb->prefix . 'ecm_template_elements';

        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $events_table WHERE id = %d",
                $event_id
            )
        );

        $template = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $templates_table WHERE id = %d AND event_id = %d",
                $template_id,
                $event_id
            )
        );

        if (!$event || !$template) {
            echo '<div class="notice notice-error"><p>Template not found.</p></div>';
            return;
        }

        $elements = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $elements_table WHERE template_id = %d ORDER BY element_order ASC, id ASC",
                $template_id
            )
        );

        $builder_background = $this->get_template_builder_background($template);

        $background_url   = $builder_background['url'];
        $background_error = $builder_background['error'];

        $back_url = admin_url(
            'admin.php?page=ecm-events&action=manage&event_id=' . absint($event_id) . '&tab=templates'
        );

        $variables = $this->get_template_variables($event, $template);

        /*
        * Fonts available to the Builder.
        */
        $installed_fonts = class_exists('ECM_Font_Manager')
            ? ECM_Font_Manager::get_available_fonts()
            : [];

        $available_fonts = class_exists('ECM_Google_Fonts')
            ? ECM_Google_Fonts::merge_with_installed($installed_fonts)
            : $installed_fonts;

        /*
        * Local @font-face declarations for installed Google/custom fonts.
        */
        $font_face_css = class_exists('ECM_Font_Manager')
            ? ECM_Font_Manager::get_font_face_css()
            : '';

?>

        <div class="wrap ecm-wrap">

            <?php if (!empty($font_face_css)) : ?>
                <style id="ecm-builder-font-faces">
                    <?php echo wp_strip_all_tags($font_face_css); ?>
                </style>
            <?php endif; ?>

            <div class="ecm-form-header">
                <a href="<?php echo esc_url($back_url); ?>" class="button">
                    ← Back to Templates
                </a>
            </div>

            <?php if (isset($_GET['element_added'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Template element added successfully.</strong></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['element_updated'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Template element updated successfully.</strong></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['element_deleted'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Template element deleted successfully.</strong></p>
                </div>
            <?php endif; ?>

            <div class="ecm-event-heading">
                <div>
                    <h2>Template Builder: <?php echo esc_html($template->template_name); ?></h2>
                    <p>
                        <strong>Event:</strong> <?php echo esc_html($event->event_name); ?>
                        &nbsp; | &nbsp;
                        <strong>Type:</strong> <?php echo esc_html($template->certificate_type); ?>
                        &nbsp; | &nbsp;
                        <strong>Page:</strong> <?php echo esc_html($template->page_size); ?> / <?php echo esc_html($template->orientation); ?>
                    </p>
                </div>
            </div>

            <input
                type="hidden"
                id="ecm_element_properties_nonce"
                value="<?php echo esc_attr(
                            wp_create_nonce('ecm_update_template_element_properties')
                        ); ?>">

            <input
                type="hidden"
                id="ecm_builder_event_id"
                value="<?php echo esc_attr($event->id); ?>">

            <input
                type="hidden"
                id="ecm_builder_template_id"
                value="<?php echo esc_attr($template->id); ?>">


            <div class="ecm-builder-layout">

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

                                $style = sprintf(
                                    'left:%spx; top:%spx; font-family:%s; font-size:%spx; color:%s; text-align:%s; transform:rotate(%sdeg);',
                                    esc_attr($element->x_position),
                                    esc_attr($element->y_position),
                                    esc_attr($element->font_family),
                                    esc_attr($element->font_size),
                                    esc_attr($element->font_color),
                                    esc_attr($element->alignment),
                                    esc_attr($element->rotation)
                                );
                                ?>

                                <div
                                    class="ecm-builder-element
                           ecm-builder-element-frame
                           ecm-selectable-builder-element"
                                    data-element-id="<?php echo esc_attr($element->id); ?>"
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

                                    <div
                                        class="ecm-builder-element-handles"
                                        aria-hidden="true">
                                        <span class="ecm-element-handle ecm-element-handle-nw"></span>
                                        <span class="ecm-element-handle ecm-element-handle-ne"></span>
                                        <span class="ecm-element-handle ecm-element-handle-sw"></span>
                                        <span class="ecm-element-handle ecm-element-handle-se"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        </div>

                    </div>

                </div>

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
                        <section class="ecm-property-card">
                            <div class="ecm-property-card-header">
                                <h4>Content</h4>
                                <p>Dynamic value used on the certificate.</p>
                            </div>

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
                        </section>

                        <!-- Typography -->
                        <section class="ecm-property-card">
                            <div class="ecm-property-card-header">
                                <h4>Typography</h4>
                                <p>Control the appearance of this text.</p>
                                
                            </div>

                            <div class="ecm-property-field">
                                <label for="ecm_properties_font_search">
                                    Font Family
                                </label>
                                <p class="ecm-font-picker-help">
                                    Google Font previews require an internet connection.
                                    Selected Google Fonts will be installed locally before
                                    final certificate generation.
                                </p>

                                <div
                                    class="ecm-font-picker"
                                    id="ecm-properties-font-picker">
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
                                            <?php if (empty($available_fonts)) : ?>

                                                <div class="ecm-font-picker-empty">
                                                    No fonts are available.
                                                </div>

                                            <?php else : ?>

                                                <?php
                                                $current_source = '';

                                                $source_labels = [
                                                    'builtin'   => 'Built-in Fonts',
                                                    'google'    => 'Google Fonts',
                                                    'custom'    => 'Custom Fonts',
                                                    'wordpress' => 'Website Fonts',
                                                ];

                                                foreach ($available_fonts as $font) :
                                                    $family = $font['family'] ?? '';
                                                    $source = $font['source'] ?? 'builtin';

                                                    if ($family === '') {
                                                        continue;
                                                    }

                                                    if ($source !== $current_source) :
                                                        $current_source = $source;
                                                ?>

                                                        <div
                                                            class="ecm-font-picker-group-label"
                                                            data-font-group="<?php echo esc_attr($source); ?>">
                                                            <?php
                                                            echo esc_html(
                                                                $source_labels[$source]
                                                                    ?? ucfirst($source)
                                                            );
                                                            ?>
                                                        </div>

                                                    <?php endif; ?>

                                                    <button
                                                        type="button"
                                                        class="ecm-font-picker-option"
                                                        role="option"
                                                        data-font-family="<?php echo esc_attr($family); ?>"
                                                        data-font-source="<?php echo esc_attr($source); ?>"
                                                        data-font-installed="<?php
                                                                                echo !empty($font['installed']) ? '1' : '0';
                                                                                ?>"
                                                        data-font-preview-url="<?php
                                                                                echo esc_url($font['preview_url'] ?? '');
                                                                                ?>"
                                                        style="font-family:'<?php
                                                                            echo esc_attr($family);
                                                                            ?>',sans-serif;">
                                                        <span class="ecm-font-option-preview">
                                                            <?php echo esc_html($family); ?>
                                                        </span>

                                                        <span class="ecm-font-option-source">
                                                            <?php if (
                                                                $source === 'google' &&
                                                                empty($font['installed'])
                                                            ) : ?>
                                                                Google
                                                            <?php elseif ($source === 'google') : ?>
                                                                Installed
                                                            <?php else : ?>
                                                                <?php echo esc_html(ucfirst($source)); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    </button>

                                                <?php endforeach; ?>

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
                                <label for="ecm_properties_alignment">
                                    Alignment
                                </label>

                                <select
                                    id="ecm_properties_alignment"
                                    class="widefat">
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                        </section>

                        <!-- Position -->
                        <section class="ecm-property-card">
                            <div class="ecm-property-card-header">
                                <h4>Position</h4>
                                <p>Fine-tune placement on the certificate.</p>
                            </div>

                            <div class="ecm-property-field">
                                <label for="ecm_properties_x_position">
                                    Horizontal Position
                                </label>

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
                                <label for="ecm_properties_y_position">
                                    Vertical Position
                                </label>

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



            </div>
        </div>
        <?php $this->render_add_element_modal($event, $template, $variables); ?>
<?php
    }

    private function get_template_variables($event, $template)
    {
        $variables = [
            'Participant Fields' => [],
            'Event Fields' => [
                '{event_name}',
                '{event_type}',
                '{event_venue}',
                '{event_start_date}',
                '{event_end_date}',
            ],
            'Session Fields' => [
                '{session_name}',
                '{session_code}',
                '{tutor_name}',
                '{session_date}',
            ],
            'System Fields' => [
                '{issue_date}',
                '{certificate_id}',
                '{qr_code}',
            ],
        ];

        $fields = $this->get_event_fields($event->id);

        foreach ($fields as $field) {
            $variables['Participant Fields'][] = '{' . $field->field_key . '}';
        }

        return $variables;
    }
}
