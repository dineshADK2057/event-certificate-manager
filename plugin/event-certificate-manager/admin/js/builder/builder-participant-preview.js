(function ($, window) {
    'use strict';


    const Builder = window.ECMBuilder;


    if (!Builder) {
        return;
    }


    /* =========================================================
     * State
     * ========================================================= */

    Builder.state = Builder.state || {};

    Builder.state.previewParticipantId = null;
    Builder.state.previewParticipantValues = {};


    let activeOptionIndex = -1;


    /* =========================================================
     * Helpers
     * ========================================================= */

    function getValue(values, key) {

        if (
            !values ||
            typeof values !== 'object' ||
            !Object.prototype.hasOwnProperty.call(
                values,
                key
            )
        ) {
            return '';
        }


        const value = values[key];


        if (
            value === null ||
            typeof value === 'undefined'
        ) {
            return '';
        }


        return String(value);
    }


    function getParticipantName(values) {

        return (
            getValue(values, 'member_name') ||
            getValue(values, 'name') ||
            '—'
        );
    }


    function getParticipantClub(values) {

        return (
            getValue(values, 'home_club') ||
            getValue(values, 'club') ||
            '—'
        );
    }


    function getParticipantEmail(values) {

        return (
            getValue(values, 'email') ||
            '—'
        );
    }


    function getOptionValues($option) {

        if (!$option.length) {
            return {};
        }


        const raw =
            $option.attr(
                'data-preview-values'
            );


        if (!raw) {
            return {};
        }


        try {

            return JSON.parse(raw) || {};

        } catch (error) {

            console.error(
                'ECM Builder: Invalid participant preview data.',
                error
            );

            return {};
        }
    }


    /* =========================================================
     * Participant Details
     * ========================================================= */

    function updateParticipantDetails(values) {

        const participantName =
            getParticipantName(values);


        const memberId =
            getValue(
                values,
                'member_id'
            );


        const homeClub =
            getParticipantClub(values);


        const email =
            getValue(
                values,
                'email'
            );


        /*
         * Member name.
         */

        $('#ecm_preview_participant_name')
            .text(
                participantName || '—'
            );


        /*
         * Member ID.
         */

        $('#ecm_preview_participant_member_id')
            .text(
                memberId || '—'
            );


        /*
         * Home club.
         */

        $('#ecm_preview_participant_home_club')
            .text(
                homeClub || '—'
            );


        /*
         * Email.
         *
         * Show it only when the participant actually
         * has an email address.
         */

        const $email =
            $('#ecm_preview_participant_email');


        if (email) {

            $email
                .text(email)
                .attr(
                    'href',
                    'mailto:' + email
                )
                .show();

        } else {

            $email
                .text('')
                .attr(
                    'href',
                    ''
                )
                .hide();
        }
    }


    /* =========================================================
     * Canvas Participant Elements
     * ========================================================= */

    function updateParticipantElements(values) {

        $('.ecm-builder-element-frame').each(
            function () {

                const $element =
                    $(this);


                const sourceType =
                    String(
                        $element.attr(
                            'data-source-type'
                        ) || ''
                    ).toLowerCase();


                if (sourceType !== 'participant') {
                    return;
                }


                const placeholderKey =
                    $element.attr(
                        'data-placeholder-key'
                    );


                if (!placeholderKey) {
                    return;
                }


                let value =
                    getValue(
                        values,
                        placeholderKey
                    );


                /*
                 * Defensive aliases.
                 */

                if (
                    !value &&
                    placeholderKey === 'member_name'
                ) {

                    value =
                        getParticipantName(values);
                }


                if (
                    !value &&
                    placeholderKey === 'home_club'
                ) {

                    value =
                        getParticipantClub(values);
                }


                /*
                 * Keep empty participant fields identifiable.
                 */

                if (
                    !value ||
                    value === '—'
                ) {

                    value =
                        '{' +
                        placeholderKey +
                        '}';
                }


                $element
                    .find(
                        '.ecm-builder-element-content'
                    )
                    .first()
                    .text(value);
            }
        );
    }


    /* =========================================================
     * QR Preview Context
     * ========================================================= */

    function updateQrPreviewContext(
        participantId
    ) {

        $(
            '.ecm-builder-element-frame[data-element-type="qr"]'
        )
            .attr(
                'data-preview-participant-id',
                participantId || ''
            )
            .data(
                'preview-participant-id',
                participantId || ''
            );
    }


    /* =========================================================
     * Dropdown
     * ========================================================= */

    function openDropdown() {

        const $dropdown =
            $('#ecm_builder_participant_dropdown');


        if (!$dropdown.length) {
            return;
        }


        $dropdown.show();


        $('#ecm_builder_participant_trigger')
            .attr(
                'aria-expanded',
                'true'
            )
            .addClass('is-open');


        $('#ecm_builder_participant_search')
            .val('')
            .trigger('input')
            .trigger('focus');


        activeOptionIndex = -1;
    }


    function closeDropdown() {

        $('#ecm_builder_participant_dropdown')
            .hide();


        $('#ecm_builder_participant_trigger')
            .attr(
                'aria-expanded',
                'false'
            )
            .removeClass('is-open');


        clearKeyboardHighlight();
    }


    function toggleDropdown() {

        const $dropdown =
            $('#ecm_builder_participant_dropdown');


        if ($dropdown.is(':visible')) {

            closeDropdown();

        } else {

            openDropdown();
        }
    }


    /* =========================================================
     * Selection
     * ========================================================= */

    function selectParticipant($option) {

        if (
            !$option ||
            !$option.length
        ) {
            return;
        }


        const participantId =
            parseInt(
                $option.attr(
                    'data-participant-id'
                ),
                10
            ) || 0;


        const participantLabel =
            $option.attr(
                'data-participant-label'
            ) || 'Participant';


        const values =
            getOptionValues(
                $option
            );


        /*
         * Hidden value.
         */

        $('#ecm_builder_preview_participant')
            .val(participantId);


        /*
         * Trigger label.
         */

        $('#ecm_builder_participant_trigger_text')
            .text(participantLabel);


        /*
         * Selected state.
         */

        $('.ecm-builder-participant-option')
            .removeClass('is-selected')
            .attr(
                'aria-selected',
                'false'
            );


        $option
            .addClass('is-selected')
            .attr(
                'aria-selected',
                'true'
            );


        /*
         * Builder state.
         */

        Builder.state.previewParticipantId =
            participantId;


        Builder.state.previewParticipantValues =
            values;


        /*
         * Update preview.
         */

        updateParticipantDetails(
            values
        );


        updateParticipantElements(
            values
        );


        updateQrPreviewContext(
            participantId
        );


        /*
         * Allow future QR module to react.
         */

        $(document).trigger(
            'ecm:builder:participant-preview-changed',
            [
                participantId,
                values
            ]
        );


        closeDropdown();
    }


    /* =========================================================
     * Search
     * ========================================================= */

    function filterOptions() {

        const query =
            String(
                $('#ecm_builder_participant_search')
                    .val() || ''
            )
                .trim()
                .toLowerCase();


        let visibleCount = 0;


        $('.ecm-builder-participant-option')
            .each(
                function () {

                    const $option =
                        $(this);


                    const search =
                        String(
                            $option.attr(
                                'data-search'
                            ) || ''
                        ).toLowerCase();


                    const matches =
                        !query ||
                        search.includes(query);


                    $option.toggle(
                        matches
                    );


                    if (matches) {
                        visibleCount++;
                    }
                }
            );


        $('#ecm_builder_participant_no_results')
            .toggle(
                visibleCount === 0
            );


        activeOptionIndex = -1;

        clearKeyboardHighlight();
    }


    /* =========================================================
     * Keyboard Navigation
     * ========================================================= */

    function getVisibleOptions() {

        return $(
            '.ecm-builder-participant-option:visible'
        );
    }


    function clearKeyboardHighlight() {

        $('.ecm-builder-participant-option')
            .removeClass(
                'is-keyboard-active'
            );
    }


    function highlightOption(index) {

        const $options =
            getVisibleOptions();


        if (!$options.length) {
            return;
        }


        if (index < 0) {
            index = $options.length - 1;
        }


        if (index >= $options.length) {
            index = 0;
        }


        activeOptionIndex =
            index;


        clearKeyboardHighlight();


        const $option =
            $options.eq(
                activeOptionIndex
            );


        $option.addClass(
            'is-keyboard-active'
        );


        const optionElement =
            $option.get(0);


        if (
            optionElement &&
            typeof optionElement.scrollIntoView ===
            'function'
        ) {

            optionElement.scrollIntoView({
                block: 'nearest'
            });
        }
    }


    /* =========================================================
     * Events
     * ========================================================= */

    $(document).on(
        'click',
        '#ecm_builder_participant_trigger',
        function (event) {

            event.preventDefault();

            event.stopPropagation();

            toggleDropdown();
        }
    );


    $(document).on(
        'click',
        '.ecm-builder-participant-option',
        function (event) {

            event.preventDefault();

            event.stopPropagation();

            selectParticipant(
                $(this)
            );
        }
    );


    $(document).on(
        'input',
        '#ecm_builder_participant_search',
        function () {

            filterOptions();
        }
    );


    /*
     * Prevent clicks inside dropdown from closing it.
     */

    $(document).on(
        'click',
        '#ecm_builder_participant_dropdown',
        function (event) {

            event.stopPropagation();
        }
    );


    /*
     * Close when clicking elsewhere.
     */

    $(document).on(
        'click',
        function () {

            closeDropdown();
        }
    );


    /*
     * Keyboard controls.
     */

    $(document).on(
        'keydown',
        '#ecm_builder_participant_search',
        function (event) {

            const $options =
                getVisibleOptions();


            if (
                event.key ===
                'ArrowDown'
            ) {

                event.preventDefault();

                highlightOption(
                    activeOptionIndex + 1
                );

                return;
            }


            if (
                event.key ===
                'ArrowUp'
            ) {

                event.preventDefault();

                highlightOption(
                    activeOptionIndex - 1
                );

                return;
            }


            if (
                event.key ===
                'Enter'
            ) {

                if (
                    activeOptionIndex < 0 ||
                    !$options.length
                ) {
                    return;
                }


                event.preventDefault();


                selectParticipant(
                    $options.eq(
                        activeOptionIndex
                    )
                );

                return;
            }


            if (
                event.key ===
                'Escape'
            ) {

                event.preventDefault();

                closeDropdown();


                $('#ecm_builder_participant_trigger')
                    .trigger('focus');
            }
        }
    );


    /* =========================================================
     * Initialization
     * ========================================================= */

    $(function () {

        const $firstParticipant =
            $('.ecm-builder-participant-option')
                .first();


        if (!$firstParticipant.length) {
            return;
        }


        /*
         * Default preview participant.
         *
         * Preview only — nothing is persisted.
         */

        selectParticipant(
            $firstParticipant
        );
    });


})(jQuery, window);