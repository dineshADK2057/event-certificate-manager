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

                            if (
                                !window.confirm(
                                    'Regenerate all certificates for the selected recipients? Existing PDF files will be replaced.'
                                )
                            ) {
                                return;
                            }

                            const form =
                                document.createElement('form');

                            form.method = 'post';

                            form.action =
                                '<?php echo esc_url(
                                        admin_url('admin-post.php')
                                    ); ?>';

                            function addField(name, value) {

                                const input =
                                    document.createElement('input');

                                input.type = 'hidden';
                                input.name = name;
                                input.value = value;

                                form.appendChild(input);
                            }

                            addField(
                                'action',
                                'ecm_bulk_regenerate_event_certificates'
                            );

                            addField(
                                'event_id',
                                '<?php echo esc_js(
                                        (string) absint($event->id)
                                    ); ?>'
                            );

                            addField(
                                'ecm_bulk_regenerate_nonce',
                                '<?php echo esc_js(
                                        wp_create_nonce(
                                            'ecm_bulk_regenerate_certificates_'
                                                . absint($event->id)
                                        )
                                    ); ?>'
                            );

                            selected.forEach(
                                function(checkbox) {

                                    addField(
                                        'participant_ids[]',
                                        checkbox.value
                                    );
                                }
                            );

                            document.body.appendChild(form);

                            form.submit();

                            return;
                        }

                        if (action === 'email') {

                            if (
                                !window.confirm(
                                    'Send all certificates belonging to the selected recipients?'
                                )
                            ) {
                                return;
                            }

                            const form =
                                document.createElement('form');

                            form.method = 'post';

                            form.action =
                                '<?php echo esc_url(
                                        admin_url('admin-post.php')
                                    ); ?>';

                            function addField(name, value) {

                                const input =
                                    document.createElement('input');

                                input.type = 'hidden';
                                input.name = name;
                                input.value = value;

                                form.appendChild(input);
                            }

                            addField(
                                'action',
                                'ecm_bulk_send_event_certificate_emails'
                            );

                            addField(
                                'event_id',
                                '<?php echo esc_js(
                                        (string) absint($event->id)
                                    ); ?>'
                            );

                            addField(
                                'ecm_bulk_send_email_nonce',
                                '<?php echo esc_js(
                                        wp_create_nonce(
                                            'ecm_bulk_send_certificate_emails_'
                                                . absint($event->id)
                                        )
                                    ); ?>'
                            );

                            selected.forEach(
                                function(checkbox) {

                                    addField(
                                        'participant_ids[]',
                                        checkbox.value
                                    );
                                }
                            );

                            document.body.appendChild(form);

                            form.submit();

                            return;
                        }
                    }
                );

                updateSelectionUI();
            });
        </script>

<?php
    }
}
