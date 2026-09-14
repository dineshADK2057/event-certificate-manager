<?php

/**
 * Template Elements Module
 *
 * Handles the creation, editing, deletion, validation,
 * and storage of certificate template elements.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Template_Elements
{

    private function render_add_element_modal($event, $template, $variables)
    {
?>
        <div id="ecm-add-element-modal" class="ecm-modal" style="display:none;">
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2 id="ecm-element-modal-title">Add Template Element</h2>
                    <button type="button" class="ecm-modal-close">&times;</button>
                </div>

                <form method="post">
                    <?php wp_nonce_field('ecm_add_template_element', 'ecm_add_template_element_nonce'); ?>
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                    <input type="hidden" name="template_id" value="<?php echo esc_attr($template->id); ?>">

                    <div class="ecm-modal-body">
                        <p>
                            <label>
                                <strong>Element Type</strong>

                                <select
                                    name="element_type"
                                    id="ecm_element_type"
                                    class="widefat">

                                    <option value="text">
                                        Text
                                    </option>

                                    <option value="qr">
                                        QR Code
                                    </option>

                                </select>
                            </label>
                        </p>
                        <p>
                            <label>
                                <strong>Placeholder</strong>
                                <select name="placeholder_key" id="ecm_element_placeholder_key" class="widefat" required>
                                    <?php foreach ($variables as $group_label => $items) : ?>
                                        <optgroup label="<?php echo esc_attr($group_label); ?>">
                                            <?php foreach ($items as $variable) : ?>
                                                <?php
                                                $key = trim($variable, '{}');

                                                if ($group_label === 'Participant Fields') {
                                                    $source_type = 'participant';
                                                } elseif ($group_label === 'Session Fields') {
                                                    $source_type = 'session';
                                                } elseif ($group_label === 'Event Fields') {
                                                    $source_type = 'event';
                                                } else {
                                                    $source_type = 'system';
                                                }
                                                ?>
                                                <option value="<?php echo esc_attr($key); ?>"
                                                    data-source-type="<?php echo esc_attr($source_type); ?>">
                                                    <?php echo esc_html($variable); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </p>

                        <input type="hidden" name="source_type" id="ecm_element_source_type" value="participant">

                        <div id="ecm_add_text_element_fields">

                            <p>
                                <label>
                                    <strong>Font Family</strong>
                                    <input type="text" name="font_family" id="ecm_element_font_family" class="widefat" value="Arial">
                                </label>
                            </p>

                            <p>
                                <label>
                                    <strong>Font Size</strong>
                                    <input type="number" name="font_size" id="ecm_element_font_size" class="widefat" value="18" min="1">
                                </label>
                            </p>

                            <p>
                                <label>
                                    <strong>Font Color</strong>
                                    <input type="color" name="font_color" id="ecm_element_font_color" value="#000000">
                                </label>
                            </p>

                            <p>
                                <label>
                                    <strong>Alignment</strong>
                                    <select name="alignment" id="ecm_element_alignment" class="widefat">
                                        <option value="left">Left</option>
                                        <option value="center">Center</option>
                                        <option value="right">Right</option>
                                    </select>
                                </label>
                            </p>

                        </div>

                        <div
                            id="ecm_add_qr_element_fields"
                            style="display:none;">

                            <p>
                                <label>
                                    <strong>Width</strong>

                                    <input
                                        type="number"
                                        name="width"
                                        id="ecm_element_width"
                                        class="widefat"
                                        value="120"
                                        min="20"
                                        step="1">
                                </label>
                            </p>

                            <p>
                                <label>
                                    <strong>Height</strong>

                                    <input
                                        type="number"
                                        name="height"
                                        id="ecm_element_height"
                                        class="widefat"
                                        value="120"
                                        min="20"
                                        step="1">
                                </label>
                            </p>

                        </div>

                        <p>
                            <label>
                                <strong>X Position</strong>
                                <input type="number" name="x_position" id="ecm_element_x_position" class="widefat" value="100" step="0.1">
                            </label>
                        </p>

                        <p>
                            <label>
                                <strong>Y Position</strong>
                                <input type="number" name="y_position" id="ecm_element_y_position" class="widefat" value="100" step="0.1">
                            </label>
                        </p>

                        <p>
                            <label>
                                <strong>Rotation</strong>
                                <input type="number" name="rotation" id="ecm_element_rotation" class="widefat" value="0" step="0.1">
                            </label>
                        </p>
                    </div>

                    <div class="ecm-modal-footer">
                        <button type="submit" name="ecm_add_template_element_submit" id="ecm_add_template_element_submit" class="button button-primary">
                            Add Element
                        </button>

                        <button type="button" class="button ecm-modal-cancel">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
<?php
    }

    public function handle_add_template_element()
    {

        if (!isset($_POST['ecm_add_template_element_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_add_template_element_nonce']) ||
            !wp_verify_nonce($_POST['ecm_add_template_element_nonce'], 'ecm_add_template_element')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $template_id = isset($_POST['template_id'])
            ? absint($_POST['template_id'])
            : 0;

        $element_type = sanitize_key(
            wp_unslash($_POST['element_type'] ?? 'text')
        );

        $placeholder = sanitize_key(
            wp_unslash($_POST['placeholder_key'] ?? '')
        );

        $source_type = sanitize_key(
            wp_unslash($_POST['source_type'] ?? 'participant')
        );

        $font_family = sanitize_text_field(
            wp_unslash($_POST['font_family'] ?? 'Arial')
        );

        $font_size = isset($_POST['font_size'])
            ? max(1, floatval($_POST['font_size']))
            : 18;

        $font_color = sanitize_hex_color(
            wp_unslash($_POST['font_color'] ?? '#000000')
        );

        $alignment = sanitize_key(
            wp_unslash($_POST['alignment'] ?? 'left')
        );

        $x_position = isset($_POST['x_position'])
            ? floatval($_POST['x_position'])
            : 0;

        $y_position = isset($_POST['y_position'])
            ? floatval($_POST['y_position'])
            : 0;

        $width = isset($_POST['width'])
            ? max(20, floatval($_POST['width']))
            : null;

        $height = isset($_POST['height'])
            ? max(20, floatval($_POST['height']))
            : null;

        $rotation = isset($_POST['rotation'])
            ? floatval($_POST['rotation'])
            : 0;

        if (!$event_id || !$template_id || empty($placeholder)) {
            wp_die('Invalid element data.');
        }

        $allowed_element_types = [
            'text',
            'qr',
        ];

        if (!in_array($element_type, $allowed_element_types, true)) {
            $element_type = 'text';
        }

        /*
        * QR elements always use the system QR placeholder.
        * Do not trust client-side values for this relationship.
        */
        if ($element_type === 'qr') {
            $placeholder = 'qr_code';
            $source_type = 'system';

            $width = $width !== null
                ? max(20, $width)
                : 120;

            $height = $height !== null
                ? max(20, $height)
                : 120;
        } else {
            /*
            * Width and height are currently QR-specific.
            */
            $width = null;
            $height = null;
        }

        $allowed_sources = ['participant', 'event', 'session', 'system'];

        if (!in_array($source_type, $allowed_sources, true)) {
            $source_type = 'participant';
        }

        $allowed_alignments = ['left', 'center', 'right'];

        if (!in_array($alignment, $allowed_alignments, true)) {
            $alignment = 'left';
        }

        if (!$font_color) {
            $font_color = '#000000';
        }

        global $wpdb;

        $templates_table = $wpdb->prefix . 'ecm_templates';
        $elements_table  = $wpdb->prefix . 'ecm_template_elements';

        $template = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $templates_table WHERE id = %d AND event_id = %d",
                $template_id,
                $event_id
            )
        );

        if (!$template) {
            wp_die('Template not found.');
        }

        $max_order = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(element_order) FROM $elements_table WHERE template_id = %d",
                $template_id
            )
        );

        $inserted = $wpdb->insert(
            $elements_table,
            [
                'template_id'     => $template_id,
                'element_type'    => $element_type,
                'placeholder_key' => $placeholder,
                'source_type'     => $source_type,
                'x_position'      => $x_position,
                'y_position'      => $y_position,
                'width'           => $width,
                'height'          => $height,
                'font_family'     => $font_family,
                'font_size'       => $font_size,
                'font_color'      => $font_color,
                'alignment'       => $alignment,
                'rotation'        => $rotation,
                'element_order'   => $max_order + 1,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%f',
                '%f',
                '%f',
                '%f',
                '%s',
                '%f',
                '%s',
                '%s',
                '%f',
                '%d',
            ]
        );

        if (!$inserted) {
            wp_die('Failed to add template element.');
        }

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events&action=template_builder&event_id=' . $event_id .
                    '&template_id=' . $template_id .
                    '&element_added=1'
            )
        );
        exit;
    }


    public function handle_delete_template_element()
    {
        if (
            !isset($_GET['page'], $_GET['action'], $_GET['event_id'], $_GET['template_id'], $_GET['element_id']) ||
            $_GET['page'] !== 'ecm-events' ||
            $_GET['action'] !== 'delete_template_element'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id    = absint($_GET['event_id']);
        $template_id = absint($_GET['template_id']);
        $element_id  = absint($_GET['element_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'ecm_delete_template_element_' . $element_id)
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $templates_table = $wpdb->prefix . 'ecm_templates';
        $elements_table  = $wpdb->prefix . 'ecm_template_elements';

        $element = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT e.*
             FROM $elements_table e
             INNER JOIN $templates_table t ON e.template_id = t.id
             WHERE e.id = %d
             AND e.template_id = %d
             AND t.event_id = %d",
                $element_id,
                $template_id,
                $event_id
            )
        );

        if (!$element) {
            wp_die('Element not found.');
        }

        $wpdb->delete(
            $elements_table,
            [
                'id'          => $element_id,
                'template_id' => $template_id,
            ],
            ['%d', '%d']
        );

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events&action=template_builder&event_id=' . $event_id .
                    '&template_id=' . $template_id .
                    '&element_deleted=1'
            )
        );
        exit;
    }

    public function ajax_update_template_element_properties()
    {
        check_ajax_referer(
            'ecm_update_template_element_properties',
            'nonce'
        );

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'You do not have permission to update this element.',
            ], 403);
        }

        $event_id    = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $template_id = isset($_POST['template_id'])
            ? absint($_POST['template_id'])
            : 0;

        $element_id  = isset($_POST['element_id'])
            ? absint($_POST['element_id'])
            : 0;

        if (!$event_id || !$template_id || !$element_id) {
            wp_send_json_error([
                'message' => 'Invalid event, template, or element.',
            ], 400);
        }

        $font_family = sanitize_text_field(
            wp_unslash($_POST['font_family'] ?? 'Arial')
        );

        $font_size = isset($_POST['font_size'])
            ? max(1, floatval($_POST['font_size']))
            : 18;

        $font_color = sanitize_hex_color(
            wp_unslash($_POST['font_color'] ?? '#000000')
        );

        $alignment = sanitize_key(
            wp_unslash($_POST['alignment'] ?? 'left')
        );

        $x_position = isset($_POST['x_position'])
            ? floatval($_POST['x_position'])
            : 0;

        $y_position = isset($_POST['y_position'])
            ? floatval($_POST['y_position'])
            : 0;

        $rotation = isset($_POST['rotation'])
            ? floatval($_POST['rotation'])
            : 0;

        $width = isset($_POST['width'])
            ? max(20, floatval($_POST['width']))
            : null;

        $height = isset($_POST['height'])
            ? max(20, floatval($_POST['height']))
            : null;

        $qr_foreground_color = sanitize_hex_color(
            wp_unslash(
                $_POST['qr_foreground_color']
                    ?? '#000000'
            )
        );

        $qr_background_color = sanitize_hex_color(
            wp_unslash(
                $_POST['qr_background_color']
                    ?? '#FFFFFF'
            )
        );

        $qr_border_color = sanitize_hex_color(
            wp_unslash(
                $_POST['qr_border_color']
                    ?? '#000000'
            )
        );

        $qr_border_width = isset($_POST['qr_border_width'])
            ? max(
                0,
                floatval($_POST['qr_border_width'])
            )
            : 0;

        $qr_border_radius = isset($_POST['qr_border_radius'])
            ? max(
                0,
                floatval($_POST['qr_border_radius'])
            )
            : 0;

        if (!$font_color) {
            $font_color = '#000000';
        }

        if (!$qr_foreground_color) {
            $qr_foreground_color = '#000000';
        }

        if (!$qr_background_color) {
            $qr_background_color = '#FFFFFF';
        }

        if (!$qr_border_color) {
            $qr_border_color = '#000000';
        }

        $allowed_alignments = ['left', 'center', 'right'];

        if (!in_array($alignment, $allowed_alignments, true)) {
            $alignment = 'left';
        }

        global $wpdb;

        $templates_table = $wpdb->prefix . 'ecm_templates';
        $elements_table  = $wpdb->prefix . 'ecm_template_elements';

        /*
        * Confirm that the element belongs to the requested template
        * and that the template belongs to the requested event.
        */
        $element = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT e.id, e.element_type
             FROM {$elements_table} e
             INNER JOIN {$templates_table} t
                ON e.template_id = t.id
             WHERE e.id = %d
               AND e.template_id = %d
               AND t.event_id = %d",
                $element_id,
                $template_id,
                $event_id
            )
        );

        if (!$element) {
            wp_send_json_error([
                'message' => 'Template element not found.',
            ], 404);
        }

        $update_data = [
            'font_family' => $font_family,
            'font_size'   => $font_size,
            'font_color'  => $font_color,
            'alignment'   => $alignment,
            'x_position'  => $x_position,
            'y_position'  => $y_position,
            'rotation'    => $rotation,
        ];

        $update_format = [
            '%s',
            '%f',
            '%s',
            '%s',
            '%f',
            '%f',
            '%f',
        ];

        if (
            strtolower((string) $element->element_type)
            === 'qr'
        ) {
            $update_data['width'] =
                $width !== null
                ? $width
                : 120;

            $update_data['height'] =
                $height !== null
                ? $height
                : 120;

            $update_data['qr_foreground_color'] =
                $qr_foreground_color;

            $update_data['qr_background_color'] =
                $qr_background_color;

            $update_data['qr_border_color'] =
                $qr_border_color;

            $update_data['qr_border_width'] =
                $qr_border_width;

            $update_data['qr_border_radius'] =
                $qr_border_radius;

            $update_format[] = '%f';
            $update_format[] = '%f';
            $update_format[] = '%s';
            $update_format[] = '%s';
            $update_format[] = '%s';
            $update_format[] = '%f';
            $update_format[] = '%f';
        }

        $updated = $wpdb->update(
            $elements_table,
            $update_data,
            [
                'id'          => $element_id,
                'template_id' => $template_id,
            ],
            $update_format,
            [
                '%d',
                '%d',
            ]
        );

        if ($updated === false) {
            wp_send_json_error([
                'message' => $wpdb->last_error
                    ? $wpdb->last_error
                    : 'Failed to save element properties.',
            ], 500);
        }

        wp_send_json_success([
            'message' => 'Element properties saved.',
            'element' => [
                'id'          => $element_id,
                'font_family' => $font_family,
                'font_size'   => $font_size,
                'font_color'  => $font_color,
                'alignment'   => $alignment,
                'x_position'  => $x_position,
                'y_position'  => $y_position,
                'rotation'    => $rotation,
                'width'               => $width,
                'height'              => $height,
                'qr_foreground_color' => $qr_foreground_color,
                'qr_background_color' => $qr_background_color,
                'qr_border_color'     => $qr_border_color,
                'qr_border_width'     => $qr_border_width,
                'qr_border_radius'    => $qr_border_radius,
            ],
        ]);
    }
}
