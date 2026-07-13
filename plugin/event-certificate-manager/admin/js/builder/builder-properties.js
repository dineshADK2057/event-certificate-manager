/**
 * ECM Template Builder Properties
 *
 * Handles live visual updates when users change element properties.
 * Database persistence is handled separately by the autosave module.
 */
(function ($, window) {
    'use strict';

    const Builder = window.ECMBuilder || {};

    if (!Builder.isActive || !Builder.isActive()) {
        return;
    }

    /**
     * Return the currently selected canvas element.
     *
     * @returns {jQuery}
     */
    Builder.getSelectedCanvasElement = function () {
        const elementId = Builder.getSelectedElementId();

        if (!elementId) {
            return $();
        }

        return Builder.getCanvasElement(elementId);
    };

    /**
     * Ask the autosave module to persist the current values.
     *
     * The guard allows this module to work while autosave code is
     * still temporarily located in the main admin script.
     */
    function scheduleSave() {
        Builder.schedulePropertySave();
    }

    /**
     * Update an element's cached data attribute and jQuery data value.
     *
     * @param {jQuery} element
     * @param {string} key
     * @param {string|number} value
     */
    function updateElementData(element, key, value) {
        element.attr('data-' + key, value);
        element.data(key, value);
    }

    $('#ecm_properties_font_family').on('input change', function () {
        const value = $(this).val();
        const element = Builder.getSelectedCanvasElement();

        element.css('font-family', value);
        updateElementData(element, 'font-family', value);

        scheduleSave();
    });

    $('#ecm_properties_font_size').on('input change', function () {
        const value = Math.max(1, parseFloat($(this).val()) || 1);
        const element = Builder.getSelectedCanvasElement();

        element.css('font-size', value + 'px');
        updateElementData(element, 'font-size', value);

        scheduleSave();
    });

    $('#ecm_properties_font_color').on('input change', function () {
        const value = $(this).val();
        const element = Builder.getSelectedCanvasElement();

        element.css('color', value);
        updateElementData(element, 'font-color', value);

        scheduleSave();
    });

    $('#ecm_properties_alignment').on('change', function () {
        const value = $(this).val();
        const element = Builder.getSelectedCanvasElement();

        element.css('text-align', value);
        updateElementData(element, 'alignment', value);

        scheduleSave();
    });

    $('#ecm_properties_x_position').on('input change', function () {
        const value = parseFloat($(this).val()) || 0;
        const element = Builder.getSelectedCanvasElement();

        element.css('left', value + 'px');
        updateElementData(element, 'x-position', value);

        scheduleSave();
    });

    $('#ecm_properties_y_position').on('input change', function () {
        const value = parseFloat($(this).val()) || 0;
        const element = Builder.getSelectedCanvasElement();

        element.css('top', value + 'px');
        updateElementData(element, 'y-position', value);

        scheduleSave();
    });

    $('#ecm_properties_rotation').on('input change', function () {
        const value = parseFloat($(this).val()) || 0;
        const element = Builder.getSelectedCanvasElement();

        element.css('transform', 'rotate(' + value + 'deg)');
        updateElementData(element, 'rotation', value);

        scheduleSave();
    });
})(jQuery, window);