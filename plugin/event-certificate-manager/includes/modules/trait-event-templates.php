<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Templates
{

    private function tab_templates($event)
    {
?>
        <div class="ecm-tab-header">
            <div>
                <h2>Templates</h2>
                <p>Create and manage certificate templates for this event.</p>
            </div>

            <div class="ecm-tab-actions">
                <button type="button" class="button button-primary ecm-open-template-modal">
                    + Create Template
                </button>
            </div>
        </div>

        <?php if (isset($_GET['template_added'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Template created successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['template_updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Template updated successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['template_deleted'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Template deleted successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['background_uploaded'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Template background uploaded successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php $this->render_templates_list_section($event); ?>
        <?php $this->render_add_template_modal($event); ?>
        <?php $this->render_template_background_modal($event); ?>
    <?php
    }

    private function render_templates_list_section($event)
    {
        global $wpdb;

        $templates_table = $wpdb->prefix . 'ecm_templates';
        $sessions_table  = $wpdb->prefix . 'ecm_sessions';

        $templates = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, s.session_name, s.session_code
             FROM $templates_table t
             LEFT JOIN $sessions_table s ON t.session_id = s.id
             WHERE t.event_id = %d
             ORDER BY t.id DESC",
                $event->id
            )
        );
    ?>
        <div class="ecm-panel ecm-panel-full">
            <h3>Template List</h3>

            <?php if (empty($templates)) : ?>
                <p>No templates created yet.</p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Certificate Type</th>
                            <th>Session</th>
                            <th>Orientation</th>
                            <th>Page Size</th>
                            <th>Background</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($template->template_name); ?></strong></td>
                                <td><?php echo esc_html($template->certificate_type); ?></td>
                                <td>
                                    <?php
                                    if ($template->session_id) {
                                        echo esc_html($template->session_code . ' - ' . $template->session_name);
                                    } else {
                                        echo '<em>Event-wide</em>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html(ucfirst($template->orientation)); ?></td>
                                <td><?php echo esc_html($template->page_size); ?></td>
                                <td>
                                    <?php if (!empty($template->background_file)) : ?>
                                        <span class="ecm-status ecm-status-active">Uploaded</span>
                                    <?php else : ?>
                                        <span class="ecm-status ecm-status-draft">Missing</span>
                                    <?php endif; ?>
                                </td>
                                <?php
                                $delete_url = wp_nonce_url(
                                    admin_url(
                                        'admin.php?page=ecm-events&action=delete_template&event_id=' . absint($event->id) . '&template_id=' . absint($template->id)
                                    ),
                                    'ecm_delete_template_' . absint($template->id)
                                );
                                ?>

                                <td>
                                    <a href="#"
                                        class="ecm-upload-template-bg"
                                        data-template-id="<?php echo esc_attr($template->id); ?>"
                                        data-template-name="<?php echo esc_attr($template->template_name); ?>">
                                        Upload Background
                                    </a>
                                    |
                                    <a href="#"
                                        class="ecm-edit-template"
                                        data-template-id="<?php echo esc_attr($template->id); ?>"
                                        data-template-name="<?php echo esc_attr($template->template_name); ?>"
                                        data-certificate-type="<?php echo esc_attr($template->certificate_type); ?>"
                                        data-session-id="<?php echo esc_attr($template->session_id ?: 0); ?>"
                                        data-orientation="<?php echo esc_attr($template->orientation); ?>"
                                        data-page-size="<?php echo esc_attr($template->page_size); ?>">
                                        Edit
                                    </a>
                                    |
                                    <a href="<?php echo esc_url($delete_url); ?>"
                                        onclick="return confirm('Delete this template?');"
                                        class="ecm-danger-link">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php
    }

    private function render_add_template_modal($event)
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
        <div id="ecm-add-template-modal" class="ecm-modal" style="display:none;">
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2 id="ecm-template-modal-title">Create Template</h2>
                    <button type="button" class="ecm-modal-close">&times;</button>
                </div>

                <form method="post">
                    <?php wp_nonce_field('ecm_add_template', 'ecm_add_template_nonce'); ?>
                    <?php wp_nonce_field('ecm_update_template', 'ecm_update_template_nonce'); ?>

                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                    <input type="hidden" name="template_id" id="ecm_template_id" value="">

                    <div class="ecm-modal-body">
                        <p>
                            <label>
                                <strong>Template Name</strong>
                                <input type="text" name="template_name" id="ecm_template_name" class="widefat" required>
                            </label>
                        </p>

                        <p>
                            <label>
                                <strong>Certificate Type</strong>
                                <select name="certificate_type" id="ecm_certificate_type" class="widefat">
                                    <option value="participant">Participant</option>
                                    <option value="speaker">Speaker</option>
                                    <option value="appreciation">Appreciation</option>
                                    <option value="organizer">Organizer</option>
                                    <option value="sponsor">Sponsor</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </label>
                        </p>

                        <p>
                            <label>
                                <strong>Session</strong>
                                <select name="session_id" id="ecm_template_session_id" class="widefat">
                                    <option value="0">Event-wide Template</option>
                                    <?php foreach ($sessions as $session) : ?>
                                        <option value="<?php echo esc_attr($session->id); ?>">
                                            <?php echo esc_html($session->session_code . ' - ' . $session->session_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </p>

                        <p>
                            <label>
                                <strong>Orientation</strong>
                                <select name="orientation" id="ecm_template_orientation" class="widefat">
                                    <option value="landscape">Landscape</option>
                                    <option value="portrait">Portrait</option>
                                </select>
                            </label>
                        </p>

                        <p>
                            <label>
                                <strong>Page Size</strong>
                                <select name="page_size" id="ecm_template_page_size" class="widefat">
                                    <option value="A4">A4</option>
                                    <option value="Letter">Letter</option>
                                </select>
                            </label>
                        </p>
                    </div>

                    <div class="ecm-modal-footer">
                        <button type="submit" name="ecm_add_template_submit" id="ecm_add_template_submit" class="button button-primary">
                            Create Template
                        </button>

                        <button type="submit" name="ecm_update_template_submit" id="ecm_update_template_submit" class="button button-primary" style="display:none;">
                            Update Template
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

    public function handle_add_template()
    {
        if (isset($_POST['ecm_update_template_submit'])) {
            return;
        }

        if (!isset($_POST['ecm_add_template_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_add_template_nonce']) ||
            !wp_verify_nonce($_POST['ecm_add_template_nonce'], 'ecm_add_template')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id         = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $session_id       = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
        $template_name    = sanitize_text_field($_POST['template_name'] ?? '');
        $certificate_type = sanitize_text_field($_POST['certificate_type'] ?? 'participant');
        $orientation      = sanitize_text_field($_POST['orientation'] ?? 'landscape');
        $page_size        = sanitize_text_field($_POST['page_size'] ?? 'A4');

        if (!$event_id || empty($template_name)) {
            wp_die('Template name is required.');
        }

        $allowed_orientations = ['landscape', 'portrait'];
        if (!in_array($orientation, $allowed_orientations, true)) {
            $orientation = 'landscape';
        }

        $allowed_page_sizes = ['A4', 'Letter'];
        if (!in_array($page_size, $allowed_page_sizes, true)) {
            $page_size = 'A4';
        }

        global $wpdb;

        $templates_table = $wpdb->prefix . 'ecm_templates';

        $inserted = $wpdb->insert(
            $templates_table,
            [
                'event_id'         => $event_id,
                'session_id'       => $session_id ?: null,
                'template_name'    => $template_name,
                'certificate_type' => $certificate_type,
                'orientation'      => $orientation,
                'page_size'        => $page_size,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        if (!$inserted) {
            wp_die('Failed to create template.');
        }

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=templates&template_added=1')
        );
        exit;
    }

    public function handle_update_template()
    {
        if (!isset($_POST['ecm_update_template_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_update_template_nonce']) ||
            !wp_verify_nonce($_POST['ecm_update_template_nonce'], 'ecm_update_template')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id         = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $template_id      = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        $session_id       = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
        $template_name    = sanitize_text_field($_POST['template_name'] ?? '');
        $certificate_type = sanitize_text_field($_POST['certificate_type'] ?? 'participant');
        $orientation      = sanitize_text_field($_POST['orientation'] ?? 'landscape');
        $page_size        = sanitize_text_field($_POST['page_size'] ?? 'A4');

        if (!$event_id || !$template_id || empty($template_name)) {
            wp_die('Invalid template data.');
        }

        $allowed_types = ['participant', 'speaker', 'appreciation', 'organizer', 'sponsor', 'custom'];
        if (!in_array($certificate_type, $allowed_types, true)) {
            $certificate_type = 'participant';
        }

        $allowed_orientations = ['landscape', 'portrait'];
        if (!in_array($orientation, $allowed_orientations, true)) {
            $orientation = 'landscape';
        }

        $allowed_page_sizes = ['A4', 'Letter'];
        if (!in_array($page_size, $allowed_page_sizes, true)) {
            $page_size = 'A4';
        }

        global $wpdb;

        $templates_table = $wpdb->prefix . 'ecm_templates';

        $updated = $wpdb->update(
            $templates_table,
            [
                'session_id'       => $session_id ?: null,
                'template_name'    => $template_name,
                'certificate_type' => $certificate_type,
                'orientation'      => $orientation,
                'page_size'        => $page_size,
                'updated_at'       => current_time('mysql'),
            ],
            [
                'id'       => $template_id,
                'event_id' => $event_id,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s'],
            ['%d', '%d']
        );

        if ($updated === false) {
            wp_die('Failed to update template.');
        }

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=templates&template_updated=1')
        );
        exit;
    }

    public function handle_delete_template()
    {
        if (
            !isset($_GET['page'], $_GET['action'], $_GET['event_id'], $_GET['template_id']) ||
            $_GET['page'] !== 'ecm-events' ||
            $_GET['action'] !== 'delete_template'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id    = absint($_GET['event_id']);
        $template_id = absint($_GET['template_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'ecm_delete_template_' . $template_id)
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $templates_table = $wpdb->prefix . 'ecm_templates';

        $wpdb->delete(
            $templates_table,
            [
                'id'       => $template_id,
                'event_id' => $event_id,
            ],
            ['%d', '%d']
        );

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=templates&template_deleted=1')
        );
        exit;
    }

    private function render_template_background_modal($event)
    {
    ?>
        <div id="ecm-template-bg-modal" class="ecm-modal" style="display:none;">
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2>Upload Template Background</h2>
                    <button type="button" class="ecm-modal-close">&times;</button>
                </div>

                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('ecm_upload_template_background', 'ecm_upload_template_bg_nonce'); ?>

                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                    <input type="hidden" name="template_id" id="ecm_bg_template_id" value="">

                    <div class="ecm-modal-body">
                        <p>
                            <strong>Template:</strong>
                            <span id="ecm_bg_template_name"></span>
                        </p>

                        <p>
                            <label>
                                <strong>Background File</strong><br>
                                <input type="file" name="template_background" accept=".png,.jpg,.jpeg,.pdf" required>
                            </label>
                        </p>

                        <p class="description">
                            Supported formats: PNG, JPG, JPEG, PDF.
                        </p>
                    </div>

                    <div class="ecm-modal-footer">
                        <button type="submit" name="ecm_upload_template_bg_submit" class="button button-primary">
                            Upload Background
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

    public function handle_upload_template_background()
    {
        if (!isset($_POST['ecm_upload_template_bg_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_upload_template_bg_nonce']) ||
            !wp_verify_nonce($_POST['ecm_upload_template_bg_nonce'], 'ecm_upload_template_background')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id    = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;

        if (!$event_id || !$template_id) {
            wp_die('Invalid template.');
        }

        if (empty($_FILES['template_background']['tmp_name'])) {
            wp_die('No background file uploaded.');
        }

        $file = $_FILES['template_background'];

        $allowed_exts = ['png', 'jpg', 'jpeg', 'pdf'];
        $filetype = wp_check_filetype($file['name']);

        if (empty($filetype['ext']) || !in_array(strtolower($filetype['ext']), $allowed_exts, true)) {
            wp_die('Invalid file type. Please upload PNG, JPG, JPEG, or PDF.');
        }

        global $wpdb;

        $templates_table = $wpdb->prefix . 'ecm_templates';

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

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $upload_dir = wp_upload_dir();
        $ecm_dir = trailingslashit($upload_dir['basedir']) . 'ecm/templates/';

        if (!file_exists($ecm_dir)) {
            wp_mkdir_p($ecm_dir);
        }

        $safe_name = sanitize_file_name(
            'template-' . $template_id . '-' . time() . '-' . $file['name']
        );

        $destination = $ecm_dir . $safe_name;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            wp_die('Failed to upload background file.');
        }

        $relative_path = 'ecm/templates/' . $safe_name;

        $wpdb->update(
            $templates_table,
            [
                'background_file' => $relative_path,
                'updated_at'      => current_time('mysql'),
            ],
            [
                'id'       => $template_id,
                'event_id' => $event_id,
            ],
            ['%s', '%s'],
            ['%d', '%d']
        );

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=templates&background_uploaded=1')
        );
        exit;
    }
}
