<?php

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Events {

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

    private function events_list() {
        ?>
        <div class="ecm-panel">
            <h2>Event List</h2>
            <p>No events created yet.</p>
            <p>Click <strong>Add New Event</strong> to create your first event.</p>
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
                            <p class="description">Example: District Convention 2026, ISAME 2026 Kathmandu.</p>
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
                    <button type="submit" class="button button-primary">Save Event</button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ecm-events')); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>
        <?php
    }
}