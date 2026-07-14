/**
 * ECM Template Builder Font Picker
 *
 * Provides searchable font selection and live font previews.
 */
(function ($, window) {
    'use strict';

    const Builder = window.ECMBuilder || {};

    if (!Builder.isActive || !Builder.isActive()) {
        return;
    }

    /**
     * Close a font picker.
     *
     * @param {jQuery} picker
     */
    function closePicker(picker) {
        picker
            .find('.ecm-font-picker-dropdown')
            .prop('hidden', true);

        picker
            .find('.ecm-font-picker-trigger')
            .attr('aria-expanded', 'false');

        picker.removeClass('is-open');
    }

    /**
     * Open a font picker.
     *
     * @param {jQuery} picker
     */
    function openPicker(picker) {
        $('.ecm-font-picker').each(function () {
            const otherPicker = $(this);

            if (!otherPicker.is(picker)) {
                closePicker(otherPicker);
            }
        });

        picker
            .find('.ecm-font-picker-dropdown')
            .prop('hidden', false);

        picker
            .find('.ecm-font-picker-trigger')
            .attr('aria-expanded', 'true');

        picker.addClass('is-open');

        const search = picker.find('.ecm-font-picker-search');

        search.val('');
        filterFonts(picker, '');
        search.trigger('focus');
    }

    /**
     * Filter displayed fonts.
     *
     * @param {jQuery} picker
     * @param {string} query
     */
    function filterFonts(picker, query) {
        const normalizedQuery = String(query || '')
            .trim()
            .toLowerCase();

        const options = picker.find('.ecm-font-picker-option');

        options.each(function () {
            const option = $(this);
            const family = String(
                option.data('font-family') || ''
            ).toLowerCase();

            option.toggle(
                normalizedQuery === '' ||
                family.includes(normalizedQuery)
            );
        });

        picker
            .find('.ecm-font-picker-group-label')
            .each(function () {
                const label = $(this);
                const source = label.data('font-group');

                const visibleOptions = picker.find(
                    '.ecm-font-picker-option' +
                    '[data-font-source="' + source + '"]:visible'
                );

                label.toggle(visibleOptions.length > 0);
            });

        let emptySearch = picker.find(
            '.ecm-font-picker-no-results'
        );

        if (!emptySearch.length) {
            emptySearch = $(
                '<div class="ecm-font-picker-no-results">' +
                'No matching fonts found.' +
                '</div>'
            );

            picker
                .find('.ecm-font-picker-options')
                .append(emptySearch);
        }

        emptySearch.toggle(
            options.filter(':visible').length === 0
        );
    }

    /**
     * Set a picker value and update its visible preview.
     *
     * @param {string} pickerId
     * @param {string} fontFamily
     * @param {boolean} triggerChange
     */
    Builder.setFontPickerValue = function (
        pickerId,
        fontFamily,
        triggerChange = false
    ) {
        const picker = $('#' + pickerId);

        if (!picker.length) {
            return;
        }

        const normalizedFamily = String(
            fontFamily || 'Arial'
        );

        picker
            .find('input[type="hidden"]')
            .val(normalizedFamily);

        picker
            .find('.ecm-font-picker-current')
            .text(normalizedFamily)
            .css(
                'font-family',
                '"' + normalizedFamily + '", sans-serif'
            );

        picker
            .find('.ecm-font-picker-option')
            .removeClass('is-selected')
            .attr('aria-selected', 'false');

        picker
            .find('.ecm-font-picker-option')
            .filter(function () {
                return String(
                    $(this).data('font-family')
                ) === normalizedFamily;
            })
            .addClass('is-selected')
            .attr('aria-selected', 'true');

        if (triggerChange) {
            picker
                .find('input[type="hidden"]')
                .trigger('change');
        }
    };

    /**
     * Toggle picker dropdown.
     */
    $(document).on(
        'click',
        '.ecm-font-picker-trigger',
        function (event) {
            event.preventDefault();

            const picker = $(this).closest('.ecm-font-picker');

            if (picker.hasClass('is-open')) {
                closePicker(picker);
                return;
            }

            openPicker(picker);
        }
    );

    /**
     * Search available fonts.
     */
    $(document).on(
        'input',
        '.ecm-font-picker-search',
        function () {
            const picker = $(this).closest('.ecm-font-picker');

            filterFonts(picker, $(this).val());
        }
    );

    /**
     * Select a font.
     */
    $(document).on(
        'click',
        '.ecm-font-picker-option',
        function (event) {
            event.preventDefault();

            const option = $(this);
            const picker = option.closest('.ecm-font-picker');
            const fontFamily = option.data('font-family');

            Builder.setFontPickerValue(
                picker.attr('id'),
                fontFamily,
                true
            );

            closePicker(picker);
        }
    );

    /**
     * Close pickers when clicking elsewhere.
     */
    $(document).on('click', function (event) {
        if ($(event.target).closest('.ecm-font-picker').length) {
            return;
        }

        $('.ecm-font-picker').each(function () {
            closePicker($(this));
        });
    });

    /**
     * Close active picker with Escape.
     */
    $(document).on('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        $('.ecm-font-picker.is-open').each(function () {
            closePicker($(this));
        });
    });

    /**
     * Initialize the current visible value.
     */
    $('.ecm-font-picker').each(function () {
        const picker = $(this);
        const fontFamily =
            picker.find('input[type="hidden"]').val() ||
            'Arial';

        Builder.setFontPickerValue(
            picker.attr('id'),
            fontFamily,
            false
        );
    });
})(jQuery, window);