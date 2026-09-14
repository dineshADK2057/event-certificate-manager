/**
 * ECM Template Builder Autosave
 *
 * Handles automatic AJAX saving of Builder element properties.
 */
(function ($, window) {
    'use strict';

    const Builder = window.ECMBuilder || {};

    if (!Builder.isActive || !Builder.isActive()) {
        return;
    }

    let saveTimer = null;
    let saveRequest = null;
    let statusHideTimer = null;

    /**
     * Update the visual autosave status.
     *
     * @param {string} status
     * @param {string} message
     */
    /**
 * Update the visible autosave status.
 *
 * Saved messages disappear automatically after a short delay.
 *
 * @param {string} status
 * @param {string} message
 */
    Builder.setSaveStatus = function (status, message) {
        const container = $('.ecm-auto-save-status');
        const text = $('#ecm-element-save-status');

        clearTimeout(statusHideTimer);

        container
            .stop(true, true)
            .removeClass('is-saving is-saved is-error is-hidden')
            .show();

        if (status) {
            container.addClass(status);
        }

        text.text(message);

        if (status === 'is-saved') {
            statusHideTimer = setTimeout(function () {
                container.fadeOut(250, function () {
                    container.addClass('is-hidden');
                });
            }, 2200);
        }
    };

    /**
     * Collect the currently selected element properties.
     *
     * @returns {Object}
     */
    Builder.collectProperties = function () {
        return {
            action: 'ecm_update_template_element_properties',
            nonce: $('#ecm_element_properties_nonce').val(),
            event_id: $('#ecm_builder_event_id').val(),
            template_id: $('#ecm_builder_template_id').val(),
            element_id: Builder.getSelectedElementId(),

            font_family: $('#ecm_properties_font_family').val(),
            font_size: $('#ecm_properties_font_size').val(),
            font_color: $('#ecm_properties_font_color').val(),
            alignment: $('#ecm_properties_alignment').val(),

            x_position: $('#ecm_properties_x_position').val(),
            y_position: $('#ecm_properties_y_position').val(),
            rotation: $('#ecm_properties_rotation').val(),

            width: $('#ecm_properties_width').val(),
            height: $('#ecm_properties_height').val(),

            qr_foreground_color:
                $('#ecm_properties_qr_foreground_color').val(),

            qr_background_color:
                $('#ecm_properties_qr_background_color').val(),

            qr_border_color:
                $('#ecm_properties_qr_border_color').val(),

            qr_border_width:
                $('#ecm_properties_qr_border_width').val(),

            qr_border_radius:
                $('#ecm_properties_qr_border_radius').val()
        };
    };

    /**
     * Keep canvas, sidebar, and edit-modal data synchronized.
     *
     * @param {Object} properties
     */
    Builder.updateSelectedElementData = function (properties) {
        const elementId = properties.element_id;
        const canvasElement = Builder.getCanvasElement(elementId);

        canvasElement.attr('data-font-family', properties.font_family);
        canvasElement.attr('data-font-size', properties.font_size);
        canvasElement.attr('data-font-color', properties.font_color);
        canvasElement.attr('data-alignment', properties.alignment);
        canvasElement.attr('data-x-position', properties.x_position);
        canvasElement.attr('data-y-position', properties.y_position);
        canvasElement.attr('data-rotation', properties.rotation);

        canvasElement.attr(
            'data-width',
            properties.width
        );

        canvasElement.attr(
            'data-height',
            properties.height
        );

        canvasElement.attr(
            'data-qr-foreground-color',
            properties.qr_foreground_color
        );

        canvasElement.attr(
            'data-qr-background-color',
            properties.qr_background_color
        );

        canvasElement.attr(
            'data-qr-border-color',
            properties.qr_border_color
        );

        canvasElement.attr(
            'data-qr-border-width',
            properties.qr_border_width
        );

        canvasElement.attr(
            'data-qr-border-radius',
            properties.qr_border_radius
        );

        canvasElement.data('font-family', properties.font_family);
        canvasElement.data('font-size', properties.font_size);
        canvasElement.data('font-color', properties.font_color);
        canvasElement.data('alignment', properties.alignment);
        canvasElement.data('x-position', properties.x_position);
        canvasElement.data('y-position', properties.y_position);
        canvasElement.data('rotation', properties.rotation);

        canvasElement.data(
            'width',
            properties.width
        );

        canvasElement.data(
            'height',
            properties.height
        );

        canvasElement.data(
            'qr-foreground-color',
            properties.qr_foreground_color
        );

        canvasElement.data(
            'qr-background-color',
            properties.qr_background_color
        );

        canvasElement.data(
            'qr-border-color',
            properties.qr_border_color
        );

        canvasElement.data(
            'qr-border-width',
            properties.qr_border_width
        );

        canvasElement.data(
            'qr-border-radius',
            properties.qr_border_radius
        );

        const listItem = Builder.getListItem(elementId);

        listItem.find('.description').first().text(
            'X: ' + properties.x_position +
            ', Y: ' + properties.y_position +
            ', Size: ' + properties.font_size
        );

    };

    /**
     * Save the selected element properties through AJAX.
     */
    Builder.saveSelectedElementProperties = function () {
        const properties = Builder.collectProperties();

        if (!properties.element_id) {
            return;
        }

        Builder.setSaveStatus('is-saving', 'Saving changes…');

        if (saveRequest && saveRequest.readyState !== 4) {
            saveRequest.abort();
        }

        saveRequest = $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: properties,

            success: function (response) {
                if (!response.success) {
                    const message =
                        response.data && response.data.message
                            ? response.data.message
                            : 'Unable to save changes.';

                    Builder.setSaveStatus('is-error', message);
                    return;
                }

                Builder.updateSelectedElementData(properties);
                Builder.setSaveStatus('is-saved', 'All changes saved');
            },

            error: function (xhr, status) {
                if (status === 'abort') {
                    return;
                }

                let message = 'Unable to save changes.';

                if (
                    xhr.responseJSON &&
                    xhr.responseJSON.data &&
                    xhr.responseJSON.data.message
                ) {
                    message = xhr.responseJSON.data.message;
                }

                Builder.setSaveStatus('is-error', message);
            }
        });
    };

    /**
     * Debounce property saves to avoid one request per keystroke.
     */
    Builder.schedulePropertySave = function () {
        clearTimeout(saveTimer);

        Builder.setSaveStatus('is-saving', 'Waiting to save…');

        saveTimer = setTimeout(function () {
            Builder.saveSelectedElementProperties();
        }, 450);
    };
})(jQuery, window);