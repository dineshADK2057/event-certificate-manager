/**
 * ECM Template Builder Font Picker
 *
 * Provides searchable font selection and live font previews.
 */
(function ($, window) {
    'use strict';

    const Builder = window.ECMBuilder || {};
    const loadedPreviewFonts = new Set();
    const fontInstallRequests = new Map();

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
            .find('.ecm-font-picker-category-label')
            .each(function () {
                const label = $(this);
                const category = String(
                    label.data('font-category') || ''
                );

                let current = label.next();
                let hasVisibleOption = false;

                while (
                    current.length &&
                    !current.hasClass('ecm-font-picker-category-label') &&
                    !current.hasClass('ecm-font-picker-group-label')
                ) {
                    if (
                        current.hasClass('ecm-font-picker-option') &&
                        current.is(':visible')
                    ) {
                        hasVisibleOption = true;
                        break;
                    }

                    current = current.next();
                }

                label.toggle(hasVisibleOption);
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
    /**
 * Set the selected font picker value and update its visible preview.
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

        const hiddenInput = picker.find(
            'input[type="hidden"]'
        );

        const currentLabel = picker.find(
            '.ecm-font-picker-current'
        );

        const options = picker.find(
            '.ecm-font-picker-option'
        );

        hiddenInput.val(normalizedFamily);

        currentLabel
            .text(normalizedFamily)
            .css(
                'font-family',
                '"' + normalizedFamily + '", sans-serif'
            );

        options
            .removeClass('is-selected')
            .attr('aria-selected', 'false');

        const selectedOption = options.filter(function () {
            return String(
                $(this).data('font-family')
            ) === normalizedFamily;
        });

        selectedOption
            .addClass('is-selected')
            .attr('aria-selected', 'true');

        if (selectedOption.length) {
            loadFontPreview(selectedOption);
        }

        if (triggerChange) {
            hiddenInput.trigger('change');
        }
    };

    /**
 * Load a Google Font preview stylesheet only once.
 *
 * @param {jQuery} option
 */
    function loadFontPreview(option) {
        const source = String(
            option.data('font-source') || ''
        );

        const previewUrl = String(
            option.data('font-preview-url') || ''
        );

        const family = String(
            option.data('font-family') || ''
        );

        if (
            source !== 'google' ||
            !previewUrl ||
            !family ||
            loadedPreviewFonts.has(family)
        ) {
            return;
        }

        const link = document.createElement('link');

        link.rel = 'stylesheet';
        link.href = previewUrl;
        link.dataset.ecmFontFamily = family;

        document.head.appendChild(link);

        loadedPreviewFonts.add(family);
    }

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
 * Install an uninstalled Google Font locally.
 *
 * @param {jQuery} option
 * @returns {Promise}
 */
    function installGoogleFont(option) {
        const family = String(
            option.data('font-family') || ''
        );

        const source = String(
            option.data('font-source') || ''
        );

        const picker = option.closest('.ecm-font-picker');

        const installed =
            String(option.attr('data-font-installed') || '0') === '1';

        if (source !== 'google' || installed) {
            return Promise.resolve({
                installed: true
            });
        }

        if (fontInstallRequests.has(family)) {
            return fontInstallRequests.get(family);
        }

        option
            .addClass('is-installing')
            .prop('disabled', true);

        option
            .find('.ecm-font-option-source')
            .text('Installing…');

        const request = $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ecm_install_google_font',
                nonce: $('#ecm_install_google_font_nonce').val(),
                family: family
            }
        })
            .done(function (response) {
                if (!response || !response.success) {
                    const message =
                        response &&
                            response.data &&
                            response.data.message
                            ? response.data.message
                            : 'The font could not be installed.';

                    option
                        .removeClass('is-installing')
                        .prop('disabled', false);

                    option
                        .find('.ecm-font-option-source')
                        .text('Install failed');

                    window.alert(message);
                    return;
                }

                const cssUrl =
                    response.data && response.data.css_url
                        ? response.data.css_url
                        : '';

                if (cssUrl) {
                    const existingLink = document.querySelector(
                        'link[data-ecm-local-font="' + family + '"]'
                    );

                    if (!existingLink) {
                        const link = document.createElement('link');

                        link.rel = 'stylesheet';
                        link.href = cssUrl;
                        link.dataset.ecmLocalFont = family;

                        document.head.appendChild(link);
                    }
                }

                option
                    .attr('data-font-installed', '1')
                    .data('font-installed', 1)
                    .removeClass('is-installing')
                    .prop('disabled', false);

                option
                    .find('.ecm-font-option-source')
                    .text('Installed');

                Builder.setFontPickerValue(
                    picker.attr('id'),
                    family,
                    true
                );

                closePicker(picker);
            })
            .fail(function (xhr, status, error) {
                console.error('ECM font installation failed:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    responseJSON: xhr.responseJSON
                });

                let message = 'The font could not be installed.';

                if (
                    xhr.responseJSON &&
                    xhr.responseJSON.data &&
                    xhr.responseJSON.data.message
                ) {
                    message = xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    message += '\n\nServer response: ' + xhr.responseText;
                }

                option
                    .removeClass('is-installing')
                    .prop('disabled', false);

                option
                    .find('.ecm-font-option-source')
                    .text('Install failed');

                window.alert(message);
            })
            .always(function () {
                fontInstallRequests.delete(family);
            });

        fontInstallRequests.set(family, request);

        return request;
    }

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
            const fontFamily = String(
                option.data('font-family') || ''
            );

            if (!fontFamily) {
                return;
            }

            loadFontPreview(option);

            installGoogleFont(option)
                .then(function () {
                    Builder.setFontPickerValue(
                        picker.attr('id'),
                        fontFamily,
                        true
                    );

                    closePicker(picker);
                });
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
    /**
 * Load a Google Font when its option is hovered or focused.
 */
    $(document).on(
        'mouseenter focusin',
        '.ecm-font-picker-option[data-font-source="google"]',
        function () {
            loadFontPreview($(this));
        }
    );
})(jQuery, window);