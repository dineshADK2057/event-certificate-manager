<?php

/**
     * Event Templates Module
     *
     * Handles certificate template CRUD, template listing,
     * template background uploads, and template-related routing.
     *
     * Builder, element, preview, and rendering logic will be
     * separated into dedicated traits within this module.
     *
     * @package EventCertificateManager
 */

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
        <?php $this->render_template_preview_modal(); ?>
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
                                <?php
                                $builder_url = admin_url(
                                    'admin.php?page=ecm-events&action=template_builder&event_id=' . absint($event->id) . '&template_id=' . absint($template->id)
                                );
                                ?>

                                <a href="<?php echo esc_url($builder_url); ?>">Builder</a>
                                |

                                <?php
                                $upload_dir = wp_upload_dir();
                                $background_url = '';

                                if (!empty($template->background_file)) {
                                    $background_url = trailingslashit($upload_dir['baseurl']) . ltrim($template->background_file, '/');
                                }
                                ?>

                                <a href="#"
                                    class="ecm-preview-template"
                                    data-template-name="<?php echo esc_attr($template->template_name); ?>"
                                    data-background-url="<?php echo esc_url($background_url); ?>"
                                    data-background-file="<?php echo esc_attr($template->background_file); ?>">
                                    Preview
                                </a>
                                |
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

        $extension = strtolower(pathinfo($destination, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            $preview_filename = pathinfo($safe_name, PATHINFO_FILENAME) . '-preview.png';
            $preview_path     = $ecm_dir . $preview_filename;

            $preview_result = $this->generate_pdf_preview_image(
                $destination,
                $preview_path,
                $template
            );

            if (is_wp_error($preview_result)) {
                // Remove the PDF because its required preview could not be generated.
                if (file_exists($destination)) {
                    unlink($destination);
                }

                wp_die(
                    'PDF uploaded, but preview generation failed: ' .
                        esc_html($preview_result->get_error_message())
                );
            }
        }

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

    private function render_template_preview_modal()
    {
    ?>
        <div id="ecm-template-preview-modal" class="ecm-modal" style="display:none;">
            <div class="ecm-modal-content ecm-modal-preview">
                <div class="ecm-modal-header">
                    <h2 id="ecm-preview-template-title">Template Preview</h2>
                    <button type="button" class="ecm-modal-close">&times;</button>
                </div>

                <div class="ecm-modal-body">
                    <div id="ecm-template-preview-content">
                        <p>No preview available.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

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
                            <div class="ecm-builder-element"
                                style="<?php echo esc_attr($style); ?>">
                                <?php echo esc_html($sample_value); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="ecm-builder-sidebar">
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
                                        'admin.php?page=ecm-events&action=delete_template_element&event_id=' . absint($event->id) .
                                            '&template_id=' . absint($template->id) .
                                            '&element_id=' . absint($element->id)
                                    ),
                                    'ecm_delete_template_element_' . absint($element->id)
                                );
                                ?>

                                <li>
                                    <strong><?php echo esc_html('{' . $element->placeholder_key . '}'); ?></strong>
                                    <br>
                                    <span class="sdescription">
                                        X: <?php echo esc_html($element->x_position); ?>,
                                        Y: <?php echo esc_html($element->y_position); ?>,
                                        Size: <?php echo esc_html($element->font_size); ?>
                                    </span>

                                    <br>
                                    <a href="#"
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
                                    |
                                    <a href="<?php echo esc_url($delete_element_url); ?>"
                                        onclick="return confirm('Delete this template element?');"
                                        class="ecm-danger-link">
                                        Delete
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
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

}
