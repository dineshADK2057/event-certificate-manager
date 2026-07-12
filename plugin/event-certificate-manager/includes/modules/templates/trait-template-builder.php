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
?>

        <div class="wrap ecm-wrap">
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

            <div class="ecm-builder-layout">

                <div class="ecm-builder-canvas-wrap">

                    <div class="ecm-builder-canvas ecm-builder-<?php echo esc_attr($template->orientation); ?> ecm-page-<?php echo esc_attr(strtolower($template->page_size)); ?>">

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
                                        <span><?php echo esc_html($background_error); ?></span>
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
                            $sample_value = $this->get_template_element_sample_value($element, $event, $template);

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
                                class="ecm-builder-element ecm-selectable-builder-element"
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
                                style="<?php echo esc_attr($style); ?>">
                                <?php echo esc_html($sample_value); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="ecm-builder-sidebar ecm-builder-properties-sidebar">

                    <!-- Element list view -->
                    <div id="ecm-elements-list-view">
                        <div class="ecm-builder-panel-header">
                            <h3>Elements</h3>

                            <button type="button" class="button button-primary ecm-open-element-modal">
                                + Add Element
                            </button>
                        </div>

                        <?php if (empty($elements)) : ?>
                            <p>No elements added yet.</p>
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
                                                class="ecm-edit-element"
                                                data-element-id="<?php echo esc_attr($element->id); ?>"
                                                data-placeholder-key="<?php echo esc_attr($element->placeholder_key); ?>"
                                                data-source-type="<?php echo esc_attr($element->source_type); ?>"
                                                data-font-family="<?php echo esc_attr($element->font_family); ?>"
                                                data-font-size="<?php echo esc_attr($element->font_size); ?>"
                                                data-font-color="<?php echo esc_attr($element->font_color); ?>"
                                                data-alignment="<?php echo esc_attr($element->alignment); ?>"
                                                data-x-position="<?php echo esc_attr($element->x_position); ?>"
                                                data-y-position="<?php echo esc_attr($element->y_position); ?>"
                                                data-rotation="<?php echo esc_attr($element->rotation); ?>">
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

                        <h3 id="ecm-properties-element-title">Element Properties</h3>

                        <input type="hidden" id="ecm_properties_element_id" value="">

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

                        <div class="ecm-property-field">
                            <label for="ecm_properties_font_family">
                                Font Family
                            </label>

                            <input
                                type="text"
                                id="ecm_properties_font_family"
                                class="widefat">
                        </div>

                        <div class="ecm-property-field">
                            <label for="ecm_properties_font_size">
                                Font Size
                            </label>

                            <input
                                type="number"
                                id="ecm_properties_font_size"
                                class="widefat"
                                min="1"
                                step="1">
                        </div>

                        <div class="ecm-property-field">
                            <label for="ecm_properties_font_color">
                                Font Color
                            </label>

                            <input
                                type="color"
                                id="ecm_properties_font_color">
                        </div>

                        <div class="ecm-property-field">
                            <label for="ecm_properties_alignment">
                                Alignment
                            </label>

                            <select id="ecm_properties_alignment" class="widefat">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>

                        <div class="ecm-property-row">
                            <div class="ecm-property-field">
                                <label for="ecm_properties_x_position">
                                    X
                                </label>

                                <input
                                    type="number"
                                    id="ecm_properties_x_position"
                                    class="widefat"
                                    step="0.1">
                            </div>

                            <div class="ecm-property-field">
                                <label for="ecm_properties_y_position">
                                    Y
                                </label>

                                <input
                                    type="number"
                                    id="ecm_properties_y_position"
                                    class="widefat"
                                    step="0.1">
                            </div>
                        </div>

                        <div class="ecm-property-field">
                            <label for="ecm_properties_rotation">
                                Rotation
                            </label>

                            <input
                                type="number"
                                id="ecm_properties_rotation"
                                class="widefat"
                                step="0.1">
                        </div>

                        <p>
                            <button
                                type="button"
                                class="button button-primary"
                                id="ecm-save-element-properties"
                                disabled>
                                Save Changes
                            </button>
                        </p>

                        <p class="description">
                            Saving from this panel will be enabled in the next step.
                        </p>
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
