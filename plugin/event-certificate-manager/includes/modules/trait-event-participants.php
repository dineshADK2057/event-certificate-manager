<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Participants
{

    private function tab_participants($event)
    {
        ?>
        <div class="ecm-tab-header">
            <div>
                <h2>Participants</h2>
                <p>View, search, and manage participants for this event.</p>
            </div>

            <div class="ecm-tab-actions">
                <button type="button" class="button button-primary ecm-open-participant-modal">
                    + Add Participant
                </button>

                <button type="button" class="button ecm-open-csv-modal">
                    Upload CSV
                </button>
                <a href="<?php echo esc_url(
                                wp_nonce_url(
                                    admin_url('admin.php?page=ecm-events&action=download_sample_csv&event_id=' . absint($event->id)),
                                    'ecm_download_sample_csv_' . absint($event->id)
                                )
                            ); ?>" class="button">
                    Download Sample CSV
                </a>
                <a href="<?php echo esc_url(
                                wp_nonce_url(
                                    admin_url('admin.php?page=ecm-events&action=export_participants_csv&event_id=' . absint($event->id)),
                                    'ecm_export_participants_csv_' . absint($event->id)
                                )
                            ); ?>" class="button">
                    Export CSV
                </a>
            </div>
        </div>

        <?php if (isset($_GET['participant_added'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Participant added successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['participant_updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Participant updated successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['participant_deleted'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Participant deleted successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['bulk_deleted'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Selected participants deleted successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['bulk_no_selection'])) : ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Please select at least one participant.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['bulk_empty_action'])) : ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Please choose a bulk action.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['csv_imported'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>CSV import completed.</strong>
                    Inserted: <?php echo esc_html(absint($_GET['inserted'] ?? 0)); ?>,
                    Skipped: <?php echo esc_html(absint($_GET['skipped'] ?? 0)); ?>.
                </p>
            </div>
        <?php endif; ?>

        <?php $this->render_participant_toolbar($event); ?>
        <?php $this->render_participant_list_section($event); ?>
        <?php $this->render_add_participant_modal($event); ?>
        <?php $this->render_csv_upload_modal($event); ?>
    <?php
    }

    private function render_participant_toolbar($event)
    {
        $search = isset($_GET['participant_search']) ? sanitize_text_field($_GET['participant_search']) : '';
    ?>
        <div class="ecm-list-toolbar">

            <form method="post" class="ecm-list-toolbar-left" id="ecm-bulk-participant-form">
                <?php wp_nonce_field('ecm_bulk_participant_action', 'ecm_bulk_participant_nonce'); ?>

                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">

                <select name="bulk_action">
                    <option value="">Bulk actions</option>
                    <option value="delete">Delete</option>
                </select>

                <button type="submit" name="ecm_bulk_participant_submit" class="button">
                    Apply
                </button>
            </form>

            <form method="get" class="ecm-list-toolbar-right">
                <input type="hidden" name="page" value="ecm-events">
                <input type="hidden" name="action" value="manage">
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                <input type="hidden" name="tab" value="participants">

                <input
                    type="search"
                    name="participant_search"
                    value="<?php echo esc_attr($search); ?>"
                    placeholder="Search participants..."
                    class="regular-text">

                <button type="submit" class="button">Search</button>

                <?php if (!empty($search)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ecm-events&action=manage&event_id=' . absint($event->id) . '&tab=participants')); ?>" class="button">
                        Clear
                    </a>
                <?php endif; ?>
            </form>

        </div>
    <?php
    }

    private function render_participant_list_section($event)
    {
        global $wpdb;

        $participants_table = $wpdb->prefix . 'ecm_participants';
        $meta_table         = $wpdb->prefix . 'ecm_participant_meta';

        $fields = $this->get_event_fields($event->id);

        if (empty($fields)) {
            return;
        }

        $search = isset($_GET['participant_search']) ? sanitize_text_field($_GET['participant_search']) : '';

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';

            $participants = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT p.*
                FROM $participants_table p
                LEFT JOIN $meta_table m ON p.id = m.participant_id
                WHERE p.event_id = %d
                AND (
                    p.member_id LIKE %s
                    OR m.meta_value LIKE %s
                )
                ORDER BY p.id DESC",
                    $event->id,
                    $like,
                    $like
                )
            );
        } else {
            $participants = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $participants_table WHERE event_id = %d ORDER BY id DESC",
                    $event->id
                )
            );
        }
    ?>

            <?php if (empty($participants)) : ?>
                <p>No participants added yet.</p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th width="35">
                                <input type="checkbox" id="ecm-select-all-participants">
                            </th>
                            <?php foreach ($fields as $field) : ?>
                                <th><?php echo esc_html($field->field_label); ?></th>
                            <?php endforeach; ?>
                            <th>Created</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $participant) : ?>
                            <?php
                            $meta_rows = $wpdb->get_results(
                                $wpdb->prepare(
                                    "SELECT meta_key, meta_value FROM $meta_table WHERE participant_id = %d",
                                    $participant->id
                                ),
                                OBJECT_K
                            );
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox"
                                        name="participant_ids[]"
                                        value="<?php echo esc_attr($participant->id); ?>"
                                        class="ecm-participant-checkbox"
                                        form="ecm-bulk-participant-form">
                                </td>
                                <?php foreach ($fields as $field) : ?>
                                    <?php
                                    if ($field->field_key === 'member_id') {
                                        $value = $participant->member_id;
                                    } else {
                                        $value = isset($meta_rows[$field->field_key])
                                            ? $meta_rows[$field->field_key]->meta_value
                                            : '';
                                    }
                                    ?>
                                    <td><?php echo esc_html($value); ?></td>
                                <?php endforeach; ?>

                                <td><?php echo esc_html($participant->created_at); ?></td>
                                <?php
                                $delete_url = wp_nonce_url(
                                    admin_url(
                                        'admin.php?page=ecm-events&action=delete_participant&event_id=' . absint($event->id) . '&participant_id=' . absint($participant->id)
                                    ),
                                    'ecm_delete_participant_' . absint($participant->id)
                                );
                                ?>
                                <td>
                                    <a href="#"
                                        class="ecm-edit-participant"
                                        data-participant-id="<?php echo esc_attr($participant->id); ?>"
                                        <?php foreach ($fields as $field) : ?>
                                        <?php
                                            if ($field->field_key === 'member_id') {
                                                $data_value = $participant->member_id;
                                            } else {
                                                $data_value = isset($meta_rows[$field->field_key])
                                                    ? $meta_rows[$field->field_key]->meta_value
                                                    : '';
                                            }
                                        ?>
                                        data-<?php echo esc_attr($field->field_key); ?>="<?php echo esc_attr($data_value); ?>"
                                        <?php endforeach; ?>>
                                        Edit
                                    </a>
                                    |
                                    <a href="<?php echo esc_url($delete_url); ?>"
                                        onclick="return confirm('Are you sure you want to delete this participant?');"
                                        class="ecm-danger-link">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        
    <?php
    }

    private function render_add_participant_modal($event)
    {
        $fields = $this->get_event_fields($event->id);

        if (empty($fields)) {
            return;
        }
    ?>
        <div id="ecm-add-participant-modal" class="ecm-modal" style="display:none;">
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2 id="ecm-participant-modal-title">Add Participant</h2>
                    <button type="button" class="ecm-modal-close">&times;</button>
                </div>

                <form method="post" id="ecm-participant-form">
                    <?php wp_nonce_field('ecm_add_participant', 'ecm_add_participant_nonce'); ?>
                    <?php wp_nonce_field('ecm_update_participant', 'ecm_update_participant_nonce'); ?>

                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                    <input type="hidden" name="participant_id" id="ecm_participant_id" value="">
                    <input type="hidden" name="ecm_form_mode" id="ecm_form_mode" value="add">

                    <div class="ecm-modal-body">
                        <?php foreach ($fields as $field) : ?>
                            <p>
                                <label for="ecm_modal_field_<?php echo esc_attr($field->field_key); ?>">
                                    <strong><?php echo esc_html($field->field_label); ?></strong>
                                    <?php if ((int) $field->is_required === 1) : ?>
                                        <span class="description">(required)</span>
                                    <?php endif; ?>
                                </label>

                                <input
                                    type="<?php echo $field->field_type === 'number' ? 'number' : 'text'; ?>"
                                    id="ecm_modal_field_<?php echo esc_attr($field->field_key); ?>"
                                    data-field-key="<?php echo esc_attr($field->field_key); ?>"
                                    name="participant_fields[<?php echo esc_attr($field->field_key); ?>]"
                                    class="widefat ecm-participant-field"
                                    <?php echo (int) $field->is_required === 1 ? 'required' : ''; ?>>
                            </p>
                        <?php endforeach; ?>
                    </div>

                    <div class="ecm-modal-footer">
                        <button type="submit" name="ecm_add_participant_submit" id="ecm_add_participant_submit" class="button button-primary">
                            Save Participant
                        </button>

                        <button type="submit" name="ecm_update_participant_submit" id="ecm_update_participant_submit" class="button button-primary" style="display:none;">
                            Update Participant
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

    private function render_csv_upload_modal($event)
    {
        $fields = $this->get_event_fields($event->id);

        if (empty($fields)) {
            return;
        }

        $headers = [];

        foreach ($fields as $field) {
            $headers[] = $field->field_key;
        }

        $csv_format = implode(',', $headers);
    ?>
        <div id="ecm-csv-upload-modal" class="ecm-modal" style="display:none;">
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2>Upload Participants CSV</h2>
                    <button type="button" class="ecm-modal-close">&times;</button>
                </div>

                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('ecm_import_participants_csv', 'ecm_import_csv_nonce'); ?>
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">

                    <div class="ecm-modal-body">
                        <p><strong>Required CSV header:</strong></p>
                        <pre class="ecm-code-preview"><?php echo esc_html($csv_format); ?></pre>

                        <p class="description">
                            Your CSV first row must match this header exactly. Member ID must be numeric.
                        </p>

                        <p>
                            <input type="file" name="participants_csv" accept=".csv" required>
                        </p>
                    </div>

                    <div class="ecm-modal-footer">
                        <button type="submit" name="ecm_import_csv_submit" class="button button-primary">
                            Upload CSV
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

    public function handle_add_participant()
    {
        if (!isset($_POST['ecm_add_participant_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_add_participant_nonce']) ||
            !wp_verify_nonce($_POST['ecm_add_participant_nonce'], 'ecm_add_participant')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $submitted_fields = isset($_POST['participant_fields']) && is_array($_POST['participant_fields'])
            ? wp_unslash($_POST['participant_fields'])
            : [];

        if (!$event_id) {
            wp_die('Invalid event.');
        }

        $fields = $this->get_event_fields($event_id);

        if (empty($fields)) {
            wp_die('Participant fields are not configured for this event.');
        }

        $clean_data = [];

        foreach ($fields as $field) {
            $key = $field->field_key;
            $value = isset($submitted_fields[$key])
                ? sanitize_text_field($submitted_fields[$key])
                : '';

            if ((int) $field->is_required === 1 && $value === '') {
                wp_die(esc_html($field->field_label) . ' is required.');
            }

            if ($field->field_type === 'number' && $value !== '' && !ctype_digit($value)) {
                wp_die(esc_html($field->field_label) . ' must contain numbers only.');
            }

            $clean_data[$key] = $value;
        }

        if (empty($clean_data['member_id'])) {
            wp_die('Member ID is required.');
        }

        global $wpdb;

        $participants_table = $wpdb->prefix . 'ecm_participants';
        $meta_table         = $wpdb->prefix . 'ecm_participant_meta';

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $participants_table WHERE event_id = %d AND member_id = %s",
                $event_id,
                $clean_data['member_id']
            )
        );

        if ($exists) {
            wp_die('This Member ID already exists for this event.');
        }

        $inserted = $wpdb->insert(
            $participants_table,
            [
                'event_id'  => $event_id,
                'member_id' => $clean_data['member_id'],
            ],
            ['%d', '%s']
        );

        if (!$inserted) {
            wp_die('Failed to add participant.');
        }

        $participant_id = (int) $wpdb->insert_id;

        foreach ($clean_data as $key => $value) {
            if ($key === 'member_id') {
                continue;
            }

            $wpdb->insert(
                $meta_table,
                [
                    'participant_id' => $participant_id,
                    'meta_key'       => $key,
                    'meta_value'     => $value,
                ],
                ['%d', '%s', '%s']
            );
        }

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=participants&participant_added=1')
        );
        exit;
    }

    public function handle_update_participant()
    {
        if (!isset($_POST['ecm_update_participant_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_update_participant_nonce']) ||
            !wp_verify_nonce($_POST['ecm_update_participant_nonce'], 'ecm_update_participant')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $participant_id = isset($_POST['participant_id']) ? absint($_POST['participant_id']) : 0;

        $submitted_fields = isset($_POST['participant_fields']) && is_array($_POST['participant_fields'])
            ? wp_unslash($_POST['participant_fields'])
            : [];

        if (!$event_id || !$participant_id) {
            wp_die('Invalid participant.');
        }

        $fields = $this->get_event_fields($event_id);

        if (empty($fields)) {
            wp_die('Participant fields are not configured for this event.');
        }

        $clean_data = [];

        foreach ($fields as $field) {
            $key = $field->field_key;
            $value = isset($submitted_fields[$key])
                ? sanitize_text_field($submitted_fields[$key])
                : '';

            if ((int) $field->is_required === 1 && $value === '') {
                wp_die(esc_html($field->field_label) . ' is required.');
            }

            if ($field->field_type === 'number' && $value !== '' && !ctype_digit($value)) {
                wp_die(esc_html($field->field_label) . ' must contain numbers only.');
            }

            $clean_data[$key] = $value;
        }

        if (empty($clean_data['member_id'])) {
            wp_die('Member ID is required.');
        }

        global $wpdb;

        $participants_table = $wpdb->prefix . 'ecm_participants';
        $meta_table = $wpdb->prefix . 'ecm_participant_meta';

        $duplicate = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $participants_table WHERE event_id = %d AND member_id = %s AND id != %d",
                $event_id,
                $clean_data['member_id'],
                $participant_id
            )
        );

        if ($duplicate) {
            wp_die('This Member ID already exists for this event.');
        }

        $wpdb->update(
            $participants_table,
            [
                'member_id' => $clean_data['member_id'],
                'updated_at' => current_time('mysql'),
            ],
            [
                'id' => $participant_id,
                'event_id' => $event_id,
            ],
            ['%s', '%s'],
            ['%d', '%d']
        );

        foreach ($clean_data as $key => $value) {
            if ($key === 'member_id') {
                continue;
            }

            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $meta_table WHERE participant_id = %d AND meta_key = %s",
                    $participant_id,
                    $key
                )
            );

            if ($exists) {
                $wpdb->update(
                    $meta_table,
                    ['meta_value' => $value],
                    [
                        'participant_id' => $participant_id,
                        'meta_key' => $key,
                    ],
                    ['%s'],
                    ['%d', '%s']
                );
            } else {
                $wpdb->insert(
                    $meta_table,
                    [
                        'participant_id' => $participant_id,
                        'meta_key' => $key,
                        'meta_value' => $value,
                    ],
                    ['%d', '%s', '%s']
                );
            }
        }

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=participants&participant_updated=1')
        );
        exit;
    }

    public function handle_delete_participant()
    {
        if (
            !isset($_GET['page'], $_GET['action'], $_GET['event_id'], $_GET['participant_id']) ||
            $_GET['page'] !== 'ecm-events' ||
            $_GET['action'] !== 'delete_participant'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id = absint($_GET['event_id']);
        $participant_id = absint($_GET['participant_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'ecm_delete_participant_' . $participant_id)
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $participants_table = $wpdb->prefix . 'ecm_participants';
        $meta_table = $wpdb->prefix . 'ecm_participant_meta';

        $participant = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $participants_table WHERE id = %d AND event_id = %d",
                $participant_id,
                $event_id
            )
        );

        if (!$participant) {
            wp_die('Participant not found.');
        }

        $wpdb->delete(
            $meta_table,
            ['participant_id' => $participant_id],
            ['%d']
        );

        $wpdb->delete(
            $participants_table,
            ['id' => $participant_id],
            ['%d']
        );

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=participants&participant_deleted=1')
        );
        exit;
    }

    public function handle_bulk_participant_action()
    {
        if (!isset($_POST['ecm_bulk_participant_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_bulk_participant_nonce']) ||
            !wp_verify_nonce($_POST['ecm_bulk_participant_nonce'], 'ecm_bulk_participant_action')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $bulk_action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $participant_ids = isset($_POST['participant_ids']) && is_array($_POST['participant_ids'])
            ? array_map('absint', $_POST['participant_ids'])
            : [];

        if (!$event_id || empty($bulk_action) || empty($participant_ids)) {
            wp_safe_redirect(admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=participants&bulk_error=1'));
            exit;
        }

        if ($bulk_action !== 'delete') {
            wp_safe_redirect(admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=participants&bulk_error=1'));
            exit;
        }

        global $wpdb;

        $participants_table = $wpdb->prefix . 'ecm_participants';
        $meta_table = $wpdb->prefix . 'ecm_participant_meta';

        foreach ($participant_ids as $participant_id) {
            $wpdb->delete($meta_table, ['participant_id' => $participant_id], ['%d']);
            $wpdb->delete($participants_table, ['id' => $participant_id, 'event_id' => $event_id], ['%d', '%d']);
        }

        wp_safe_redirect(admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=participants&bulk_deleted=1'));
        exit;
    }

    public function handle_csv_import()
    {
        if (!isset($_POST['ecm_import_csv_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_import_csv_nonce']) ||
            !wp_verify_nonce($_POST['ecm_import_csv_nonce'], 'ecm_import_participants_csv')
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

        if (empty($_FILES['participants_csv']['tmp_name'])) {
            wp_die('No CSV file uploaded.');
        }

        $file_type = wp_check_filetype($_FILES['participants_csv']['name']);

        if ($file_type['ext'] !== 'csv') {
            wp_die('Invalid file type. Please upload a CSV file.');
        }

        $fields = $this->get_event_fields($event_id);

        if (empty($fields)) {
            wp_die('Participant fields are not configured for this event.');
        }

        $expected_headers = [];

        foreach ($fields as $field) {
            $expected_headers[] = $field->field_key;
        }

        $handle = fopen($_FILES['participants_csv']['tmp_name'], 'r');

        if (!$handle) {
            wp_die('Unable to read CSV file.');
        }

        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            wp_die('CSV header row is missing.');
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $header = array_map('trim', $header);

        if ($header !== $expected_headers) {
            fclose($handle);
            wp_die('Invalid CSV header. Required header: ' . esc_html(implode(',', $expected_headers)));
        }

        global $wpdb;

        $participants_table = $wpdb->prefix . 'ecm_participants';
        $meta_table         = $wpdb->prefix . 'ecm_participant_meta';

        $inserted = 0;
        $skipped  = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < count($expected_headers)) {
                $skipped++;
                continue;
            }

            $row_data = [];

            foreach ($expected_headers as $index => $key) {
                $row_data[$key] = isset($row[$index]) ? sanitize_text_field(trim($row[$index])) : '';
            }

            foreach ($fields as $field) {
                $value = $row_data[$field->field_key] ?? '';

                if ((int) $field->is_required === 1 && $value === '') {
                    $skipped++;
                    continue 2;
                }

                if ($field->field_type === 'number' && $value !== '' && !ctype_digit($value)) {
                    $skipped++;
                    continue 2;
                }
            }

            if (empty($row_data['member_id'])) {
                $skipped++;
                continue;
            }

            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $participants_table WHERE event_id = %d AND member_id = %s",
                    $event_id,
                    $row_data['member_id']
                )
            );

            if ($exists) {
                $skipped++;
                continue;
            }

            $wpdb->insert(
                $participants_table,
                [
                    'event_id'  => $event_id,
                    'member_id' => $row_data['member_id'],
                ],
                ['%d', '%s']
            );

            if (!$wpdb->insert_id) {
                $skipped++;
                continue;
            }

            $participant_id = (int) $wpdb->insert_id;

            foreach ($row_data as $key => $value) {
                if ($key === 'member_id') {
                    continue;
                }

                $wpdb->insert(
                    $meta_table,
                    [
                        'participant_id' => $participant_id,
                        'meta_key'       => $key,
                        'meta_value'     => $value,
                    ],
                    ['%d', '%s', '%s']
                );
            }

            $inserted++;
        }

        fclose($handle);

        wp_safe_redirect(
            admin_url(
                'admin.php?page=ecm-events&action=manage&event_id=' . $event_id .
                    '&tab=participants&csv_imported=1&inserted=' . $inserted .
                    '&skipped=' . $skipped
            )
        );
        exit;
    }

    public function handle_download_sample_csv()
    {
        if (
            !isset($_GET['page'], $_GET['action'], $_GET['event_id']) ||
            $_GET['page'] !== 'ecm-events' ||
            $_GET['action'] !== 'download_sample_csv'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id = absint($_GET['event_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'ecm_download_sample_csv_' . $event_id)
        ) {
            wp_die('Security check failed.');
        }

        $fields = $this->get_event_fields($event_id);

        if (empty($fields)) {
            wp_die('Participant fields are not configured for this event.');
        }

        $headers = [];

        foreach ($fields as $field) {
            $headers[] = $field->field_key;
        }

        $filename = 'ecm-sample-participants-event-' . $event_id . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        fputcsv($output, $headers);

        fclose($output);
        exit;
    }

    public function handle_export_participants_csv()
    {
        if (
            !isset($_GET['page'], $_GET['action'], $_GET['event_id']) ||
            $_GET['page'] !== 'ecm-events' ||
            $_GET['action'] !== 'export_participants_csv'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id = absint($_GET['event_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'ecm_export_participants_csv_' . $event_id)
        ) {
            wp_die('Security check failed.');
        }

        $fields = $this->get_event_fields($event_id);

        if (empty($fields)) {
            wp_die('Participant fields are not configured for this event.');
        }

        global $wpdb;

        $participants_table = $wpdb->prefix . 'ecm_participants';
        $meta_table         = $wpdb->prefix . 'ecm_participant_meta';

        $participants = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $participants_table WHERE event_id = %d ORDER BY id ASC",
                $event_id
            )
        );

        $headers = [];

        foreach ($fields as $field) {
            $headers[] = $field->field_key;
        }

        $filename = 'ecm-participants-event-' . $event_id . '.csv';

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fputcsv($output, $headers);

        foreach ($participants as $participant) {
            $meta_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_key, meta_value FROM $meta_table WHERE participant_id = %d",
                    $participant->id
                ),
                OBJECT_K
            );

            $row = [];

            foreach ($fields as $field) {
                if ($field->field_key === 'member_id') {
                    $row[] = $participant->member_id;
                } else {
                    $row[] = isset($meta_rows[$field->field_key])
                        ? $meta_rows[$field->field_key]->meta_value
                        : '';
                }
            }

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    } 

}
