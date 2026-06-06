<?php

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Events {

    public function __construct() {
        add_action('admin_init', [$this, 'handle_event_save']);
    }

    public function events_page() {
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
                $this->add_event_form();
            } else {
                $this->events_list();
            }
            ?>
        </div>
        <?php
    }

    public function handle_event_save() {
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

        $event_code = $this->generate_event_code($event_name);

        $inserted = $wpdb->insert(
            $table,
            [
                'event_code' => $event_code,
                'event_name' => $event_name,
                'event_type' => $event_type,
                'venue'      => $venue,
                'start_date' => $start_date ?: null,
                'end_date'   => $end_date ?: null,
                'status'     => $status,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$inserted) {
            wp_die('Failed to save event.');
        }

        wp_safe_redirect(admin_url('admin.php?page=ecm-events&created=1'));
        exit;
    }

    private function generate_event_code($event_name) {
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

    private function events_list() {
        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';

        $events = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
        ?>

        <?php if (isset($_GET['created'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Event created successfully.</strong></p>
            </div>
        <?php endif; ?>

        <div class="ecm-panel">
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event) : ?>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>
        </div>
        <?php
    }

    private function add_event_form() {
        ?>
        <div class="ecm-panel">
            <h2>Add New Event</h2>

            <form method="post">
                <?php wp_nonce_field('ecm_save_event', 'ecm_event_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="event_name">Event Name</label>
                        </th>
                        <td>
                            <input type="text" id="event_name" name="event_name" class="regular-text" required>
                            <p class="description">Event code will be generated automatically.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="event_type">Event Type</label>
                        </th>
                        <td>
                            <input type="text" id="event_type" name="event_type" class="regular-text">
                            <p class="description">Example: Convention, Training, Forum, Seminar.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="venue">Venue</label>
                        </th>
                        <td>
                            <input type="text" id="venue" name="venue" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="start_date">Start Date</label>
                        </th>
                        <td>
                            <input type="date" id="start_date" name="start_date">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="end_date">End Date</label>
                        </th>
                        <td>
                            <input type="date" id="end_date" name="end_date">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="status">Status</label>
                        </th>
                        <td>
                            <select id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="closed">Closed</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="ecm_save_event_submit" class="button button-primary">Save Event</button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ecm-events')); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>
        <?php
    }
}