/**
 * ECM Template Builder Interaction Engine
 *
 * Handles pointer-based movement of Builder elements.
 * Future snapping, guides, resizing, and rotation interactions
 * will extend this module.
 */
(function ($, window) {
    'use strict';

    const Builder = window.ECMBuilder || {};

    if (!Builder.isActive || !Builder.isActive()) {
        return;
    }

    let interaction = null;
    let animationFrame = null;

    /**
     * Keep a value between a minimum and maximum.
     *
     * @param {number} value
     * @param {number} minimum
     * @param {number} maximum
     * @returns {number}
     */
    function clamp(value, minimum, maximum) {
        return Math.min(Math.max(value, minimum), maximum);
    }

    /**
     * Round coordinates to two decimal places.
     *
     * @param {number} value
     * @returns {number}
     */
    function roundCoordinate(value) {
        return Math.round(value * 100) / 100;
    }

    /**
     * Apply the current pointer position to the active element.
     *
     * @param {PointerEvent} event
     */
    function moveInteraction(event) {
        if (!interaction || event.pointerId !== interaction.pointerId) {
            return;
        }

        interaction.latestClientX = event.clientX;
        interaction.latestClientY = event.clientY;

        if (animationFrame) {
            return;
        }

        animationFrame = window.requestAnimationFrame(function () {
            animationFrame = null;

            if (!interaction) {
                return;
            }

            const zoom = Builder.state.zoom || 1;

            const deltaX =
                (
                    interaction.latestClientX -
                    interaction.startClientX
                ) / zoom;

            const deltaY =
                (
                    interaction.latestClientY -
                    interaction.startClientY
                ) / zoom;

            let nextX = interaction.startX + deltaX;
            let nextY = interaction.startY + deltaY;

            const canvasWidth = interaction.canvas.innerWidth();
            const canvasHeight = interaction.canvas.innerHeight();

            const elementWidth = interaction.element.outerWidth();
            const elementHeight = interaction.element.outerHeight();

            nextX = clamp(
                nextX,
                0,
                Math.max(0, canvasWidth - elementWidth)
            );

            nextY = clamp(
                nextY,
                0,
                Math.max(0, canvasHeight - elementHeight)
            );

            nextX = roundCoordinate(nextX);
            nextY = roundCoordinate(nextY);

            interaction.currentX = nextX;
            interaction.currentY = nextY;

            interaction.element.css({
                left: nextX + 'px',
                top: nextY + 'px'
            });

            $('#ecm_properties_x_position').val(nextX);
            $('#ecm_properties_y_position').val(nextY);

            Builder.setElementPositionData(
                interaction.element,
                nextX,
                nextY
            );

            interaction.hasMoved =
                Math.abs(deltaX) > 2 || Math.abs(deltaY) > 2;
        });
    }

    /**
     * Finish the current drag operation.
     *
     * @param {PointerEvent} event
     */
    function endInteraction(event) {
        if (!interaction || event.pointerId !== interaction.pointerId) {
            return;
        }

        const completedInteraction = interaction;
        interaction = null;

        if (animationFrame) {
            window.cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }

        completedInteraction.element.removeClass('ecm-element-dragging');

        try {
            completedInteraction.element[0].releasePointerCapture(
                event.pointerId
            );
        } catch (error) {
            // Pointer capture may already have been released.
        }

        if (!completedInteraction.hasMoved) {
            return;
        }

        Builder.setElementPositionData(
            completedInteraction.element,
            completedInteraction.currentX,
            completedInteraction.currentY
        );

        /*
         * Persist the new X/Y position using the existing
         * Builder autosave engine.
         */
        if (typeof Builder.schedulePropertySave === 'function') {
            Builder.schedulePropertySave();
        }
    }

    /**
     * Begin dragging a Builder element.
     */
    $(document).on(
        'pointerdown',
        '.ecm-builder-element-frame',
        function (event) {
            /*
             * Ignore non-primary mouse buttons.
             */
            if (event.pointerType === 'mouse' && event.button !== 0) {
                return;
            }

            /*
             * Resize handles will receive their own interaction
             * behavior in a later sprint.
             */
            if ($(event.target).closest('.ecm-element-handle').length) {
                return;
            }

            const element = $(this);
            const canvas = element.closest('.ecm-builder-canvas');
            const elementId = element.data('element-id');

            if (!canvas.length || !elementId) {
                return;
            }

            event.preventDefault();

            Builder.selectElement(elementId);

            const startX =
                parseFloat(element.attr('data-x-position')) ||
                parseFloat(element.css('left')) ||
                0;

            const startY =
                parseFloat(element.attr('data-y-position')) ||
                parseFloat(element.css('top')) ||
                0;

            interaction = {
                pointerId: event.pointerId,
                element: element,
                canvas: canvas,

                startClientX: event.clientX,
                startClientY: event.clientY,

                latestClientX: event.clientX,
                latestClientY: event.clientY,

                startX: startX,
                startY: startY,

                currentX: startX,
                currentY: startY,

                hasMoved: false
            };

            element.addClass('ecm-element-dragging');

            element[0].setPointerCapture(event.pointerId);
        }
    );

    $(document).on(
        'pointermove',
        '.ecm-builder-element-frame',
        moveInteraction
    );

    $(document).on(
        'pointerup pointercancel',
        '.ecm-builder-element-frame',
        endInteraction
    );

    


})(jQuery, window);