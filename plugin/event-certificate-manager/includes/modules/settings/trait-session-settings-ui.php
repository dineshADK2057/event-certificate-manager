<?php

/**
 * ECM Session Settings UI
 *
 * Renders event-scoped session settings for certificate generation,
 * QR verification, and optional participant capacity.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Session_Settings_UI
{
    /**
     * Render the session-settings section inside Event Settings.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_event_session_settings_section($event)
    {
        global $wpdb;

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $sessions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$sessions_table}
                WHERE event_id = %d
                ORDER BY id DESC",
                $event->id
            )
        );
        ?>

        <div class="ecm-panel ecm-panel-full">
            <h3>Session Settings</h3>

            <p class="description">
                Configure certificate, QR verification, and capacity
                settings for each session.
            </p>

            <?php if (empty($sessions)) : ?>

                <p>
                    No sessions were found. Create a session before
                    configuring session-specific settings.
                </p>

            <?php else : ?>

                <?php
                $selected_session_id =
                    isset($_GET['settings_session_id'])
                        ? absint($_GET['settings_session_id'])
                        : (int) $sessions[0]->id;
                ?>

                <form
                    method="get"
                    class="ecm-session-settings-selector"
                >
                    <input
                        type="hidden"
                        name="page"
                        value="ecm-events"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="manage"
                    >

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?php echo esc_attr($event->id); ?>"
                    >

                    <input
                        type="hidden"
                        name="tab"
                        value="settings"
                    >

                    <label for="ecm_settings_session_id">
                        <strong>Select Session</strong>
                    </label>

                    <select
                        id="ecm_settings_session_id"
                        name="settings_session_id"
                    >
                        <?php foreach ($sessions as $session) : ?>
                            <option
                                value="<?php echo esc_attr(
                                    $session->id
                                ); ?>"
                                <?php selected(
                                    $selected_session_id,
                                    $session->id
                                ); ?>
                            >
                                <?php
                                echo esc_html(
                                    $session->session_code
                                    . ' - '
                                    . $session->session_name
                                );
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button
                        type="submit"
                        class="button"
                    >
                        Load Settings
                    </button>
                </form>

                <?php
                $selected_session = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT *
                        FROM {$sessions_table}
                        WHERE id = %d
                        AND event_id = %d",
                        $selected_session_id,
                        $event->id
                    )
                );

                if ($selected_session) {
                    $this->render_session_settings_panel(
                        $event,
                        $selected_session
                    );
                }
                ?>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render settings for one event session.
     *
     * @param object $event   Event database record.
     * @param object $session Session database record.
     *
     * @return void
     */
    private function render_session_settings_panel(
        $event,
        $session
    ) {
        $settings = get_option(
            'ecm_session_settings_' . $session->id,
            []
        );

        $certificate_enabled = isset(
            $settings['certificate_enabled']
        )
            ? (int) $settings['certificate_enabled']
            : 0;

        $qr_enabled = isset($settings['qr_enabled'])
            ? (int) $settings['qr_enabled']
            : 1;

        $capacity = isset($settings['capacity'])
            ? absint($settings['capacity'])
            : '';
        ?>

        <div class="ecm-session-settings-panel">
            <form method="post">
                <?php
                wp_nonce_field(
                    'ecm_save_session_settings',
                    'ecm_session_settings_nonce'
                );
                ?>

                <input
                    type="hidden"
                    name="event_id"
                    value="<?php echo esc_attr($event->id); ?>"
                >

                <input
                    type="hidden"
                    name="session_id"
                    value="<?php echo esc_attr($session->id); ?>"
                >

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            Certificate Generation
                        </th>

                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="certificate_enabled"
                                    value="1"
                                    <?php checked(
                                        $certificate_enabled,
                                        1
                                    ); ?>
                                >

                                Enable certificate generation for
                                this session
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            QR Verification
                        </th>

                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="qr_enabled"
                                    value="1"
                                    <?php checked(
                                        $qr_enabled,
                                        1
                                    ); ?>
                                >

                                Enable QR verification for
                                session certificates
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="session_capacity">
                                Session Capacity
                            </label>
                        </th>

                        <td>
                            <input
                                type="number"
                                id="session_capacity"
                                name="capacity"
                                value="<?php echo esc_attr($capacity); ?>"
                                min="0"
                                class="small-text"
                            >

                            <p class="description">
                                Leave empty or use 0 for unlimited capacity.
                            </p>
                        </td>
                    </tr>
                </table>

                <p>
                    <button
                        type="submit"
                        name="ecm_save_session_settings_submit"
                        class="button button-primary"
                    >
                        Save Session Settings
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}