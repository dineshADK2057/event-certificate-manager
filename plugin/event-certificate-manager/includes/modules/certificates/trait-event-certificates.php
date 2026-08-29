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
        global $wpdb;

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        $templates_table =
            $wpdb->prefix . 'ecm_templates';

        $sessions_table =
            $wpdb->prefix . 'ecm_sessions';

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $participant_meta_table =
            $wpdb->prefix . 'ecm_participant_meta';


        /*
        * Load templates available for this event.
        */
        $event_templates = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
            t.*,
            s.session_name

        FROM {$templates_table} t

        LEFT JOIN {$sessions_table} s
            ON s.id = t.session_id

        WHERE t.event_id = %d

        ORDER BY t.id DESC",
                $event->id
            )
        );

        /*
        * Load participants associated with this event.
        *
        * member_name is obtained from participant metadata for
        * administrator-friendly selection labels.
        */
        $event_participants = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
            p.id,
            p.member_id,
            name_meta.meta_value AS member_name

            FROM {$participants_table} p

            INNER JOIN {$event_participants_table} ep
                ON ep.participant_id = p.id

            LEFT JOIN {$participant_meta_table} name_meta
                ON name_meta.participant_id = p.id
                AND name_meta.meta_key = 'member_name'

            WHERE ep.event_id = %d

            ORDER BY
            name_meta.meta_value ASC,
            p.member_id ASC",
                $event->id
            )
        );



        /*
         * Event certificate statistics.
         */
        $total_certificates = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$certificates_table}
                WHERE event_id = %d",
                $event->id
            )
        );

        $generated_certificates = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$certificates_table}
                WHERE event_id = %d
                AND status = %s",
                $event->id,
                'generated'
            )
        );

        $emailed_certificates = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$certificates_table}
                WHERE event_id = %d
                AND emailed_at IS NOT NULL",
                $event->id
            )
        );

        /*
        * Load one participant-centric certificate summary row.
        *
        * Individual certificates remain independent database records.
        * The Event Certificates UI aggregates those records by participant
        * to avoid displaying duplicate participant rows.
        */
        $certificate_participants = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
            p.id AS participant_id,
            p.member_id,

            COALESCE(
                name_meta.meta_value,
                MAX(c.recipient_name)
            ) AS member_name,

            club_meta.meta_value AS home_club,

            COALESCE(
                email_meta.meta_value,
                MAX(c.recipient_email)
            ) AS email,

            COUNT(c.id) AS certificate_count,

            GROUP_CONCAT(
                DISTINCT
                CASE
                    WHEN c.session_id IS NULL
                        THEN 'Event-wide'
                    ELSE s.session_name
                END
                ORDER BY
                    CASE
                        WHEN c.session_id IS NULL THEN 0
                        ELSE 1
                    END,
                    s.session_name ASC
                SEPARATOR ', '
            ) AS certificate_scopes,

            MAX(
                CASE
                    WHEN c.emailed_at IS NOT NULL THEN 1
                    ELSE 0
                END
            ) AS has_emailed,

            MAX(
                CASE
                    WHEN c.verification_count > 0 THEN 1
                    ELSE 0
                END
            ) AS has_verified

            FROM {$certificates_table} c

            INNER JOIN {$participants_table} p
                ON p.id = c.participant_id

            LEFT JOIN {$sessions_table} s
                ON s.id = c.session_id

            LEFT JOIN {$participant_meta_table} name_meta
                ON name_meta.participant_id = p.id
                AND name_meta.meta_key = 'member_name'

            LEFT JOIN {$participant_meta_table} club_meta
                ON club_meta.participant_id = p.id
                AND club_meta.meta_key = 'home_club'

            LEFT JOIN {$participant_meta_table} email_meta
                ON email_meta.participant_id = p.id
                AND email_meta.meta_key = 'email'

            WHERE c.event_id = %d

            GROUP BY
                p.id,
                p.member_id,
                name_meta.meta_value,
                club_meta.meta_value,
                email_meta.meta_value

            ORDER BY
                name_meta.meta_value ASC,
                p.member_id ASC",
                $event->id
            )
        );

        /*
        * Load the individual certificate records used by the
        * participant certificate-detail modal.
        */
        $individual_certificates = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
            c.*,
            s.session_name

            FROM {$certificates_table} c

            LEFT JOIN {$sessions_table} s
                ON s.id = c.session_id

            WHERE c.event_id = %d

            ORDER BY
                c.participant_id ASC,
                c.id DESC",
                $event->id
            )
        );

        /*
        * Build participant-indexed certificate data for the
        * client-side certificate detail viewer.
        */
        $certificate_details = [];

        $upload_dir =
            wp_upload_dir();

        foreach ($individual_certificates as $certificate) {

            $participant_id =
                (int) $certificate->participant_id;

            if (
                !isset(
                    $certificate_details[$participant_id]
                )
            ) {
                $certificate_details[$participant_id] = [];
            }

            $pdf_url = '';

            if (
                !empty($certificate->pdf_file) &&
                empty($upload_dir['error'])
            ) {
                $pdf_url =
                    trailingslashit(
                        $upload_dir['baseurl']
                    )
                    . ltrim(
                        $certificate->pdf_file,
                        '/'
                    );
            }

            $certificate_details[$participant_id][] = [
                'id' =>
                (int) $certificate->id,

                'certificate_id' =>
                (string) $certificate->certificate_id,

                'scope' =>
                !empty($certificate->session_name)
                    ? (string) $certificate->session_name
                    : 'Event-wide',

                'status' =>
                !empty($certificate->status)
                    ? (string) $certificate->status
                    : 'generated',

                'generated_at' =>
                !empty($certificate->generated_at)
                    ? (string) $certificate->generated_at
                    : '',

                'emailed_at' =>
                !empty($certificate->emailed_at)
                    ? (string) $certificate->emailed_at
                    : '',

                'verification_count' =>
                (int) $certificate->verification_count,

                'pdf_url' =>
                $pdf_url,

                // 'regenerate_url' =>
                // wp_nonce_url(
                //     admin_url(
                //         'admin-post.php'
                //             . '?action=ecm_regenerate_event_certificate'
                //             . '&certificate_id='
                //             . (int) $certificate->id
                //             . '&event_id='
                //             . (int) $certificate->event_id
                //     ),
                //     'ecm_regenerate_certificate_'
                //         . (int) $certificate->id,
                //     'ecm_regenerate_certificate_nonce'
                // ),

                'record_id' =>
                (int) $certificate->id,

                'regenerate_nonce' =>
                wp_create_nonce(
                    'ecm_regenerate_certificate_'
                        . (int) $certificate->id
                ),

                'email_nonce' =>
                wp_create_nonce(
                    'ecm_send_certificate_email_'
                        . (int) $certificate->id
                ),
            ];
        }

?>

        <div class="ecm-tab-header">

            <div>
                <h2>Certificates</h2>

                <p>
                    Manage generated certificates for this event.
                </p>
            </div>

        </div>

        <?php if (isset($_GET['certificate_generated'])) : ?>

            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        Certificate generated successfully.
                    </strong>

                    <?php if (!empty($_GET['certificate_id'])) : ?>
                        Certificate ID:
                        <code>
                            <?php
                            echo esc_html(
                                sanitize_text_field(
                                    wp_unslash(
                                        $_GET['certificate_id']
                                    )
                                )
                            );
                            ?>
                        </code>
                    <?php endif; ?>
                </p>
            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET['certificate_regenerated'])
        ) : ?>

            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        Certificate regenerated successfully.
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET['certificate_regeneration_error'])
        ) : ?>

            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>
                        The certificate could not be regenerated.
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET['certificate_emailed'])
        ) : ?>

            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        Certificate email sent successfully.
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET['certificate_email_error'])
        ) : ?>

            <?php
            $email_error =
                sanitize_key(
                    wp_unslash(
                        $_GET['certificate_email_error']
                    )
                );

            $email_error_messages = [
                'ecm_email_recipient_missing' =>
                'This certificate does not have a valid recipient email address.',

                'ecm_email_pdf_missing' =>
                'This certificate does not have a generated PDF.',

                'ecm_email_pdf_not_found' =>
                'The generated certificate PDF could not be found.',

                'ecm_email_delivery_failed' =>
                'WordPress could not send the certificate email.',

                'ecm_email_snapshot_update_failed' =>
                'The participant email was found, but the certificate recipient email could not be updated.',

                'ecm_email_database_update_failed' =>
                'The email was sent, but the delivery status could not be updated.',
            ];

            $email_error_message =
                isset(
                    $email_error_messages[$email_error]
                )
                ? $email_error_messages[$email_error]
                : 'The certificate email could not be sent.';
            ?>

            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>
                        <?php
                        echo esc_html(
                            $email_error_message
                        );
                        ?>
                    </strong>
                </p>
            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET['certificate_generation_error'])
        ) : ?>

            <?php
            $generation_error =
                sanitize_key(
                    wp_unslash(
                        $_GET['certificate_generation_error']
                    )
                );

            $generation_error_messages = [
                'ecm_certificate_already_exists' =>
                'This participant already has a certificate generated with the selected template.',

                'ecm_generation_participant_not_found' =>
                'The selected participant does not belong to this event.',

                'ecm_generation_template_not_found' =>
                'The selected template could not be loaded.',

                'ecm_certificate_pdf_write_failed' =>
                'The certificate was rendered but could not be saved.',

                'ecm_certificate_record_creation_failed' =>
                'The PDF was generated, but the certificate database record could not be created.',

                'ecm_certificate_generation_failed' =>
                'Certificate generation failed unexpectedly.',

                'ecm_certificate_session_participant_required' =>
                'This participant is not assigned to the session required by the selected certificate template.',
            ];

            $generation_error_message =
                isset(
                    $generation_error_messages[$generation_error]
                )
                ? $generation_error_messages[$generation_error]
                : 'The certificate could not be generated.';
            ?>

            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>
                        <?php
                        echo esc_html(
                            $generation_error_message
                        );
                        ?>
                    </strong>
                </p>
            </div>

        <?php endif; ?>

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
        <script>
            window.ecmCertificateDetails =
                <?php
                echo wp_json_encode(
                    $certificate_details,
                    JSON_HEX_TAG |
                        JSON_HEX_AMP |
                        JSON_HEX_APOS |
                        JSON_HEX_QUOT
                );
                ?>;
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const table =
                    document.getElementById(
                        'ecm-certificate-recipients-table'
                    );

                if (!table) {
                    return;
                }

                const searchInput =
                    document.getElementById(
                        'ecm-certificate-recipient-search'
                    );

                const selectAll =
                    document.getElementById(
                        'ecm-select-all-certificates'
                    );

                const bulkAction =
                    document.getElementById(
                        'ecm-certificate-bulk-action'
                    );

                const bulkApply =
                    document.getElementById(
                        'ecm-apply-certificate-bulk-action'
                    );

                const selectionCount =
                    document.getElementById(
                        'ecm-certificate-selection-count'
                    );

                const rows =
                    Array.from(
                        table.querySelectorAll(
                            '.ecm-certificate-recipient-row'
                        )
                    );

                function getVisibleRows() {
                    return rows.filter(function(row) {
                        return !row.hidden;
                    });
                }

                function getVisibleCheckboxes() {
                    return getVisibleRows()
                        .map(function(row) {
                            return row.querySelector(
                                '.ecm-certificate-recipient-checkbox'
                            );
                        })
                        .filter(Boolean);
                }

                function getSelectedCheckboxes() {
                    return rows
                        .map(function(row) {
                            return row.querySelector(
                                '.ecm-certificate-recipient-checkbox'
                            );
                        })
                        .filter(function(checkbox) {
                            return checkbox && checkbox.checked;
                        });
                }

                /*
                 * Participant certificate detail viewer.
                 */
                const certificateModal =
                    document.getElementById(
                        'ecm-certificate-detail-modal'
                    );

                const certificateDetailName =
                    document.getElementById(
                        'ecm-certificate-detail-name'
                    );

                const certificateDetailMeta =
                    document.getElementById(
                        'ecm-certificate-detail-meta'
                    );

                const certificateDetailList =
                    document.getElementById(
                        'ecm-certificate-detail-list'
                    );

                const certificateModalClose =
                    document.getElementById(
                        'ecm-close-certificate-detail'
                    );

                const certificateViewButtons =
                    document.querySelectorAll(
                        '.ecm-view-recipient-certificates'
                    );

                function escapeHtml(value) {

                    const element =
                        document.createElement('div');

                    element.textContent =
                        value === null ||
                        value === undefined ?
                        '' :
                        String(value);

                    return element.innerHTML;
                }

                function openCertificateDetails(button) {

                    const participantId =
                        button.dataset.participantId;

                    const memberId =
                        button.dataset.memberId || '';

                    const memberName =
                        button.dataset.memberName ||
                        'Unknown participant';

                    const homeClub =
                        button.dataset.homeClub || '—';

                    const email =
                        button.dataset.email || '—';

                    const certificates =
                        (
                            window.ecmCertificateDetails &&
                            window.ecmCertificateDetails[
                                participantId
                            ]
                        ) ?
                        window.ecmCertificateDetails[
                            participantId
                        ] : [];

                    certificateDetailName.textContent =
                        memberName;

                    certificateDetailMeta.innerHTML =
                        '<strong>Member ID:</strong> ' +
                        escapeHtml(memberId) +
                        ' &nbsp; | &nbsp; ' +
                        '<strong>Home Club:</strong> ' +
                        escapeHtml(homeClub) +
                        ' &nbsp; | &nbsp; ' +
                        '<strong>Email:</strong> ' +
                        escapeHtml(email);

                    if (certificates.length === 0) {

                        certificateDetailList.innerHTML =
                            '<p>No certificates were found.</p>';

                    } else {

                        certificateDetailList.innerHTML =
                            certificates.map(
                                function(certificate) {

                                    const emailed =
                                        certificate.emailed_at ?
                                        '<span class="ecm-status ecm-status-emailed">Mailed</span>' :
                                        '';

                                    const verified =
                                        Number(
                                            certificate.verification_count
                                        ) > 0 ?
                                        '<span class="ecm-status ecm-status-verified">Verified</span>' :
                                        '';

                                    const viewPdf =
                                        certificate.pdf_url ?
                                        '<a class="button button-primary" target="_blank" href="' +
                                        escapeHtml(
                                            certificate.pdf_url
                                        ) +
                                        '">View PDF</a>' :
                                        '<button class="button" disabled>PDF unavailable</button>';

                                    return (
                                        '<div style="' +
                                        'border:1px solid #dcdcde;' +
                                        'padding:18px;' +
                                        'margin-bottom:14px;' +
                                        'border-radius:4px;' +
                                        '">' +

                                        '<div style="' +
                                        'display:flex;' +
                                        'justify-content:space-between;' +
                                        'align-items:flex-start;' +
                                        'gap:15px;' +
                                        '">' +

                                        '<div>' +

                                        '<h4 style="margin:0 0 6px;">' +
                                        escapeHtml(
                                            certificate.scope
                                        ) +
                                        '</h4>' +

                                        '<div class="description">' +
                                        'Certificate ID: <code>' +
                                        escapeHtml(
                                            certificate.certificate_id
                                        ) +
                                        '</code>' +
                                        '</div>' +

                                        '</div>' +

                                        '<div>' +

                                        '<span class="ecm-status ecm-status-generated">' +
                                        'Generated' +
                                        '</span> ' +

                                        emailed +
                                        ' ' +
                                        verified +

                                        '</div>' +

                                        '</div>' +

                                        '<div style="' +
                                        'margin-top:16px;' +
                                        'display:flex;' +
                                        'gap:8px;' +
                                        'flex-wrap:wrap;' +
                                        '">' +

                                        viewPdf +

                                        '<form ' +
                                        'method="post" ' +
                                        'action="<?php echo esc_url(admin_url('admin-post.php')); ?>" ' +
                                        'style="display:inline;"' +
                                        '>' +

                                        '<input ' +
                                        'type="hidden" ' +
                                        'name="action" ' +
                                        'value="ecm_regenerate_event_certificate"' +
                                        '>' +

                                        '<input ' +
                                        'type="hidden" ' +
                                        'name="certificate_id" ' +
                                        'value="' +
                                        escapeHtml(certificate.id) +
                                        '"' +
                                        '>' +

                                        '<input ' +
                                        'type="hidden" ' +
                                        'name="event_id" ' +
                                        'value="<?php echo esc_js((string) $event->id); ?>"' +
                                        '>' +

                                        '<input ' +
                                        'type="hidden" ' +
                                        'name="ecm_regenerate_certificate_nonce" ' +
                                        'value="' +
                                        escapeHtml(
                                            certificate.regenerate_nonce
                                        ) +
                                        '"' +
                                        '>' +

                                        '<button ' +
                                        'type="submit" ' +
                                        'class="button" ' +
                                        'onclick="return confirm(' +
                                        "'Regenerate this certificate? The existing PDF will be replaced.'" +
                                        ');"' +
                                        '>' +
                                        'Regenerate' +
                                        '</button>' +

                                        '</form>' +

                                        '<form ' +
                                        'method="post" ' +
                                        'action="<?php echo esc_url(admin_url('admin-post.php')); ?>" ' +
                                        'style="display:inline;"' +
                                        '>' +

                                        '<input ' +
                                        'type="hidden" ' +
                                        'name="action" ' +
                                        'value="ecm_send_event_certificate_email"' +
                                        '>' +

                                        '<input ' +
                                        'type="hidden" ' +
                                        'name="certificate_id" ' +
                                        'value="' +
                                        escapeHtml(certificate.id) +
                                        '"' +
                                        '>' +

                                        '<input ' +
                                        'type="hidden" ' +
                                        'name="event_id" ' +
                                        'value="<?php echo esc_js((string) $event->id); ?>"' +
                                        '>' +

                                        '<input ' +
                                        'type="hidden" ' +
                                        'name="ecm_send_certificate_email_nonce" ' +
                                        'value="' +
                                        escapeHtml(
                                            certificate.email_nonce
                                        ) +
                                        '"' +
                                        '>' +

                                        '<button ' +
                                        'type="submit" ' +
                                        'class="button" ' +
                                        '>' +
                                        (
                                            certificate.emailed_at ?
                                            'Resend Email' :
                                            'Send Email'
                                        ) +
                                        '</button>' +

                                        '</form>' +

                                        '</div>' +

                                        '</div>'
                                    );
                                }
                            ).join('');
                    }

                    certificateModal.style.display =
                        'flex';
                }

                function closeCertificateDetails() {

                    certificateModal.style.display =
                        'none';
                }

                certificateViewButtons.forEach(
                    function(button) {

                        button.addEventListener(
                            'click',
                            function() {
                                openCertificateDetails(
                                    button
                                );
                            }
                        );
                    }
                );

                certificateModalClose.addEventListener(
                    'click',
                    closeCertificateDetails
                );

                certificateModal.addEventListener(
                    'click',
                    function(event) {

                        if (
                            event.target ===
                            certificateModal
                        ) {
                            closeCertificateDetails();
                        }
                    }
                );

                document.addEventListener(
                    'keydown',
                    function(event) {

                        if (
                            event.key === 'Escape' &&
                            certificateModal.style.display ===
                            'flex'
                        ) {
                            closeCertificateDetails();
                        }
                    }
                );



                function updateSelectionUI() {

                    const selected =
                        getSelectedCheckboxes();

                    const visible =
                        getVisibleCheckboxes();

                    const visibleSelected =
                        visible.filter(function(checkbox) {
                            return checkbox.checked;
                        });

                    const selectedCount =
                        selected.length;

                    selectionCount.textContent =
                        selectedCount +
                        (
                            selectedCount === 1 ?
                            ' selected' :
                            ' selected'
                        );

                    const hasSelection =
                        selectedCount > 0;

                    bulkAction.disabled = !hasSelection;

                    bulkApply.disabled = !hasSelection;

                    if (visible.length === 0) {
                        selectAll.checked = false;
                        selectAll.indeterminate = false;
                        return;
                    }

                    selectAll.checked =
                        visibleSelected.length === visible.length;

                    selectAll.indeterminate =
                        visibleSelected.length > 0 &&
                        visibleSelected.length < visible.length;
                }

                /*
                 * Live recipient search.
                 */
                searchInput.addEventListener(
                    'input',
                    function() {

                        const query =
                            searchInput.value
                            .trim()
                            .toLowerCase();

                        rows.forEach(function(row) {

                            const searchable =
                                (
                                    row.dataset.search || ''
                                ).toLowerCase();

                            row.hidden =
                                query !== '' &&
                                !searchable.includes(query);
                        });
                        const emptyRow =
                            document.getElementById(
                                'ecm-certificate-search-empty'
                            );

                        if (emptyRow) {
                            emptyRow.hidden =
                                getVisibleRows().length !== 0;
                        }

                        updateSelectionUI();
                    }
                );

                /*
                 * Select/deselect all currently visible recipients.
                 */
                selectAll.addEventListener(
                    'change',
                    function() {

                        const visibleCheckboxes =
                            getVisibleCheckboxes();

                        visibleCheckboxes.forEach(
                            function(checkbox) {
                                checkbox.checked =
                                    selectAll.checked;
                            }
                        );

                        updateSelectionUI();
                    }
                );

                /*
                 * Individual recipient selection.
                 */
                rows.forEach(function(row) {

                    const checkbox =
                        row.querySelector(
                            '.ecm-certificate-recipient-checkbox'
                        );

                    if (!checkbox) {
                        return;
                    }

                    checkbox.addEventListener(
                        'change',
                        updateSelectionUI
                    );
                });

                /*
                 * Backend bulk actions are intentionally deferred.
                 *
                 * The toolbar is being established now so both future
                 * bulk actions can reuse the same certificate services
                 * as the individual certificate actions.
                 */
                bulkApply.addEventListener(
                    'click',
                    function() {

                        const action =
                            bulkAction.value;

                        if (!action) {
                            return;
                        }

                        const selected =
                            getSelectedCheckboxes();

                        if (selected.length === 0) {
                            return;
                        }

                        if (action === 'regenerate') {
                            window.alert(
                                'Bulk regeneration will be enabled after the individual regeneration workflow is completed.'
                            );

                            return;
                        }

                        if (action === 'email') {
                            window.alert(
                                'Bulk email delivery will be enabled after the individual email workflow is completed.'
                            );
                        }
                    }
                );

                updateSelectionUI();
            });
        </script>

<?php
    }

    /**
     * Generate one certificate from the Event Certificates tab.
     *
     * @return void
     */
    public function handle_generate_event_certificate()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to generate certificates.'
            );
        }

        check_admin_referer(
            'ecm_generate_event_certificate',
            'ecm_generate_certificate_nonce'
        );

        $event_id = isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        $participant_id = isset($_POST['participant_id'])
            ? absint($_POST['participant_id'])
            : 0;

        $template_id = isset($_POST['template_id'])
            ? absint($_POST['template_id'])
            : 0;

        if (
            !$event_id ||
            !$participant_id ||
            !$template_id
        ) {
            wp_die(
                'Event, participant, and template are required.'
            );
        }

        global $wpdb;

        $templates_table =
            $wpdb->prefix . 'ecm_templates';

        /*
     * Load the selected template while simultaneously
     * establishing that it belongs to this event.
     */
        $template = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, event_id, session_id
            FROM {$templates_table}
            WHERE id = %d
            AND event_id = %d
            LIMIT 1",
                $template_id,
                $event_id
            )
        );

        if (!$template) {
            wp_die(
                'The selected certificate template does not belong to this event.'
            );
        }

        /*
     * Session-specific templates determine their own session.
     * Event-wide templates use NULL.
     */
        $session_id = !empty($template->session_id)
            ? absint($template->session_id)
            : null;

        $result = ECM_Certificate_Generator::generate(
            [
                'event_id' =>
                $event_id,

                'participant_id' =>
                $participant_id,

                'template_id' =>
                $template_id,

                'session_id' =>
                $session_id,

                'force' =>
                false,
            ]
        );

        $redirect_url = admin_url(
            'admin.php?page=ecm-events'
                . '&action=manage'
                . '&event_id=' . $event_id
                . '&tab=certificates'
        );

        if (is_wp_error($result)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'certificate_generation_error' =>
                        $result->get_error_code(),
                    ],
                    $redirect_url
                )
            );

            exit;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'certificate_generated' => 1,
                    'certificate_id' =>
                    $result->certificate_id,
                ],
                $redirect_url
            )
        );

        exit;
    }

    /**
     * Regenerate one existing certificate.
     *
     * @return void
     */
    public function handle_regenerate_event_certificate()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to regenerate certificates.'
            );
        }

        $certificate_record_id =
            isset($_POST['certificate_id'])
            ? absint($_POST['certificate_id'])
            : 0;

        $event_id =
            isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        if (
            !$certificate_record_id ||
            !$event_id
        ) {
            wp_die(
                'Invalid certificate regeneration request.'
            );
        }

        check_admin_referer(
            'ecm_regenerate_certificate_'
                . $certificate_record_id,
            'ecm_regenerate_certificate_nonce'
        );

        global $wpdb;

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        /*
     * Prevent regeneration of a certificate belonging to
     * another event through a manipulated request.
     */
        $belongs_to_event =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                FROM {$certificates_table}
                WHERE id = %d
                AND event_id = %d
                LIMIT 1",
                    $certificate_record_id,
                    $event_id
                )
            );

        if (!$belongs_to_event) {
            wp_die(
                'The selected certificate does not belong to this event.'
            );
        }

        $result =
            ECM_Certificate_Generator::regenerate(
                $certificate_record_id
            );

        $redirect_url =
            admin_url(
                'admin.php?page=ecm-events'
                    . '&action=manage'
                    . '&event_id=' . $event_id
                    . '&tab=certificates'
            );

        if (is_wp_error($result)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'certificate_regeneration_error' =>
                        $result->get_error_code(),
                    ],
                    $redirect_url
                )
            );

            exit;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'certificate_regenerated' => 1,
                ],
                $redirect_url
            )
        );

        exit;
    }

    /**
     * Send or resend one existing certificate by email.
     *
     * @return void
     */
    public function handle_send_event_certificate_email()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission to send certificate emails.'
            );
        }

        $certificate_record_id =
            isset($_POST['certificate_id'])
            ? absint($_POST['certificate_id'])
            : 0;

        $event_id =
            isset($_POST['event_id'])
            ? absint($_POST['event_id'])
            : 0;

        if (
            !$certificate_record_id ||
            !$event_id
        ) {
            wp_die(
                'Invalid certificate email request.'
            );
        }

        check_admin_referer(
            'ecm_send_certificate_email_'
                . $certificate_record_id,
            'ecm_send_certificate_email_nonce'
        );

        global $wpdb;

        $certificates_table =
            $wpdb->prefix . 'ecm_certificates';

        /*
     * Ensure the certificate belongs to the current event.
     */
        $belongs_to_event =
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                FROM {$certificates_table}
                WHERE id = %d
                AND event_id = %d
                LIMIT 1",
                    $certificate_record_id,
                    $event_id
                )
            );

        if (!$belongs_to_event) {
            wp_die(
                'The selected certificate does not belong to this event.'
            );
        }

        $result =
            ECM_Certificate_Email_Delivery::send(
                $certificate_record_id
            );

        $redirect_url =
            admin_url(
                'admin.php?page=ecm-events'
                    . '&action=manage'
                    . '&event_id=' . $event_id
                    . '&tab=certificates'
            );

        if (is_wp_error($result)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'certificate_email_error' =>
                        $result->get_error_code(),
                    ],
                    $redirect_url
                )
            );

            exit;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'certificate_emailed' => 1,
                ],
                $redirect_url
            )
        );

        exit;
    }
}
