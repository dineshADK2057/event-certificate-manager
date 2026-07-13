/**
 * ECM Template Builder Zoom Engine
 *
 * Controls visual workspace zoom without changing the certificate's
 * stored element coordinates or original page dimensions.
 */
(function ($, window) {
    'use strict';

    const Builder = window.ECMBuilder || {};

    if (!Builder.isActive || !Builder.isActive()) {
        return;
    }

    const MIN_ZOOM = 0.25;
    const MAX_ZOOM = 2;
    const ZOOM_STEP = 0.1;
    const FIT_PADDING = 24;

    let resizeTimer = null;

    /**
     * Keep zoom within the supported range.
     *
     * @param {number} zoom
     * @returns {number}
     */
    function clampZoom(zoom) {
        return Math.min(
            MAX_ZOOM,
            Math.max(MIN_ZOOM, zoom)
        );
    }

    /**
     * Round zoom to avoid long floating-point values.
     *
     * @param {number} zoom
     * @returns {number}
     */
    function normalizeZoom(zoom) {
        return Math.round(zoom * 100) / 100;
    }

    /**
     * Return the Builder workspace.
     *
     * @returns {jQuery}
     */
    function getWorkspace() {
        return $('.ecm-builder-workspace').first();
    }

    /**
     * Return the zoom wrapper.
     *
     * @returns {jQuery}
     */
    function getZoomWrapper() {
        return $('.ecm-builder-zoom-wrapper').first();
    }

    /**
     * Return the certificate canvas.
     *
     * @returns {jQuery}
     */
    function getCanvas() {
        return $('.ecm-builder-canvas').first();
    }

    /**
     * Read available workspace dimensions after removing padding.
     *
     * @returns {{width: number, height: number}}
     */
    function getWorkspaceAvailableSize() {
        const workspace = getWorkspace();

        if (!workspace.length) {
            return {
                width: 0,
                height: 0
            };
        }

        const node = workspace[0];
        const styles = window.getComputedStyle(node);

        const horizontalPadding =
            parseFloat(styles.paddingLeft || 0) +
            parseFloat(styles.paddingRight || 0);

        const verticalPadding =
            parseFloat(styles.paddingTop || 0) +
            parseFloat(styles.paddingBottom || 0);

        return {
            width: Math.max(
                0,
                node.clientWidth -
                horizontalPadding -
                FIT_PADDING
            ),

            height: Math.max(
                0,
                node.clientHeight -
                verticalPadding -
                FIT_PADDING
            )
        };
    }

    /**
     * Update the toolbar and zoom control state.
     *
     * @param {number} zoom
     */
    function updateControls(zoom) {
        const percentage = Math.round(zoom * 100);

        $('#ecm-toolbar-zoom-value').text(
            percentage + '%'
        );

        $('#ecm-toolbar-zoom-out').prop(
            'disabled',
            zoom <= MIN_ZOOM
        );

        $('#ecm-toolbar-zoom-in').prop(
            'disabled',
            zoom >= MAX_ZOOM
        );
    }

    /**
     * Apply visual zoom to the certificate canvas.
     *
     * The wrapper receives the scaled dimensions so scrolling and
     * centering continue to work correctly.
     *
     * @param {number} requestedZoom
     * @param {string} mode
     */
    Builder.setZoom = function (
        requestedZoom,
        mode = 'manual'
    ) {
        const canvas = getCanvas();
        const wrapper = getZoomWrapper();

        if (!canvas.length || !wrapper.length) {
            return;
        }

        const zoom = normalizeZoom(
            clampZoom(
                parseFloat(requestedZoom) || 1
            )
        );

        /*
         * offsetWidth/offsetHeight remain the original unscaled
         * certificate dimensions.
         */
        const canvasWidth = canvas[0].offsetWidth;
        const canvasHeight = canvas[0].offsetHeight;

        Builder.state.zoom = zoom;
        Builder.state.zoomMode = mode;

        canvas.css({
            transform: 'scale(' + zoom + ')',
            transformOrigin: 'top left'
        });

        wrapper.css({
            width: Math.ceil(canvasWidth * zoom) + 'px',
            height: Math.ceil(canvasHeight * zoom) + 'px'
        });

        updateControls(zoom);

        $(document).trigger(
            'ecm:builder:zoom-changed',
            [{
                zoom: zoom,
                mode: mode
            }]
        );
    };

    /**
     * Increase workspace zoom.
     */
    Builder.zoomIn = function () {
        Builder.setZoom(
            (Builder.state.zoom || 1) + ZOOM_STEP,
            'manual'
        );
    };

    /**
     * Decrease workspace zoom.
     */
    Builder.zoomOut = function () {
        Builder.setZoom(
            (Builder.state.zoom || 1) - ZOOM_STEP,
            'manual'
        );
    };

    /**
     * Reset workspace zoom to 100%.
     */
    Builder.resetZoom = function () {
        Builder.setZoom(1, 'manual');
    };

    /**
     * Fit the certificate to the available workspace width.
     */
    Builder.fitWidth = function () {
        const canvas = getCanvas();
        const available = getWorkspaceAvailableSize();

        if (!canvas.length || !available.width) {
            return;
        }

        const zoom =
            available.width / canvas[0].offsetWidth;

        Builder.setZoom(zoom, 'fit-width');
    };

    /**
     * Fit the complete certificate inside the workspace.
     */
    Builder.fitPage = function () {
        const canvas = getCanvas();
        const available = getWorkspaceAvailableSize();

        if (
            !canvas.length ||
            !available.width ||
            !available.height
        ) {
            return;
        }

        const widthZoom =
            available.width / canvas[0].offsetWidth;

        const heightZoom =
            available.height / canvas[0].offsetHeight;

        Builder.setZoom(
            Math.min(widthZoom, heightZoom),
            'fit-page'
        );
    };

    /**
     * Recalculate responsive fit modes after resizing.
     */
    function refreshResponsiveZoom() {
        if (Builder.state.zoomMode === 'fit-width') {
            Builder.fitWidth();
            return;
        }

        if (Builder.state.zoomMode === 'fit-page') {
            Builder.fitPage();
        }
    }

    $(document).on(
        'click',
        '#ecm-toolbar-zoom-in',
        function () {
            Builder.zoomIn();
        }
    );

    $(document).on(
        'click',
        '#ecm-toolbar-zoom-out',
        function () {
            Builder.zoomOut();
        }
    );

    $(document).on(
        'click',
        '#ecm-toolbar-zoom-value',
        function () {
            Builder.resetZoom();
        }
    );

    $(document).on(
        'click',
        '#ecm-toolbar-fit-width',
        function () {
            Builder.fitWidth();
        }
    );

    $(document).on(
        'click',
        '#ecm-toolbar-fit-page',
        function () {
            Builder.fitPage();
        }
    );

    $(window).on('resize', function () {
        clearTimeout(resizeTimer);

        resizeTimer = setTimeout(function () {
            refreshResponsiveZoom();
        }, 150);
    });

    /*
     * Initialize at the zoom stored in shared Builder state.
     */
    Builder.setZoom(
        Builder.state.zoom || 1,
        'manual'
    );
})(jQuery, window);