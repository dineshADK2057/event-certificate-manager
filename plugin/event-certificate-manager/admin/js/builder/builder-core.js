/**
 * ECM Template Builder Core
 *
 * Provides the global namespace and shared Builder utilities.
 */
(function ($, window) {
    'use strict';

    window.ECMBuilder = window.ECMBuilder || {};

    const Builder = window.ECMBuilder;

    /**
     * Check whether the current page contains the Template Builder.
     *
     * @returns {boolean}
     */
    Builder.isActive = function () {
        return $('.ecm-builder-layout').length > 0;
    };

    /**
     * Find a canvas element by its database element ID.
     *
     * @param {string|number} elementId
     * @returns {jQuery}
     */
    Builder.getCanvasElement = function (elementId) {
        return $(
            '.ecm-selectable-builder-element[data-element-id="' +
            elementId +
            '"]'
        );
    };

    /**
     * Find the matching sidebar list item.
     *
     * @param {string|number} elementId
     * @returns {jQuery}
     */
    Builder.getListItem = function (elementId) {
        return $(
            '.ecm-element-list-item[data-element-id="' +
            elementId +
            '"]'
        );
    };

    /**
     * Return the currently selected element ID.
     *
     * @returns {string}
     */
    Builder.getSelectedElementId = function () {
        return String($('#ecm_properties_element_id').val() || '');
    };

    /**
 * Update an element's stored position values in the DOM.
 *
 * This keeps HTML data attributes and jQuery's cached data
 * synchronized with the current canvas position.
 *
 * @param {jQuery} element
 * @param {number} x
 * @param {number} y
 */
    Builder.setElementPositionData = function (element, x, y) {
        element.attr('data-x-position', x);
        element.attr('data-y-position', y);

        element.data('x-position', x);
        element.data('y-position', y);
    };
    
})(jQuery, window);