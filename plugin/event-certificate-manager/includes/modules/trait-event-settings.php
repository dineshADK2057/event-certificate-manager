<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Settings
{

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

        <?php if (isset($_GET['field_updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Participant field updated successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['field_deleted'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Participant field deleted successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['session_settings_saved'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Session settings saved successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php $this->render_participant_fields_section($event); ?>
        <?php $this->render_event_session_settings_section($event); ?>
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
                        <th width="130">Actions</th>
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
                            <?php
                            $delete_field_url = wp_nonce_url(
                                admin_url(
                                    'admin.php?page=ecm-events&action=delete_custom_field&event_id=' . absint($event->id) . '&field_id=' . absint($field->id)
                                ),
                                'ecm_delete_custom_field_' . absint($field->id)
                            );
                            ?>
                            <td>
                                <a href="#"
                                    class="ecm-edit-field"
                                    data-field-id="<?php echo esc_attr($field->id); ?>"
                                    data-field-label="<?php echo esc_attr($field->field_label); ?>"
                                    data-field-type="<?php echo esc_attr($field->field_type); ?>"
                                    data-is-required="<?php echo esc_attr($field->is_required); ?>"
                                    data-field-order="<?php echo esc_attr($field->field_order); ?>">
                                    Edit
                                </a>

                                <?php if (!in_array($field->field_key, ['member_id', 'member_name', 'home_club'], true)) : ?>
                                    |
                                    <a href="<?php echo esc_url($delete_field_url); ?>"
                                        onclick="return confirm('Delete this participant field? Existing participant data for this field may remain but will no longer display.');"
                                        class="ecm-danger-link">
                                        Delete
                                    </a>
                                <?php endif; ?>

                                <?php $this->render_edit_field_modal($event); ?>
                            </td>
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

            <div class="ecm-participants-fields-form">
                <label>
                    Field Label
                    <input type="text" name="field_label" class="regular-text" placeholder="Example: Cluster, District, Country">
                </label>

                <label>
                    Field Type
                    <select name="field_type">
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="email">Email</option>
                        <option value="select">Select</option>
                    </select>
                </label>
            </div>

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

    private function render_edit_field_modal($event)
    {
    ?>
        <div id="ecm-edit-field-modal" class="ecm-modal" style="display:none;">
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2>Edit Participant Field</h2>
                    <button type="button" class="ecm-modal-close">&times;</button>
                </div>

                <form method="post">
                    <?php wp_nonce_field('ecm_update_custom_field', 'ecm_update_custom_field_nonce'); ?>

                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                    <input type="hidden" name="field_id" id="ecm_edit_field_id" value="">

                    <div class="ecm-modal-body">
                        <p>
                            <label>
                                <strong>Field Label</strong>
                                <input type="text" name="field_label" id="ecm_edit_field_label" class="widefat" required>
                            </label>
                        </p>

                        <p>
                            <label>
                                <strong>Field Type</strong>
                                <select name="field_type" id="ecm_edit_field_type" class="widefat">
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="email">Email</option>
                                    <option value="select">Select</option>
                                </select>
                            </label>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" name="is_required" id="ecm_edit_is_required" value="1">
                                Required field
                            </label>
                        </p>

                        <p>
                            <label>
                                <strong>Order</strong>
                                <input type="number" name="field_order" id="ecm_edit_field_order" class="small-text" min="0">
                            </label>
                        </p>
                    </div>

                    <div class="ecm-modal-footer">
                        <button type="submit" name="ecm_update_custom_field_submit" class="button button-primary">
                            Update Field
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

    public function handle_update_custom_field()
    {
        if (!isset($_POST['ecm_update_custom_field_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_update_custom_field_nonce']) ||
            !wp_verify_nonce($_POST['ecm_update_custom_field_nonce'], 'ecm_update_custom_field')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id    = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $field_id    = isset($_POST['field_id']) ? absint($_POST['field_id']) : 0;
        $field_label = sanitize_text_field($_POST['field_label'] ?? '');
        $field_type  = sanitize_text_field($_POST['field_type'] ?? 'text');
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $field_order = isset($_POST['field_order']) ? absint($_POST['field_order']) : 0;

        if (!$event_id || !$field_id || empty($field_label)) {
            wp_die('Invalid field data.');
        }

        $allowed_types = ['text', 'number', 'email', 'select'];

        if (!in_array($field_type, $allowed_types, true)) {
            $field_type = 'text';
        }

        global $wpdb;

        $fields_table = $wpdb->prefix . 'ecm_event_fields';

        $field = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $fields_table WHERE id = %d AND event_id = %d",
                $field_id,
                $event_id
            )
        );

        if (!$field) {
            wp_die('Field not found.');
        }

        $wpdb->update(
            $fields_table,
            [
                'field_label' => $field_label,
                'field_type'  => $field_type,
                'is_required' => $is_required,
                'field_order' => $field_order,
            ],
            [
                'id'       => $field_id,
                'event_id' => $event_id,
            ],
            ['%s', '%s', '%d', '%d'],
            ['%d', '%d']
        );

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=settings&field_updated=1')
        );
        exit;
    }

    public function handle_delete_custom_field()
    {
        if (
            !isset($_GET['page'], $_GET['action'], $_GET['event_id'], $_GET['field_id']) ||
            $_GET['page'] !== 'ecm-events' ||
            $_GET['action'] !== 'delete_custom_field'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id = absint($_GET['event_id']);
        $field_id = absint($_GET['field_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'ecm_delete_custom_field_' . $field_id)
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $fields_table = $wpdb->prefix . 'ecm_event_fields';

        $field = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $fields_table WHERE id = %d AND event_id = %d",
                $field_id,
                $event_id
            )
        );

        if (!$field) {
            wp_die('Field not found.');
        }

        if (in_array($field->field_key, ['member_id', 'member_name', 'home_club'], true)) {
            wp_die('Default fields cannot be deleted.');
        }

        $wpdb->delete(
            $fields_table,
            [
                'id'       => $field_id,
                'event_id' => $event_id,
            ],
            ['%d', '%d']
        );

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=settings&field_deleted=1')
        );
        exit;
    }

    private function render_session_settings_panel($event, $session)
    {
        $settings = get_option('ecm_session_settings_' . $session->id, []);

        $certificate_enabled = isset($settings['certificate_enabled']) ? (int) $settings['certificate_enabled'] : 0;
        $qr_enabled          = isset($settings['qr_enabled']) ? (int) $settings['qr_enabled'] : 1;
        $capacity            = isset($settings['capacity']) ? absint($settings['capacity']) : '';
    ?>
        <div class="ecm-panel ecm-panel-full">
            <h3>Session Settings</h3>
            <p class="description">Configure certificate and verification settings for this session.</p>

            <form method="post">
                <?php wp_nonce_field('ecm_save_session_settings', 'ecm_session_settings_nonce'); ?>

                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                <input type="hidden" name="session_id" value="<?php echo esc_attr($session->id); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row">Certificate Generation</th>
                        <td>
                            <label>
                                <input type="checkbox" name="certificate_enabled" value="1" <?php checked($certificate_enabled, 1); ?>>
                                Enable certificate generation for this session
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">QR Verification</th>
                        <td>
                            <label>
                                <input type="checkbox" name="qr_enabled" value="1" <?php checked($qr_enabled, 1); ?>>
                                Enable QR verification for certificates generated from this session
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="session_capacity">Session Capacity</label>
                        </th>
                        <td>
                            <input type="number"
                                id="session_capacity"
                                name="capacity"
                                value="<?php echo esc_attr($capacity); ?>"
                                min="0"
                                class="small-text">
                            <p class="description">Optional. Leave empty or 0 for unlimited capacity.</p>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="ecm_save_session_settings_submit" class="button button-primary">
                        Save Session Settings
                    </button>
                </p>
            </form>
        </div>
    <?php
    }

    public function handle_save_session_settings()
    {
        if (!isset($_POST['ecm_save_session_settings_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_session_settings_nonce']) ||
            !wp_verify_nonce($_POST['ecm_session_settings_nonce'], 'ecm_save_session_settings')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id   = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;

        if (!$event_id || !$session_id) {
            wp_die('Invalid session.');
        }

        $settings = [
            'certificate_enabled' => isset($_POST['certificate_enabled']) ? 1 : 0,
            'qr_enabled'          => isset($_POST['qr_enabled']) ? 1 : 0,
            'capacity'            => isset($_POST['capacity']) ? absint($_POST['capacity']) : 0,
        ];

        update_option('ecm_session_settings_' . $session_id, $settings, false);

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events&action=manage&event_id=' . $event_id .
                    '&tab=settings&settings_session_id=' . $session_id .
                    '&session_settings_saved=1'
            )
        );
        exit;
    }
    private function render_event_session_settings_section($event)
    {
        global $wpdb;

        $sessions_table = $wpdb->prefix . 'ecm_sessions';

        $sessions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $sessions_table WHERE event_id = %d ORDER BY id DESC",
                $event->id
            )
        );
    ?>
        <div class="ecm-panel ecm-panel-full">
            <h3>Session Settings</h3>
            <p class="description">Configure certificate, QR verification, and capacity settings for each session.</p>

            <?php if (empty($sessions)) : ?>
                <p>No sessions found. Please create sessions first.</p>
            <?php else : ?>
                <form method="get" style="margin-bottom: 16px;">
                    <input type="hidden" name="page" value="ecm-events">
                    <input type="hidden" name="action" value="manage">
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                    <input type="hidden" name="tab" value="settings">

                    <?php
                    $selected_session_id = isset($_GET['settings_session_id'])
                        ? absint($_GET['settings_session_id'])
                        : (int) $sessions[0]->id;
                    ?>

                    <label>
                        <strong>Select Session</strong><br>
                        <select name="settings_session_id">
                            <?php foreach ($sessions as $session) : ?>
                                <option value="<?php echo esc_attr($session->id); ?>" <?php selected($selected_session_id, $session->id); ?>>
                                    <?php echo esc_html($session->session_code . ' - ' . $session->session_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <button type="submit" class="button">Load Settings</button>
                </form>

                <?php
                $selected_session = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $sessions_table WHERE id = %d AND event_id = %d",
                        $selected_session_id,
                        $event->id
                    )
                );

                if ($selected_session) {
                    $this->render_session_settings_panel($event, $selected_session);
                }
                ?>
            <?php endif; ?>
        </div>
<?php
    }
}
