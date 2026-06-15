<?php

if (!defined('ABSPATH')) {
    exit;
}
require_once ECM_PLUGIN_PATH . 'includes/modules/trait-event-sessions.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/trait-event-settings.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/trait-event-participants.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/trait-event-helpers.php';
require_once ECM_PLUGIN_PATH . 'includes/modules/trait-event-templates.php';

class ECM_Events
{
    use ECM_Event_Sessions;
    use ECM_Event_Settings;
    use ECM_Event_Participants;
    use ECM_Event_Helpers;
    use ECM_Event_Templates;

    public function __construct()
    {
        add_action('admin_init', [$this, 'handle_event_save']);
        add_action('admin_init', [$this, 'handle_event_delete']);

        add_action('admin_init', [$this, 'handle_add_default_fields']);
        add_action('admin_init', [$this, 'handle_add_custom_field']);
        add_action('admin_init', [$this, 'handle_update_custom_field']);
        add_action('admin_init', [$this, 'handle_delete_custom_field']);

        add_action('admin_init', [$this, 'handle_add_participant']);
        add_action('admin_init', [$this, 'handle_update_participant']);
        add_action('admin_init', [$this, 'handle_delete_participant']);
        add_action('admin_init', [$this, 'handle_bulk_participant_action']);

        add_action('admin_init', [$this, 'handle_csv_import']);
        add_action('admin_init', [$this, 'handle_download_sample_csv']);
        add_action('admin_init', [$this, 'handle_export_participants_csv']);

        add_action('admin_init', [$this, 'handle_add_session']);
        add_action('admin_init', [$this, 'handle_update_session']);
        add_action('admin_init', [$this, 'handle_delete_session']);
        add_action('admin_init', [$this, 'handle_add_session_participants']);

        add_action('wp_ajax_ecm_search_session_available_participants', [$this, 'ajax_search_session_available_participants']);
        add_action('wp_ajax_ecm_add_session_participants_ajax', [$this, 'ajax_add_session_participants']);

        add_action('admin_init', [$this, 'handle_remove_session_participant']);
        add_action('admin_init', [$this, 'handle_save_session_settings']);
    }

    public function events_page()
    {
?>
        <div class="wrap ecm-wrap">
            <div class="ecm-page-header">
                <div>
                    <h1>Events</h1>
                    <p class="ecm-subtitle">Create and manage events for certificate generation.</p>
                </div>

                <a href="<?php echo esc_url(admin_url('admin.php?page=ecm-events&action=add')); ?>" class="button button-primary">
                    Add New Event
                </a>
            </div>

            <?php
            $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

            if ($action === 'add') {
                $this->event_form();
            } elseif ($action === 'edit') {
                $event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
                $this->event_form($event_id);
            } elseif ($action === 'manage') {
                $event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
                $this->manage_event_page($event_id);
            } else {
                $this->events_list();
            }
            ?>
        </div>
    <?php
    }

    public function handle_event_save()
    {
        if (!isset($_POST['ecm_save_event_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_event_nonce']) ||
            !wp_verify_nonce($_POST['ecm_event_nonce'], 'ecm_save_event')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ecm_events';

        $event_id   = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $event_name = sanitize_text_field($_POST['event_name'] ?? '');
        $event_type = sanitize_text_field($_POST['event_type'] ?? '');
        $venue      = sanitize_text_field($_POST['venue'] ?? '');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date   = sanitize_text_field($_POST['end_date'] ?? '');
        $status     = sanitize_text_field($_POST['status'] ?? 'draft');

        if (empty($event_name)) {
            wp_die('Event name is required.');
        }

        $allowed_statuses = ['draft', 'active', 'closed'];
        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'draft';
        }

        $data = [
            'event_name' => $event_name,
            'event_type' => $event_type,
            'venue'      => $venue,
            'start_date' => $start_date ?: null,
            'end_date'   => $end_date ?: null,
            'status'     => $status,
            'updated_at' => current_time('mysql'),
        ];

        $formats = ['%s', '%s', '%s', '%s', '%s', '%s', '%s'];

        if ($event_id > 0) {
            $updated = $wpdb->update(
                $table,
                $data,
                ['id' => $event_id],
                $formats,
                ['%d']
            );

            if ($updated === false) {
                wp_die('Failed to update event.');
            }

            wp_safe_redirect(admin_url('admin.php?page=ecm-events&updated=1'));
            exit;
        }

        $event_code = $this->generate_event_code($event_name);
        $data['event_code'] = $event_code;

        $inserted = $wpdb->insert(
            $table,
            $data,
            array_merge($formats, ['%s'])
        );

        if (!$inserted) {
            wp_die('Failed to save event.');
        }

        wp_safe_redirect(admin_url('admin.php?page=ecm-events&created=1'));
        exit;
    }

    public function handle_event_delete()
    {
        if (
            !isset($_GET['page'], $_GET['action'], $_GET['event_id']) ||
            $_GET['page'] !== 'ecm-events' ||
            $_GET['action'] !== 'delete'
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id = absint($_GET['event_id']);

        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'ecm_delete_event_' . $event_id)
        ) {
            wp_die('Security check failed.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ecm_events';

        $deleted = $wpdb->delete(
            $table,
            ['id' => $event_id],
            ['%d']
        );

        if ($deleted === false) {
            wp_die('Failed to delete event.');
        }

        wp_safe_redirect(admin_url('admin.php?page=ecm-events&deleted=1'));
        exit;
    }

    private function generate_event_code($event_name)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';

        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($event_name, 0, 8)));

        if (empty($prefix)) {
            $prefix = 'EVENT';
        }

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $number = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $number;
    }

    private function events_list()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';

        $events = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    ?>

        <?php if (isset($_GET['created'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Event created successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Event updated successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Event deleted successfully.</strong></p>
            </div>
        <?php endif; ?>

        <div class="ecm-panel ecm-panel-full">
            <h2>Event List</h2>

            <?php if (empty($events)) : ?>

                <p>No events created yet.</p>
                <p>Click <strong>Add New Event</strong> to create your first event.</p>

            <?php else : ?>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Event Code</th>
                            <th>Event Name</th>
                            <th>Type</th>
                            <th>Venue</th>
                            <th>Start Date</th>
                            <th>Status</th>
                            <th width="160">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event) : ?>
                            <?php
                            $edit_url = admin_url('admin.php?page=ecm-events&action=edit&event_id=' . absint($event->id));
                            $manage_url = admin_url('admin.php?page=ecm-events&action=manage&event_id=' . absint($event->id));

                            $delete_url = wp_nonce_url(
                                admin_url('admin.php?page=ecm-events&action=delete&event_id=' . absint($event->id)),
                                'ecm_delete_event_' . absint($event->id)
                            );
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($event->event_code); ?></strong></td>
                                <td><?php echo esc_html($event->event_name); ?></td>
                                <td><?php echo esc_html($event->event_type); ?></td>
                                <td><?php echo esc_html($event->venue); ?></td>
                                <td><?php echo esc_html($event->start_date); ?></td>
                                <td>
                                    <span class="ecm-status ecm-status-<?php echo esc_attr($event->status); ?>">
                                        <?php echo esc_html(ucfirst($event->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($manage_url); ?>">Manage</a>
                                    |
                                    <a href="<?php echo esc_url($edit_url); ?>">Edit</a>
                                    |
                                    <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Are you sure you want to delete this event?');" class="ecm-danger-link">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>
        </div>
    <?php
    }

    private function event_form($event_id = 0)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';

        $event = null;

        if ($event_id > 0) {
            $event = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $event_id)
            );

            if (!$event) {
                echo '<div class="notice notice-error"><p>Event not found.</p></div>';
                return;
            }
        }

        $is_edit = $event_id > 0;
    ?>
        <div class="ecm-form-header">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ecm-events')); ?>" class="button">
                ← Back to Event List
            </a>
        </div>

        <div class="ecm-panel">
            <h2><?php echo $is_edit ? 'Edit Event' : 'Add New Event'; ?></h2>

            <?php if ($is_edit) : ?>
                <p><strong>Event Code:</strong> <?php echo esc_html($event->event_code); ?></p>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('ecm_save_event', 'ecm_event_nonce'); ?>

                <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="event_name">Event Name</label>
                        </th>
                        <td>
                            <input type="text" id="event_name" name="event_name" class="regular-text" required value="<?php echo esc_attr($event->event_name ?? ''); ?>">
                            <p class="description">Event code is generated automatically and cannot be edited.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="event_type">Event Type</label>
                        </th>
                        <td>
                            <input type="text" id="event_type" name="event_type" class="regular-text" value="<?php echo esc_attr($event->event_type ?? ''); ?>">
                            <p class="description">Example: Convention, Training, Forum, Seminar.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="venue">Venue</label>
                        </th>
                        <td>
                            <input type="text" id="venue" name="venue" class="regular-text" value="<?php echo esc_attr($event->venue ?? ''); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="start_date">Start Date</label>
                        </th>
                        <td>
                            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($event->start_date ?? ''); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="end_date">End Date</label>
                        </th>
                        <td>
                            <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($event->end_date ?? ''); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="status">Status</label>
                        </th>
                        <td>
                            <?php $selected_status = $event->status ?? 'draft'; ?>
                            <select id="status" name="status">
                                <option value="draft" <?php selected($selected_status, 'draft'); ?>>Draft</option>
                                <option value="active" <?php selected($selected_status, 'active'); ?>>Active</option>
                                <option value="closed" <?php selected($selected_status, 'closed'); ?>>Closed</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="ecm_save_event_submit" class="button button-primary">
                        <?php echo $is_edit ? 'Update Event' : 'Save Event'; ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ecm-events')); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>
    <?php
    }

    private function manage_event_page($event_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';

        $event = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $event_id)
        );

        if (!$event) {
            echo '<div class="notice notice-error"><p>Event not found.</p></div>';
            return;
        }

        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

        $tabs = [
            'overview'     => 'Overview',
            'participants' => 'Participants',
            'sessions'     => 'Sessions',
            'templates'    => 'Templates',
            'certificates' => 'Certificates',
            'logs'         => 'Logs',
            'settings'     => 'Settings',
        ];

    ?>
        <div class="ecm-form-header">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ecm-events')); ?>" class="button">
                ← Back to Event List
            </a>
        </div>

        <div class="ecm-event-heading">
            <div>
                <h2><?php echo esc_html($event->event_name); ?></h2>
                <p>
                    <strong>Event Code:</strong> <?php echo esc_html($event->event_code); ?>
                    &nbsp; | &nbsp;
                    <strong>Status:</strong>
                    <span class="ecm-status ecm-status-<?php echo esc_attr($event->status); ?>">
                        <?php echo esc_html(ucfirst($event->status)); ?>
                    </span>
                </p>
            </div>

            <a href="<?php echo esc_url(admin_url('admin.php?page=ecm-events&action=edit&event_id=' . absint($event->id))); ?>" class="button">
                Edit Event
            </a>
        </div>

        <nav class="nav-tab-wrapper ecm-tabs">
            <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                <?php
                $tab_url = admin_url(
                    'admin.php?page=ecm-events&action=manage&event_id=' . absint($event->id) . '&tab=' . $tab_key
                );
                ?>
                <a href="<?php echo esc_url($tab_url); ?>"
                    class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($tab_label); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="ecm-panel ecm-panel-full ecm-tab-content">
            <?php $this->render_event_tab($current_tab, $event); ?>
        </div>
    <?php
    }

    private function render_event_tab($tab, $event)
    {
        switch ($tab) {
            case 'participants':
                $this->tab_participants($event);
                break;

            case 'sessions':
                $this->tab_sessions($event);
                break;

            case 'templates':
                $this->tab_templates($event);
                break;

            case 'certificates':
                $this->tab_certificates($event);
                break;

            case 'logs':
                $this->tab_logs($event);
                break;

            case 'settings':
                $this->tab_settings($event);
                break;

            case 'overview':
            default:
                $this->tab_overview($event);
                break;
        }
    }

    private function tab_overview($event)
    {
    ?>
        <h2>Overview</h2>
        <p>This is the main control center for this event.</p>

        <table class="widefat striped ecm-details-table">
            <tbody>
                <tr>
                    <th>Event Name</th>
                    <td><?php echo esc_html($event->event_name); ?></td>
                </tr>
                <tr>
                    <th>Event Code</th>
                    <td><?php echo esc_html($event->event_code); ?></td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td><?php echo esc_html($event->event_type); ?></td>
                </tr>
                <tr>
                    <th>Venue</th>
                    <td><?php echo esc_html($event->venue); ?></td>
                </tr>
                <tr>
                    <th>Start Date</th>
                    <td><?php echo esc_html($event->start_date); ?></td>
                </tr>
                <tr>
                    <th>End Date</th>
                    <td><?php echo esc_html($event->end_date); ?></td>
                </tr>
            </tbody>
        </table>
    <?php
    }

    

    private function tab_templates($event)
    {
    ?>
        <h2>Templates</h2>
        <p>Certificate template upload and placeholder positioning will be built here.</p>
    <?php
    }

    private function tab_logs($event)
    {
    ?>
        <h2>Certificate Logs</h2>
        <p>Generated certificate history, email status, downloads, and resend actions will be built here.</p>
    <?php
    }


   

    private function render_add_participant_section($event)
    {
        $fields = $this->get_event_fields($event->id);

        if (empty($fields)) {
            return;
        }
    ?>
        <div class="ecm-panel ecm-panel-full">
            <h3>Add Participant</h3>
            <p class="description">Add one participant manually using this event's fields.</p>

            <form method="post">
                <?php wp_nonce_field('ecm_add_participant', 'ecm_add_participant_nonce'); ?>
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">

                <table class="form-table">
                    <?php foreach ($fields as $field) : ?>
                        <tr>
                            <th scope="row">
                                <label for="ecm_field_<?php echo esc_attr($field->field_key); ?>">
                                    <?php echo esc_html($field->field_label); ?>
                                    <?php if ((int) $field->is_required === 1) : ?>
                                        <span class="description">(required)</span>
                                    <?php endif; ?>
                                </label>
                            </th>
                            <td>
                                <input>
                                    type="<?php echo $field->field_type === 'number' ? 'number' : 'text'; ?>"
                                    id="ecm_field_<?php echo esc_attr($field->field_key); ?>"
                                    name="participant_fields[<?php echo esc_attr($field->field_key); ?>]"
                                    class="regular-text"
                                    <?php echo (int) $field->is_required === 1 ? 'required' : ''; ?>>
                                </input>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <p>
                    <button type="submit" name="ecm_add_participant_submit" class="button button-primary">
                        Add Participant
                    </button>
                </p>
            </form>
        </div>
    <?php
    }



    private function tab_certificates($event)
    {
    ?>
        <h2>Certificates</h2>
        <p>This module will manage generated certificates, downloads, email sending, QR verification, and resend actions.</p>

        <div class="ecm-panel ecm-panel-full">
            <h3>Certificate Engine</h3>
            <p>Coming soon:</p>
            <ul class="ul-disc">
                <li>Generated certificate list</li>
                <li>Certificate ID management</li>
                <li>Download certificate PDF</li>
                <li>Resend email</li>
                <li>QR verification status</li>
            </ul>
        </div>
    <?php
    }

    public function handle_add_session()
    {
        if (isset($_POST['ecm_update_session_submit'])) {
            return;
        }

        if (!isset($_POST['ecm_add_session_submit'])) {
            return;
        }

        if (
            !isset($_POST['ecm_add_session_nonce']) ||
            !wp_verify_nonce($_POST['ecm_add_session_nonce'], 'ecm_add_session')
        ) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $event_id     = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $session_name = sanitize_text_field($_POST['session_name'] ?? '');
        $tutor_name   = sanitize_text_field($_POST['tutor_name'] ?? '');
        $session_date = sanitize_text_field($_POST['session_date'] ?? '');
        $status       = sanitize_text_field($_POST['status'] ?? 'active');

        if (!$event_id) {
            wp_die('Invalid event.');
        }

        if (empty($session_name)) {
            wp_die('Session name is required.');
        }

        $allowed_statuses = ['draft', 'active', 'closed'];

        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'active';
        }

        global $wpdb;

        $sessions_table = $wpdb->prefix . 'ecm_sessions';

        $session_code = $this->generate_session_code($event_id);

        $inserted = $wpdb->insert(
            $sessions_table,
            [
                'event_id'     => $event_id,
                'session_code' => $session_code,
                'session_name' => $session_name,
                'tutor_name'   => $tutor_name,
                'session_date' => $session_date ?: null,
                'status'       => $status,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$inserted) {
            wp_die('Failed to add session.');
        }

        wp_safe_redirect(
            admin_url('admin.php?page=ecm-events&action=manage&event_id=' . $event_id . '&tab=sessions&session_added=1')
        );
        exit;
    }
}
