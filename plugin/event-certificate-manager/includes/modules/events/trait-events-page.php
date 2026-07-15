<?php

/**
 * ECM Events Page
 *
 * Handles the global Events admin page, event list,
 * and add/edit event forms.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Events_Page
{
    /**
     * Render the main Events admin page.
     *
     * @return void
     */
    public function events_page()
    {
        ?>
        <div class="wrap ecm-wrap">
            <div class="ecm-page-header">
                <div>
                    <h1>Events</h1>

                    <p class="ecm-subtitle">
                        Create and manage events for certificate generation.
                    </p>
                </div>

                <a
                    href="<?php echo esc_url(
                        admin_url(
                            'admin.php?page=ecm-events&action=add'
                        )
                    ); ?>"
                    class="button button-primary"
                >
                    Add New Event
                </a>
            </div>

            <?php
            $action = isset($_GET['action'])
                ? sanitize_text_field(
                    wp_unslash($_GET['action'])
                )
                : '';

            if ($action === 'add') {
                $this->event_form();
            } elseif ($action === 'edit') {
                $event_id = isset($_GET['event_id'])
                    ? absint($_GET['event_id'])
                    : 0;

                $this->event_form($event_id);
            } elseif ($action === 'template_builder') {
                $event_id = isset($_GET['event_id'])
                    ? absint($_GET['event_id'])
                    : 0;

                $template_id = isset($_GET['template_id'])
                    ? absint($_GET['template_id'])
                    : 0;

                $this->render_template_builder_page(
                    $event_id,
                    $template_id
                );
            } elseif ($action === 'manage') {
                $event_id = isset($_GET['event_id'])
                    ? absint($_GET['event_id'])
                    : 0;

                $this->manage_event_page($event_id);
            } else {
                $this->events_list();
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render the event list.
     *
     * @return void
     */
    private function events_list()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';

        $events = $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY id DESC"
        );
        ?>

        <?php if (isset($_GET['created'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Event created successfully.</strong>
                </p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Event updated successfully.</strong>
                </p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Event deleted successfully.</strong>
                </p>
            </div>
        <?php endif; ?>

        <div class="ecm-panel ecm-panel-full">
            <h2>Event List</h2>

            <?php if (empty($events)) : ?>

                <p>No events created yet.</p>

                <p>
                    Click <strong>Add New Event</strong>
                    to create your first event.
                </p>

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
                            $edit_url = admin_url(
                                'admin.php?page=ecm-events'
                                . '&action=edit'
                                . '&event_id='
                                . absint($event->id)
                            );

                            $manage_url = admin_url(
                                'admin.php?page=ecm-events'
                                . '&action=manage'
                                . '&event_id='
                                . absint($event->id)
                            );

                            $delete_url = wp_nonce_url(
                                admin_url(
                                    'admin.php?page=ecm-events'
                                    . '&action=delete'
                                    . '&event_id='
                                    . absint($event->id)
                                ),
                                'ecm_delete_event_'
                                . absint($event->id)
                            );
                            ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?php echo esc_html(
                                            $event->event_code
                                        ); ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        $event->event_name
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        $event->event_type
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        $event->venue
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        $event->start_date
                                    ); ?>
                                </td>

                                <td>
                                    <span
                                        class="ecm-status ecm-status-<?php
                                        echo esc_attr(
                                            $event->status
                                        );
                                        ?>"
                                    >
                                        <?php echo esc_html(
                                            ucfirst($event->status)
                                        ); ?>
                                    </span>
                                </td>

                                <td>
                                    <a href="<?php echo esc_url($manage_url); ?>">
                                        Manage
                                    </a>

                                    |

                                    <a href="<?php echo esc_url($edit_url); ?>">
                                        Edit
                                    </a>

                                    |

                                    <a
                                        href="<?php echo esc_url($delete_url); ?>"
                                        onclick="return confirm('Are you sure you want to delete this event?');"
                                        class="ecm-danger-link"
                                    >
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

    /**
     * Render the add/edit event form.
     *
     * @param int $event_id Event ID.
     *
     * @return void
     */
    private function event_form($event_id = 0)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';
        $event = null;

        if ($event_id > 0) {
            $event = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE id = %d",
                    $event_id
                )
            );

            if (!$event) {
                echo '<div class="notice notice-error">'
                    . '<p>Event not found.</p>'
                    . '</div>';

                return;
            }
        }

        $is_edit = $event_id > 0;
        ?>

        <div class="ecm-form-header">
            <a
                href="<?php echo esc_url(
                    admin_url('admin.php?page=ecm-events')
                ); ?>"
                class="button"
            >
                ← Back to Event List
            </a>
        </div>

        <div class="ecm-panel">
            <h2>
                <?php echo esc_html(
                    $is_edit ? 'Edit Event' : 'Add New Event'
                ); ?>
            </h2>

            <?php if ($is_edit) : ?>
                <p>
                    <strong>Event Code:</strong>
                    <?php echo esc_html($event->event_code); ?>
                </p>
            <?php endif; ?>

            <form method="post">
                <?php
                wp_nonce_field(
                    'ecm_save_event',
                    'ecm_event_nonce'
                );
                ?>

                <input
                    type="hidden"
                    name="event_id"
                    value="<?php echo esc_attr($event_id); ?>"
                >

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="event_name">
                                Event Name
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="event_name"
                                name="event_name"
                                class="regular-text"
                                required
                                value="<?php echo esc_attr(
                                    $event->event_name ?? ''
                                ); ?>"
                            >

                            <p class="description">
                                Event code is generated automatically
                                and cannot be edited.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="event_type">
                                Event Type
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="event_type"
                                name="event_type"
                                class="regular-text"
                                value="<?php echo esc_attr(
                                    $event->event_type ?? ''
                                ); ?>"
                            >

                            <p class="description">
                                Example: Convention, Training,
                                Forum, Seminar.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="venue">
                                Venue
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                id="venue"
                                name="venue"
                                class="regular-text"
                                value="<?php echo esc_attr(
                                    $event->venue ?? ''
                                ); ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="start_date">
                                Start Date
                            </label>
                        </th>

                        <td>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                value="<?php echo esc_attr(
                                    $event->start_date ?? ''
                                ); ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="end_date">
                                End Date
                            </label>
                        </th>

                        <td>
                            <input
                                type="date"
                                id="end_date"
                                name="end_date"
                                value="<?php echo esc_attr(
                                    $event->end_date ?? ''
                                ); ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="status">
                                Status
                            </label>
                        </th>

                        <td>
                            <?php
                            $selected_status =
                                $event->status ?? 'draft';
                            ?>

                            <select
                                id="status"
                                name="status"
                            >
                                <option
                                    value="draft"
                                    <?php selected(
                                        $selected_status,
                                        'draft'
                                    ); ?>
                                >
                                    Draft
                                </option>

                                <option
                                    value="active"
                                    <?php selected(
                                        $selected_status,
                                        'active'
                                    ); ?>
                                >
                                    Active
                                </option>

                                <option
                                    value="closed"
                                    <?php selected(
                                        $selected_status,
                                        'closed'
                                    ); ?>
                                >
                                    Closed
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p>
                    <button
                        type="submit"
                        name="ecm_save_event_submit"
                        class="button button-primary"
                    >
                        <?php echo esc_html(
                            $is_edit
                                ? 'Update Event'
                                : 'Save Event'
                        ); ?>
                    </button>

                    <a
                        href="<?php echo esc_url(
                            admin_url(
                                'admin.php?page=ecm-events'
                            )
                        ); ?>"
                        class="button"
                    >
                        Cancel
                    </a>
                </p>
            </form>
        </div>
        <?php
    }
}