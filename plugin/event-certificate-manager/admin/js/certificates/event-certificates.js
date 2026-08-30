/**
 * ECM Event Certificates
 *
 * Handles the Event Certificates admin interface:
 *
 * - recipient search
 * - recipient selection
 * - select all
 * - certificate detail modal
 * - PDF viewing
 * - individual regeneration
 * - individual email delivery
 * - bulk regeneration
 * - bulk email delivery
 */

document.addEventListener('DOMContentLoaded', function () {

    /*
     * Certificate configuration is provided by WordPress
     * through wp_localize_script().
     */
    if (
        typeof ecmCertificateConfig === 'undefined' ||
        !ecmCertificateConfig
    ) {
        return;
    }

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

    /*
     * ---------------------------------------------------------
     * Selection helpers
     * ---------------------------------------------------------
     */

    function getVisibleRows() {
        return rows.filter(function (row) {
            return !row.hidden;
        });
    }

    function getVisibleCheckboxes() {
        return getVisibleRows()
            .map(function (row) {
                return row.querySelector(
                    '.ecm-certificate-recipient-checkbox'
                );
            })
            .filter(Boolean);
    }

    function getSelectedCheckboxes() {
        return rows
            .map(function (row) {
                return row.querySelector(
                    '.ecm-certificate-recipient-checkbox'
                );
            })
            .filter(function (checkbox) {
                return (
                    checkbox &&
                    checkbox.checked
                );
            });
    }

    function updateSelectionUI() {

        const selected =
            getSelectedCheckboxes();

        const visible =
            getVisibleCheckboxes();

        const visibleSelected =
            visible.filter(function (checkbox) {
                return checkbox.checked;
            });

        const selectedCount =
            selected.length;

        if (selectionCount) {
            selectionCount.textContent =
                selectedCount + ' selected';
        }

        const hasSelection =
            selectedCount > 0;

        if (bulkAction) {
            bulkAction.disabled =
                !hasSelection;
        }

        if (bulkApply) {
            bulkApply.disabled =
                !hasSelection;
        }

        if (!selectAll) {
            return;
        }

        if (visible.length === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;

            return;
        }

        selectAll.checked =
            visibleSelected.length ===
            visible.length;

        selectAll.indeterminate =
            visibleSelected.length > 0 &&
            visibleSelected.length <
            visible.length;
    }

    /*
     * ---------------------------------------------------------
     * Live recipient search
     * ---------------------------------------------------------
     */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            function () {

                const query =
                    searchInput.value
                        .trim()
                        .toLowerCase();

                rows.forEach(
                    function (row) {

                        const searchable =
                            (
                                row.dataset.search ||
                                ''
                            ).toLowerCase();

                        row.hidden =
                            query !== '' &&
                            !searchable.includes(
                                query
                            );
                    }
                );

                const emptyRow =
                    document.getElementById(
                        'ecm-certificate-search-empty'
                    );

                const noResults =
                    getVisibleRows().length === 0;

                if (emptyRow) {
                    emptyRow.hidden =
                        !noResults;
                }

                updateSelectionUI();
            }
        );
    }

    /*
     * ---------------------------------------------------------
     * Select / deselect all visible recipients
     * ---------------------------------------------------------
     */

    if (selectAll) {

        selectAll.addEventListener(
            'change',
            function () {

                const visibleCheckboxes =
                    getVisibleCheckboxes();

                visibleCheckboxes.forEach(
                    function (checkbox) {

                        checkbox.checked =
                            selectAll.checked;
                    }
                );

                updateSelectionUI();
            }
        );
    }

    /*
     * ---------------------------------------------------------
     * Individual recipient selection
     * ---------------------------------------------------------
     */

    rows.forEach(function (row) {

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
     * ---------------------------------------------------------
     * Certificate detail modal
     * ---------------------------------------------------------
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
                value === undefined
                ? ''
                : String(value);

        return element.innerHTML;
    }

    function openCertificateDetails(button) {

        if (
            !certificateModal ||
            !certificateDetailName ||
            !certificateDetailMeta ||
            !certificateDetailList
        ) {
            return;
        }

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

        const certificateDetails =
            ecmCertificateConfig
                .certificateDetails || {};

        const certificates =
            certificateDetails[
            participantId
            ] || [];

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
                certificates
                    .map(
                        function (certificate) {

                            const emailed =
                                certificate
                                    .emailed_at
                                    ? (
                                        '<span ' +
                                        'class="' +
                                        'ecm-status ' +
                                        'ecm-status-emailed' +
                                        '">' +
                                        'Mailed' +
                                        '</span>'
                                    )
                                    : '';

                            const verified =
                                Number(
                                    certificate
                                        .verification_count
                                ) > 0
                                    ? (
                                        '<span ' +
                                        'class="' +
                                        'ecm-status ' +
                                        'ecm-status-verified' +
                                        '">' +
                                        'Verified' +
                                        '</span>'
                                    )
                                    : '';

                            const viewPdf =
                                certificate.pdf_url
                                    ? (
                                        '<a ' +
                                        'class="' +
                                        'button ' +
                                        'button-primary' +
                                        '" ' +
                                        'target="_blank" ' +
                                        'rel="noopener noreferrer" ' +
                                        'href="' +
                                        escapeHtml(
                                            certificate
                                                .pdf_url
                                        ) +
                                        '">' +
                                        'View PDF' +
                                        '</a>'
                                    )
                                    : (
                                        '<button ' +
                                        'class="button" ' +
                                        'disabled>' +
                                        'PDF unavailable' +
                                        '</button>'
                                    );

                            const regenerateForm =
                                buildRegenerateForm(
                                    certificate
                                );

                            const emailForm =
                                buildEmailForm(
                                    certificate
                                );

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

                                '<h4 style="' +
                                'margin:0 0 6px;' +
                                '">' +

                                escapeHtml(
                                    certificate
                                        .scope
                                ) +

                                '</h4>' +

                                '<div ' +
                                'class="description"' +
                                '>' +

                                'Certificate ID: ' +

                                '<code>' +
                                escapeHtml(
                                    certificate
                                        .certificate_id
                                ) +
                                '</code>' +

                                '</div>' +

                                '</div>' +

                                '<div>' +

                                '<span ' +
                                'class="' +
                                'ecm-status ' +
                                'ecm-status-generated' +
                                '"' +
                                '>' +
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

                                regenerateForm +

                                emailForm +

                                '</div>' +

                                '</div>'
                            );
                        }
                    )
                    .join('');
        }

        certificateModal.style.display =
            'flex';
    }

    /*
     * ---------------------------------------------------------
     * Individual regenerate form
     * ---------------------------------------------------------
     */

    function buildRegenerateForm(
        certificate
    ) {

        return (
            '<form ' +
            'method="post" ' +
            'action="' +
            escapeHtml(
                ecmCertificateConfig
                    .adminPostUrl
            ) +
            '" ' +
            'style="display:inline;"' +
            '>' +

            '<input ' +
            'type="hidden" ' +
            'name="action" ' +
            'value="' +
            'ecm_regenerate_event_certificate' +
            '"' +
            '>' +

            '<input ' +
            'type="hidden" ' +
            'name="certificate_id" ' +
            'value="' +
            escapeHtml(
                certificate.id
            ) +
            '"' +
            '>' +

            '<input ' +
            'type="hidden" ' +
            'name="event_id" ' +
            'value="' +
            escapeHtml(
                ecmCertificateConfig
                    .eventId
            ) +
            '"' +
            '>' +

            '<input ' +
            'type="hidden" ' +
            'name="' +
            'ecm_regenerate_certificate_nonce' +
            '" ' +
            'value="' +
            escapeHtml(
                certificate
                    .regenerate_nonce
            ) +
            '"' +
            '>' +

            '<button ' +
            'type="submit" ' +
            'class="button" ' +
            'data-ecm-regenerate-certificate' +
            '>' +

            'Regenerate' +

            '</button>' +

            '</form>'
        );
    }

    /*
     * ---------------------------------------------------------
     * Individual Send / Resend form
     * ---------------------------------------------------------
     */

    function buildEmailForm(
        certificate
    ) {

        const label =
            certificate.emailed_at
                ? 'Resend Email'
                : 'Send Email';

        return (
            '<form ' +
            'method="post" ' +
            'action="' +
            escapeHtml(
                ecmCertificateConfig
                    .adminPostUrl
            ) +
            '" ' +
            'style="display:inline;"' +
            '>' +

            '<input ' +
            'type="hidden" ' +
            'name="action" ' +
            'value="' +
            'ecm_send_event_certificate_email' +
            '"' +
            '>' +

            '<input ' +
            'type="hidden" ' +
            'name="certificate_id" ' +
            'value="' +
            escapeHtml(
                certificate.id
            ) +
            '"' +
            '>' +

            '<input ' +
            'type="hidden" ' +
            'name="event_id" ' +
            'value="' +
            escapeHtml(
                ecmCertificateConfig
                    .eventId
            ) +
            '"' +
            '>' +

            '<input ' +
            'type="hidden" ' +
            'name="' +
            'ecm_send_certificate_email_nonce' +
            '" ' +
            'value="' +
            escapeHtml(
                certificate
                    .email_nonce
            ) +
            '"' +
            '>' +

            '<button ' +
            'type="submit" ' +
            'class="button"' +
            '>' +

            escapeHtml(label) +

            '</button>' +

            '</form>'
        );
    }

    /*
     * ---------------------------------------------------------
     * Open participant certificate modal
     * ---------------------------------------------------------
     */

    certificateViewButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function () {

                    openCertificateDetails(
                        button
                    );
                }
            );
        }
    );

    /*
     * ---------------------------------------------------------
     * Certificate regeneration confirmation
     *
     * Forms inside the modal are created dynamically,
     * therefore event delegation is used.
     * ---------------------------------------------------------
     */

    if (certificateDetailList) {

        certificateDetailList.addEventListener(
            'click',
            function (event) {

                const regenerateButton =
                    event.target.closest(
                        '[data-ecm-regenerate-certificate]'
                    );

                if (!regenerateButton) {
                    return;
                }

                const confirmed =
                    window.confirm(
                        'Regenerate this certificate? ' +
                        'The existing PDF will be replaced.'
                    );

                if (!confirmed) {
                    event.preventDefault();
                }
            }
        );
    }

    /*
     * ---------------------------------------------------------
     * Close modal
     * ---------------------------------------------------------
     */

    function closeCertificateDetails() {

        if (!certificateModal) {
            return;
        }

        certificateModal.style.display =
            'none';
    }

    if (certificateModalClose) {

        certificateModalClose.addEventListener(
            'click',
            closeCertificateDetails
        );
    }

    if (certificateModal) {

        certificateModal.addEventListener(
            'click',
            function (event) {

                if (
                    event.target ===
                    certificateModal
                ) {
                    closeCertificateDetails();
                }
            }
        );
    }

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                certificateModal &&
                certificateModal.style.display ===
                'flex'
            ) {
                closeCertificateDetails();
            }
        }
    );

    /*
     * ---------------------------------------------------------
     * Helper for dynamically-generated POST forms
     * ---------------------------------------------------------
     */

    function addHiddenField(
        form,
        name,
        value
    ) {

        const input =
            document.createElement('input');

        input.type =
            'hidden';

        input.name =
            name;

        input.value =
            value;

        form.appendChild(
            input
        );
    }

    /*
     * ---------------------------------------------------------
     * Bulk actions
     * ---------------------------------------------------------
     */

    if (bulkApply) {

        bulkApply.addEventListener(
            'click',
            function () {

                const action =
                    bulkAction
                        ? bulkAction.value
                        : '';

                if (!action) {
                    return;
                }

                const selected =
                    getSelectedCheckboxes();

                if (
                    selected.length === 0
                ) {
                    return;
                }

                /*
                 * Bulk regeneration.
                 */
                if (
                    action ===
                    'regenerate'
                ) {

                    const confirmed =
                        window.confirm(
                            'Regenerate all certificates ' +
                            'for the selected recipients? ' +
                            'Existing PDF files will be replaced.'
                        );

                    if (!confirmed) {
                        return;
                    }

                    submitBulkRegeneration(
                        selected
                    );

                    return;
                }

                /*
                 * Bulk email delivery.
                 */
                if (
                    action ===
                    'email'
                ) {

                    const confirmed =
                        window.confirm(
                            'Send all certificates belonging ' +
                            'to the selected recipients?'
                        );

                    if (!confirmed) {
                        return;
                    }

                    submitBulkEmail(
                        selected
                    );
                }
            }
        );
    }

    /*
     * ---------------------------------------------------------
     * Submit bulk regeneration
     * ---------------------------------------------------------
     */

    function submitBulkRegeneration(
        selected
    ) {

        const form =
            document.createElement(
                'form'
            );

        form.method =
            'post';

        form.action =
            ecmCertificateConfig
                .adminPostUrl;

        addHiddenField(
            form,
            'action',
            'ecm_bulk_regenerate_event_certificates'
        );

        addHiddenField(
            form,
            'event_id',
            ecmCertificateConfig
                .eventId
        );

        addHiddenField(
            form,
            'ecm_bulk_regenerate_nonce',
            ecmCertificateConfig
                .bulkRegenerateNonce
        );

        selected.forEach(
            function (checkbox) {

                addHiddenField(
                    form,
                    'participant_ids[]',
                    checkbox.value
                );
            }
        );

        document.body.appendChild(
            form
        );

        form.submit();
    }

    /*
     * ---------------------------------------------------------
     * Submit bulk email delivery
     * ---------------------------------------------------------
     */

    function submitBulkEmail(
        selected
    ) {

        const form =
            document.createElement(
                'form'
            );

        form.method =
            'post';

        form.action =
            ecmCertificateConfig
                .adminPostUrl;

        addHiddenField(
            form,
            'action',
            'ecm_bulk_send_event_certificate_emails'
        );

        addHiddenField(
            form,
            'event_id',
            ecmCertificateConfig
                .eventId
        );

        addHiddenField(
            form,
            'ecm_bulk_send_email_nonce',
            ecmCertificateConfig
                .bulkEmailNonce
        );

        selected.forEach(
            function (checkbox) {

                addHiddenField(
                    form,
                    'participant_ids[]',
                    checkbox.value
                );
            }
        );

        document.body.appendChild(
            form
        );

        form.submit();
    }

    /*
     * Initial interface state.
     */
    updateSelectionUI();
});