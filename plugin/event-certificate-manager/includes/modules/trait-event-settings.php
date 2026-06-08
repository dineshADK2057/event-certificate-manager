<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Settings {

   private function tab_settings($event)
    {
    ?>
        <div class="ecm-tab-header">
            <div>
                <h2>Event Settings</h2>
                <p>Configure participant fields and event-specific rules.</p>
            </div>
        </div>

        <?php if (isset($_GET['fields_added'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Default participant fields added.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['field_added'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Custom participant field added.</strong></p>
            </div>
        <?php endif; ?>

        <?php $this->render_participant_fields_section($event); ?>
    <?php
    }

    private function render_participant_fields_section($event)
    {
        global $wpdb;

        $fields_table = $wpdb->prefix . 'ecm_event_fields';

        $fields = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $fields_table WHERE event_id = %d ORDER BY field_order ASC, id ASC",
                $event->id
            )
        );

    ?>
        <div class="ecm-panel ecm-panel-full">
            <h3>Participant Fields</h3>
            <p class="description">
                These fields define what participant data this event requires. They will be used for manual entry, CSV import, and certificate placeholders.
            </p>

            <?php if (empty($fields)) : ?>
                <div class="notice notice-info inline">
                    <p>No participant fields found. Add the default fields first.</p>
                </div>

                <form method="post">
                    <?php wp_nonce_field('ecm_add_default_fields', 'ecm_default_fields_nonce'); ?>
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                    <button type="submit" name="ecm_add_default_fields_submit" class="button button-primary">
                        Add Default Fields
                    </button>
                </form>
            <?php else : ?>

                <table class="widefat striped ecm-fields-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Key</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $field) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($field->field_label); ?></strong></td>
                                <td><code><?php echo esc_html($field->field_key); ?></code></td>
                                <td><?php echo esc_html($field->field_type); ?></td>
                                <td><?php echo $field->is_required ? 'Yes' : 'No'; ?></td>
                                <td><?php echo esc_html($field->field_order); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

            <hr>

            <h3>Add Custom Field</h3>

            <form method="post" class="ecm-inline-form">
                <?php wp_nonce_field('ecm_add_custom_field', 'ecm_custom_field_nonce'); ?>
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">

                <p>
                    <label>
                        Field Label<br>
                        <input type="text" name="field_label" class="regular-text" placeholder="Example: Cluster, District, Country">
                    </label>
                </p>

                <p>
                    <label>
                        Field Type<br>
                        <select name="field_type">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="email">Email</option>
                            <option value="select">Select</option>
                        </select>
                    </label>
                </p>

                <p>
                    <label>
                        <input type="checkbox" name="is_required" value="1">
                        Required field
                    </label>
                </p>

                <p>
                    <button type="submit" name="ecm_add_custom_field_submit" class="button button-primary">
                        Add Field
                    </button>
                </p>
            </form>
        </div>
    <?php
    }

    private function create_field_key($label)
    {
        $key = strtolower($label);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_');

        return $key ?: 'custom_field';
    }

    public function handle_add_default_fields()
    {
        if (!isset($_POST['ecm_add_default_fields_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_default_fields_nonce']) ||
            !wp_verify_nonce($_POST['ecm_default_fields_nonce'], 'ecm_add_default_fields')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

        if (!$event_id) {
            wp_die('Invalid event.');
        }

        global $wpdb;
        $fields_table = $wpdb->prefix . 'ecm_event_fields';

        $defaults = [
            [
                'field_key'   => 'member_id',
                'field_label' => 'Member ID',
                'field_type'  => 'number',
                'is_required' => 1,
                'field_order' => 1,
            ],
            [
                'field_key'   => 'member_name',
                'field_label' => 'Member Name',
                'field_type'  => 'text',
                'is_required' => 1,
                'field_order' => 2,
            ],
            [
                'field_key'   => 'home_club',
                'field_label' => 'Home Club',
                'field_type'  => 'text',
                'is_required' => 1,
                'field_order' => 3,
            ],
        ];

        foreach ($defaults as $field) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $fields_table WHERE event_id = %d AND field_key = %s",
                    $event_id,
                    $field['field_key']
                )
            );

            if ($exists) {
                continue;
            }

            $wpdb->insert(
                $fields_table,
                [
                    'event_id'    => $event_id,
                    'field_key'   => $field['field_key'],
                    'field_label' => $field['field_label'],
                    'field_type'  => $field['field_type'],
                    'is_required' => $field['is_required'],
                    'field_order' => $field['field_order'],
                ],
                ['%d', '%s', '%s', '%s', '%d', '%d']
            );
        }

        wp_safe_redirect(admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=settings&fields_added=1'));
        exit;
    }

    public function handle_add_custom_field()
    {
        if (!isset($_POST['ecm_add_custom_field_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_custom_field_nonce']) ||
            !wp_verify_nonce($_POST['ecm_custom_field_nonce'], 'ecm_add_custom_field')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id    = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $field_label = sanitize_text_field($_POST['field_label'] ?? '');
        $field_type  = sanitize_text_field($_POST['field_type'] ?? 'text');
        $is_required = isset($_POST['is_required']) ? 1 : 0;

        if (!$event_id || empty($field_label)) {
            wp_die('Field label is required.');
        }

        $allowed_types = ['text', 'number', 'email', 'select'];

        if (!in_array($field_type, $allowed_types, true)) {
            $field_type = 'text';
        }

        global $wpdb;
        $fields_table = $wpdb->prefix . 'ecm_event_fields';

        $field_key = $this->create_field_key($field_label);

        $original_key = $field_key;
        $counter = 2;

        while ($wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $fields_table WHERE event_id = %d AND field_key = %s",
                $event_id,
                $field_key
            )
        )) {
            $field_key = $original_key . '_' . $counter;
            $counter++;
        }

        $max_order = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(field_order) FROM $fields_table WHERE event_id = %d",
                $event_id
            )
        );

        $wpdb->insert(
            $fields_table,
            [
                'event_id'    => $event_id,
                'field_key'   => $field_key,
                'field_label' => $field_label,
                'field_type'  => $field_type,
                'is_required' => $is_required,
                'field_order' => $max_order + 1,
            ],
            ['%d', '%s', '%s', '%s', '%d', '%d']
        );

        wp_safe_redirect(admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=settings&field_added=1'));
        exit;
    }



}