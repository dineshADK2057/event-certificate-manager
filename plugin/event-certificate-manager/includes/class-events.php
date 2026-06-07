<?php

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Events
{

    public function __construct()
    {
        add_action('admin_init', [$this, 'handle_event_save']);
        add_action('admin_init', [$this, 'handle_event_delete']);
        add_action('admin_init', [$this, 'handle_add_default_fields']);
        add_action('admin_init', [$this, 'handle_add_custom_field']);
        add_action('admin_init', [$this, 'handle_add_participant']);
        add_action('admin_init', [$this, 'handle_update_participant']);
        add_action('admin_init', [$this, 'handle_delete_participant']);
        add_action('admin_init', [$this, 'handle_bulk_participant_action']);
        add_action('admin_init', [$this, 'handle_csv_import']);
        add_action('admin_init', [$this, 'handle_download_sample_csv']);
        add_action('admin_init', [$this, 'handle_export_participants_csv']);
        add_action('admin_init', [$this, 'handle_add_session']);
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


    private function tab_sessions($event)
    {
    ?>
        <div class="ecm-tab-header">
            <div>
                <h2>Sessions</h2>
                <p>Create and manage sessions under this event.</p>
            </div>

            <div class="ecm-tab-actions">
                <button type="button" class="button button-primary ecm-open-session-modal">
                    + Add Session
                </button>
            </div>
        </div>

        <?php if (isset($_GET['session_added'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Session added successfully.</strong></p>
            </div>
        <?php endif; ?>

        <?php $this->render_sessions_list_section($event); ?>
        <?php $this->render_add_session_modal($event); ?>
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

    private function get_event_fields($event_id)
    {
        global $wpdb;

        $fields_table = $wpdb->prefix . 'ecm_event_fields';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $fields_table WHERE event_id = %d ORDER BY field_order ASC, id ASC",
                $event_id
            )
        );
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
                                <input
                                    type="<?php echo $field->field_type === 'number' ? 'number' : 'text'; ?>"
                                    id="ecm_field_<?php echo esc_attr($field->field_key); ?>"
                                    name="participant_fields[<?php echo esc_attr($field->field_key); ?>]"
                                    class="regular-text"
                                    <?php echo (int) $field->is_required === 1 ? 'required' : ''; ?>>
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
        <div class="ecm-panel ecm-panel-full">
            <h3>Participant List</h3>

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

    private function render_sessions_list_section($event)
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
            <h3>Session List</h3>

            <?php if (empty($sessions)) : ?>
                <p>No sessions created yet.</p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Session Code</th>
                            <th>Session Name</th>
                            <th>Tutor / Speaker</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($session->session_code); ?></strong></td>
                                <td><?php echo esc_html($session->session_name); ?></td>
                                <td><?php echo esc_html($session->tutor_name); ?></td>
                                <td><?php echo esc_html($session->session_date); ?></td>
                                <td>
                                    <span class="ecm-status ecm-status-<?php echo esc_attr($session->status); ?>">
                                        <?php echo esc_html(ucfirst($session->status)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php
    }

    private function render_add_session_modal($event)
    {
    ?>
        <div id="ecm-add-session-modal" class="ecm-modal" style="display:none;">
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2>Add Session</h2>
                    <button type="button" class="ecm-modal-close">&times;</button>
                </div>

                <form method="post">
                    <?php wp_nonce_field('ecm_add_session', 'ecm_add_session_nonce'); ?>
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">

                    <div class="ecm-modal-body">
                        <p>
                            <label for="session_name">
                                <strong>Session Name</strong>
                            </label>
                            <input type="text" id="session_name" name="session_name" class="widefat" required>
                        </p>

                        <p>
                            <label for="tutor_name">
                                <strong>Tutor / Speaker</strong>
                            </label>
                            <input type="text" id="tutor_name" name="tutor_name" class="widefat">
                        </p>

                        <p>
                            <label for="session_date">
                                <strong>Session Date</strong>
                            </label>
                            <input type="date" id="session_date" name="session_date" class="widefat">
                        </p>

                        <p>
                            <label for="session_status">
                                <strong>Status</strong>
                            </label>
                            <select id="session_status" name="status" class="widefat">
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="closed">Closed</option>
                            </select>
                        </p>
                    </div>

                    <div class="ecm-modal-footer">
                        <button type="submit" name="ecm_add_session_submit" class="button button-primary">
                            Save Session
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

    public function handle_add_session() {
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

private function generate_session_code($event_id) {
    global $wpdb;

    $sessions_table = $wpdb->prefix . 'ecm_sessions';

    $count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE event_id = %d",
            $event_id
        )
    );

    $number = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

    return 'SES-' . $number;
}
}
