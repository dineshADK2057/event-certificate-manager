/**
 * ECM Template Builder Toolbar
 *
 * Manages global Builder toolbar controls and state indicators.
 */
(function ($, window) {
    'use strict';

    const Builder = window.ECMBuilder || {};

    if (!Builder.isActive || !Builder.isActive()) {
        return;
    }

    /**
     * Update the displayed workspace zoom value.
     *
     * @param {number} zoom
     */
    Builder.updateToolbarZoom = function (zoom) {
        const percentage = Math.round(zoom * 100);

        $('#ecm-toolbar-zoom-value').text(
            percentage + '%'
        );
    };

    /**
     * Update active/inactive toolbar toggle styling.
     *
     * @param {string} control
     * @param {boolean} active
     */
    Builder.updateToolbarToggle = function (control, active) {
        $('#ecm-toolbar-' + control).toggleClass(
            'is-active',
            Boolean(active)
        );
    };

    Builder.updateToolbarZoom(
        Builder.state.zoom || 1
    );
})(jQuery, window);