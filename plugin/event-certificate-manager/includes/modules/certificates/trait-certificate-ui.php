<?php

/**
 * ECM Certificate UI
 *
 * Renders event-scoped certificate recipient management UI.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Certificate_UI
{
    /**
     * Render certificate recipients table and detail modal.
     *
     * @param object $event
     * @param array  $certificate_participants
     * @param array  $certificate_details
     *
     * @return void
     */
    private function render_certificate_recipients_ui(
        $event,
        $certificate_participants,
        $certificate_details
    ) {
?>

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
                        <tr
                            id="ecm-certificate-search-empty"
                            hidden>
                            <td
                                colspan="7"
                                style="text-align: center;">
                                No certificate recipients match your search.
                            </td>
                        </tr>

                        <?php foreach (
                            $certificate_participants
                            as $recipient
                        ) : ?>

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
