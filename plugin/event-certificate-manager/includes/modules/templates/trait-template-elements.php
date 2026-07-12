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
                    <?php wp_nonce_field('ecm_update_template_element', 'ecm_update_template_element_nonce'); ?>

                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                    <input type="hidden" name="template_id" value="<?php echo esc_attr($template->id); ?>">
                    <input type="hidden" name="element_id" id="ecm_element_id" value="">

                    <div class="ecm-modal-body">
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

                        <button type="submit" name="ecm_update_template_element_submit" id="ecm_update_template_element_submit" class="button button-primary" style="display:none;">
                            Update Element
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
        if (isset($_POST['ecm_update_template_element_submit'])) {
            return;
        }

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

        $event_id       = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $template_id    = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        $placeholder    = sanitize_text_field($_POST['placeholder_key'] ?? '');
        $source_type    = sanitize_text_field($_POST['source_type'] ?? 'participant');
        $font_family    = sanitize_text_field($_POST['font_family'] ?? 'Arial');
        $font_size      = isset($_POST['font_size']) ? absint($_POST['font_size']) : 18;
        $font_color     = sanitize_hex_color($_POST['font_color'] ?? '#000000');
        $alignment      = sanitize_text_field($_POST['alignment'] ?? 'left');
        $x_position     = isset($_POST['x_position']) ? floatval($_POST['x_position']) : 0;
        $y_position     = isset($_POST['y_position']) ? floatval($_POST['y_position']) : 0;
        $rotation       = isset($_POST['rotation']) ? floatval($_POST['rotation']) : 0;

        if (!$event_id || !$template_id || empty($placeholder)) {
            wp_die('Invalid element data.');
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
                'placeholder_key' => $placeholder,
                'source_type'     => $source_type,
                'x_position'      => $x_position,
                'y_position'      => $y_position,
                'font_family'     => $font_family,
                'font_size'       => $font_size,
                'font_color'      => $font_color,
                'alignment'       => $alignment,
                'rotation'        => $rotation,
                'element_order'   => $max_order + 1,
            ],
            ['%d', '%s', '%s', '%f', '%f', '%s', '%d', '%s', '%s', '%f', '%d']
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

    public function handle_update_template_element()
    {
        if (!isset($_POST['ecm_update_template_element_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_update_template_element_nonce']) ||
            !wp_verify_nonce($_POST['ecm_update_template_element_nonce'], 'ecm_update_template_element')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id       = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $template_id    = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        $element_id     = isset($_POST['element_id']) ? absint($_POST['element_id']) : 0;
        $placeholder    = sanitize_text_field($_POST['placeholder_key'] ?? '');
        $source_type    = sanitize_text_field($_POST['source_type'] ?? 'participant');
        $font_family    = sanitize_text_field($_POST['font_family'] ?? 'Arial');
        $font_size      = isset($_POST['font_size']) ? absint($_POST['font_size']) : 18;
        $font_color     = sanitize_hex_color($_POST['font_color'] ?? '#000000');
        $alignment      = sanitize_text_field($_POST['alignment'] ?? 'left');
        $x_position     = isset($_POST['x_position']) ? floatval($_POST['x_position']) : 0;
        $y_position     = isset($_POST['y_position']) ? floatval($_POST['y_position']) : 0;
        $rotation       = isset($_POST['rotation']) ? floatval($_POST['rotation']) : 0;

        if (!$event_id || !$template_id || !$element_id || empty($placeholder)) {
            wp_die('Invalid element data.');
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

        $updated = $wpdb->update(
            $elements_table,
            [
                'placeholder_key' => $placeholder,
                'source_type'     => $source_type,
                'x_position'      => $x_position,
                'y_position'      => $y_position,
                'font_family'     => $font_family,
                'font_size'       => $font_size,
                'font_color'      => $font_color,
                'alignment'       => $alignment,
                'rotation'        => $rotation,
            ],
            [
                'id'          => $element_id,
                'template_id' => $template_id,
            ],
            ['%s', '%s', '%f', '%f', '%s', '%f', '%s', '%s', '%f'],
            ['%d', '%d']
        );

        if ($updated === false) {
            wp_die('Failed to update template element.');
        }

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events&action=template_builder&event_id=' . $event_id .
                    '&template_id=' . $template_id .
                    '&element_updated=1'
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

        if (!$font_color) {
            $font_color = '#000000';
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
        $element_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT e.id
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

        if (!$element_exists) {
            wp_send_json_error([
                'message' => 'Template element not found.',
            ], 404);
        }

        $updated = $wpdb->update(
            $elements_table,
            [
                'font_family' => $font_family,
                'font_size'   => $font_size,
                'font_color'  => $font_color,
                'alignment'   => $alignment,
                'x_position'  => $x_position,
                'y_position'  => $y_position,
                'rotation'    => $rotation,
            ],
            [
                'id'          => $element_id,
                'template_id' => $template_id,
            ],
            [
                '%s',
                '%f',
                '%s',
                '%s',
                '%f',
                '%f',
                '%f',
            ],
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
            ],
        ]);
    }
}
