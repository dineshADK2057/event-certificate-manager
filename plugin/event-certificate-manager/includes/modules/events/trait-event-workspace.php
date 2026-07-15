<?php

/**
 * ECM Event Workspace
 *
 * Handles the event management workspace, event header,
 * tab navigation, and event-tab routing.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Workspace
{
    /**
     * Render the management workspace for one event.
     *
     * @param int $event_id Event ID.
     *
     * @return void
     */
    private function manage_event_page($event_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'ecm_events';

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

        $current_tab = isset($_GET['tab'])
            ? sanitize_key(wp_unslash($_GET['tab']))
            : 'overview';

        $tabs = [
            'overview'     => 'Overview',
            'participants' => 'Participants',
            'sessions'     => 'Sessions',
            'templates'    => 'Templates',
            'certificates' => 'Certificates',
            'logs'         => 'Logs',
            'settings'     => 'Settings',
        ];

        if (!array_key_exists($current_tab, $tabs)) {
            $current_tab = 'overview';
        }
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

        <div class="ecm-event-heading">
            <div>
                <h2>
                    <?php echo esc_html($event->event_name); ?>
                </h2>

                <p>
                    <strong>Event Code:</strong>
                    <?php echo esc_html($event->event_code); ?>

                    &nbsp; | &nbsp;

                    <strong>Status:</strong>

                    <span
                        class="ecm-status ecm-status-<?php
                        echo esc_attr($event->status);
                        ?>"
                    >
                        <?php echo esc_html(
                            ucfirst($event->status)
                        ); ?>
                    </span>
                </p>
            </div>

            <a
                href="<?php echo esc_url(
                    admin_url(
                        'admin.php?page=ecm-events'
                        . '&action=edit'
                        . '&event_id='
                        . absint($event->id)
                    )
                ); ?>"
                class="button"
            >
                Edit Event
            </a>
        </div>

        <nav class="nav-tab-wrapper ecm-tabs">
            <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                <?php
                $tab_url = admin_url(
                    'admin.php?page=ecm-events'
                    . '&action=manage'
                    . '&event_id='
                    . absint($event->id)
                    . '&tab='
                    . sanitize_key($tab_key)
                );
                ?>

                <a
                    href="<?php echo esc_url($tab_url); ?>"
                    class="nav-tab <?php
                    echo $current_tab === $tab_key
                        ? 'nav-tab-active'
                        : '';
                    ?>"
                >
                    <?php echo esc_html($tab_label); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="ecm-panel ecm-panel-full ecm-tab-content">
            <?php
            $this->render_event_tab(
                $current_tab,
                $event
            );
            ?>
        </div>
        <?php
    }

    /**
     * Route one event workspace tab to its module.
     *
     * @param string $tab   Current tab key.
     * @param object $event Event record.
     *
     * @return void
     */
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
}