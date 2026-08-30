<?php

/**
 * ECM Event Certificates
 *
 * Renders and manages certificates belonging to one event.
 *
 * Certificate rendering itself remains inside the dedicated
 * certificate engine. This trait is responsible only for the
 * event-scoped certificate management interface.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Certificates
{
    /**
     * Render the Certificates tab for one event.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function tab_certificates($event)
    {
        $page_data =
            $this->get_event_certificate_page_data(
                $event
            );

        $event_templates =
            $page_data['event_templates'];

        $event_participants =
            $page_data['event_participants'];

        $total_certificates =
            $page_data['total_certificates'];

        $generated_certificates =
            $page_data['generated_certificates'];

        $emailed_certificates =
            $page_data['emailed_certificates'];

        $certificate_participants =
            $page_data['certificate_participants'];

        $certificate_details =
            $page_data['certificate_details'];

?>


        <!-- notices -->
        <?php
        $this->render_certificate_notices();
        ?>





        <div class="ecm-panel ecm-panel-full">

            <h3>Generate Certificate</h3>

            <p class="description">
                Generate and store a certificate for one participant
                using a template belonging to this event.
            </p>

            <?php if (empty($event_participants)) : ?>

                <div class="notice notice-warning inline">
                    <p>
                        Add at least one participant to this event
                        before generating certificates.
                    </p>
                </div>

            <?php elseif (empty($event_templates)) : ?>

                <div class="notice notice-warning inline">
                    <p>
                        Create at least one certificate template
                        before generating certificates.
                    </p>
                </div>

            <?php else : ?>

                <form
                    method="post"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

                    <input
                        type="hidden"
                        name="action"
                        value="ecm_generate_event_certificate">

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?php echo esc_attr($event->id); ?>">

                    <?php
                    wp_nonce_field(
                        'ecm_generate_event_certificate',
                        'ecm_generate_certificate_nonce'
                    );
                    ?>

                    <table class="form-table">

                        <tbody>

                            <tr>

                                <th scope="row">
                                    <label for="ecm-certificate-participant">
                                        Participant
                                    </label>
                                </th>

                                <td>

                                    <select
                                        name="participant_id"
                                        id="ecm-certificate-participant"
                                        required>

                                        <option value="">
                                            Select participant
                                        </option>

                                        <?php foreach (
                                            $event_participants
                                            as $participant
                                        ) : ?>

                                            <option
                                                value="<?php echo esc_attr($participant->id); ?>">
                                                <?php
                                                $participant_label =
                                                    !empty($participant->member_name)
                                                    ? $participant->member_name
                                                    : 'Participant';

                                                echo esc_html(
                                                    $participant_label
                                                        . ' — '
                                                        . $participant->member_id
                                                );
                                                ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </td>

                            </tr>

                            <tr>

                                <th scope="row">
                                    <label for="ecm-certificate-template">
                                        Template
                                    </label>
                                </th>

                                <td>

                                    <select
                                        name="template_id"
                                        id="ecm-certificate-template"
                                        required>

                                        <option value="">
                                            Select template
                                        </option>

                                        <?php foreach (
                                            $event_templates
                                            as $template
                                        ) : ?>

                                            <option
                                                value="<?php echo esc_attr($template->id); ?>">
                                                <?php
                                                $template_label =
                                                    $template->template_name;

                                                if (
                                                    !empty($template->session_name)
                                                ) {
                                                    $template_label .=
                                                        ' — '
                                                        . $template->session_name;
                                                } else {
                                                    $template_label .=
                                                        ' — Event-wide';
                                                }

                                                echo esc_html(
                                                    $template_label
                                                );
                                                ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                    <?php
                    submit_button(
                        'Generate Certificate',
                        'primary',
                        'submit',
                        false
                    );
                    ?>

                </form>

            <?php endif; ?>

        </div>

        <div class="ecm-panel">

            <h3>Certificate Statistics</h3>

            <div class="ecm-stat-grid">

                <div class="ecm-stat-card">

                    <span class="ecm-stat-number">
                        <?php
                        echo esc_html(
                            $total_certificates
                        );
                        ?>
                    </span>

                    <span class="ecm-stat-label">
                        Total Certificates
                    </span>

                </div>

                <div class="ecm-stat-card">

                    <span class="ecm-stat-number">
                        <?php
                        echo esc_html(
                            $generated_certificates
                        );
                        ?>
                    </span>

                    <span class="ecm-stat-label">
                        Generated
                    </span>

                </div>

                <div class="ecm-stat-card">

                    <span class="ecm-stat-number">
                        <?php
                        echo esc_html(
                            $emailed_certificates
                        );
                        ?>
                    </span>

                    <span class="ecm-stat-label">
                        Emailed
                    </span>

                </div>

            </div>

        </div>

        <div class="ecm-panel ecm-panel-full">

            <div class="ecm-panel-header">

                <div>
                    <h3>Certificate Recipients</h3>

                    <p>
                        View and manage certificate activity
                        for participants in this event.
                    </p>
                </div>

            </div>

            <?php if (empty($certificate_participants)) : ?>

                <p>
                    No certificates have been generated for this event yet.
                </p>

            <?php else : ?>

                <div
                    class="ecm-certificate-recipient-toolbar"
                    style="
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin: 16px 0;
        flex-wrap: wrap;
    ">

                    <div
                        style="
            display: flex;
            align-items: center;
            gap: 8px;
        ">

                        <select
                            id="ecm-certificate-bulk-action"
                            disabled>
                            <option value="">
                                Bulk Actions
                            </option>

                            <option value="regenerate">
                                Regenerate Selected
                            </option>

                            <option value="email">
                                Send Email to Selected
                            </option>
                        </select>

                        <button
                            type="button"
                            class="button"
                            id="ecm-apply-certificate-bulk-action"
                            disabled>
                            Apply
                        </button>

                        <span
                            id="ecm-certificate-selection-count"
                            class="description">
                            0 selected
                        </span>

                    </div>

                    <div>

                        <input
                            type="search"
                            id="ecm-certificate-recipient-search"
                            class="regular-text"
                            placeholder="Search recipients..."
                            autocomplete="off">

                    </div>

                </div>

                <table class="widefat striped" id="ecm-certificate-recipients-table">

                    <thead>

                        <tr>

                            <th style="width: 40px;">
                                <input
                                    type="checkbox"
                                    id="ecm-select-all-certificates">
                            </th>

                            <th>Recipient</th>

                            <th>Home Club</th>

                            <th>Email</th>

                            <th>Certificates / Sessions</th>

                            <th>Status</th>

                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach (
                            $certificate_participants
                            as $recipient
                        ) : ?>
                            <tr
                                id="ecm-certificate-search-empty"
                                hidden>
                                <td
                                    colspan="7"
                                    style="text-align: center;">
                                    No certificate recipients match your search.
                                </td>
                            </tr>

                            <tr
                                class="ecm-certificate-recipient-row"
                                data-search="<?php
                                                echo esc_attr(
                                                    strtolower(
                                                        implode(
                                                            ' ',
                                                            [
                                                                (string) $recipient->member_id,
                                                                (string) $recipient->member_name,
                                                                (string) $recipient->home_club,
                                                                (string) $recipient->email,
                                                                (string) $recipient->certificate_scopes,
                                                            ]
                                                        )
                                                    )
                                                );
                                                ?>">

                                <td>

                                    <input
                                        type="checkbox"
                                        class="ecm-certificate-recipient-checkbox"
                                        name="participant_ids[]"
                                        value="<?php echo esc_attr(
                                                    $recipient->participant_id
                                                ); ?>">

                                </td>

                                <td>

                                    <span>
                                        <?php
                                        echo esc_html(
                                            $recipient->member_id
                                        );
                                        ?>
                                    </span>

                                    <br>

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            !empty($recipient->member_name)
                                                ? $recipient->member_name
                                                : 'Unknown participant'
                                        );
                                        ?>
                                    </strong>

                                </td>

                                <td>

                                    <?php
                                    echo !empty($recipient->home_club)
                                        ? esc_html(
                                            $recipient->home_club
                                        )
                                        : '—';
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    echo !empty($recipient->email)
                                        ? esc_html(
                                            $recipient->email
                                        )
                                        : '—';
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    echo !empty($recipient->certificate_scopes)
                                        ? esc_html(
                                            $recipient->certificate_scopes
                                        )
                                        : 'Event-wide';
                                    ?>

                                </td>

                                <td>

                                    <span class="ecm-status ecm-status-generated">
                                        Generated
                                    </span>

                                    <?php if (
                                        !empty($recipient->has_emailed)
                                    ) : ?>

                                        <span class="ecm-status ecm-status-emailed">
                                            Mailed
                                        </span>

                                    <?php endif; ?>

                                    <?php if (
                                        !empty($recipient->has_verified)
                                    ) : ?>

                                        <span class="ecm-status ecm-status-verified">
                                            Verified
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="ecm-view-recipient-certificates"
                                        data-participant-id="<?php echo esc_attr(
                                                                    $recipient->participant_id
                                                                ); ?>"
                                        data-member-id="<?php echo esc_attr(
                                                            $recipient->member_id
                                                        ); ?>"
                                        data-member-name="<?php echo esc_attr(
                                                                $recipient->member_name
                                                            ); ?>"
                                        data-home-club="<?php echo esc_attr(
                                                            $recipient->home_club
                                                        ); ?>"
                                        data-email="<?php echo esc_attr(
                                                        $recipient->email
                                                    ); ?>"
                                        data-tooltip="View Certificate"
                                        aria-label="View Certificate">
                                        <span
                                            class="dashicons dashicons-visibility"
                                            aria-hidden="true"></span>
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

        <div
            id="ecm-certificate-detail-modal"
            style="
        display: none;
        position: fixed;
        inset: 0;
        z-index: 100000;
        background: rgba(0, 0, 0, 0.45);
        align-items: center;
        justify-content: center;
        padding: 30px;
        box-sizing: border-box;
    ">

            <div
                style="
            background: #fff;
            width: 100%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
            border-radius: 6px;
            box-shadow: 0 10px 40px rgba(0,0,0,.25);
        ">

                <div
                    style="
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 24px;
                border-bottom: 1px solid #dcdcde;
            ">

                    <div>

                        <h2
                            id="ecm-certificate-detail-name"
                            style="margin: 0 0 8px;"></h2>

                        <div
                            id="ecm-certificate-detail-meta"
                            class="description"></div>

                    </div>

                    <button
                        type="button"
                        id="ecm-close-certificate-detail"
                        class="button"
                        aria-label="Close">
                        ✕
                    </button>

                </div>

                <div style="padding: 24px;">

                    <h3 style="margin-top: 0;">
                        Certificates
                    </h3>

                    <div
                        id="ecm-certificate-detail-list"></div>

                </div>

            </div>

        </div>



<?php


        wp_localize_script(
            'ecm-event-certificates',
            'ecmCertificateConfig',
            [
                'adminPostUrl' =>
                admin_url(
                    'admin-post.php'
                ),

                'eventId' =>
                absint(
                    $event->id
                ),

                'bulkRegenerateNonce' =>
                wp_create_nonce(
                    'ecm_bulk_regenerate_certificates_'
                        . absint(
                            $event->id
                        )
                ),

                'bulkEmailNonce' =>
                wp_create_nonce(
                    'ecm_bulk_send_certificate_emails_'
                        . absint(
                            $event->id
                        )
                ),

                'certificateDetails' =>
                $certificate_details,
            ]
        );
    }
}
