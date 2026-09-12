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
     */
    function scheduleSave() {
        if (typeof Builder.schedulePropertySave === 'function') {
            Builder.schedulePropertySave();
        }
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

    /**
     * Font family.
     */
    $('#ecm_properties_font_family').on(
        'input change',
        function () {
            const value = $(this).val();
            const element = Builder.getSelectedCanvasElement();

            if (!element.length) {
                return;
            }

            element.css('font-family', value);
            updateElementData(
                element,
                'font-family',
                value
            );

            scheduleSave();
        }
    );

    /**
     * Font size.
     */
    $('#ecm_properties_font_size').on(
        'input change',
        function () {
            const value = Math.max(
                1,
                parseFloat($(this).val()) || 1
            );

            const element = Builder.getSelectedCanvasElement();

            if (!element.length) {
                return;
            }

            element.css(
                'font-size',
                value + 'px'
            );

            updateElementData(
                element,
                'font-size',
                value
            );

            scheduleSave();
        }
    );

    /**
     * Font color.
     */
    $('#ecm_properties_font_color').on(
        'input change',
        function () {
            const value = $(this).val();
            const element = Builder.getSelectedCanvasElement();

            if (!element.length) {
                return;
            }

            element.css(
                'color',
                value
            );

            updateElementData(
                element,
                'font-color',
                value
            );

            scheduleSave();
        }
    );

    /**
     * Text alignment icon controls.
     */
    $(document).on(
        'click',
        '.ecm-alignment-button',
        function (event) {
            event.preventDefault();

            const button = $(this);

            const alignment = String(
                button.data('text-alignment') || 'left'
            ).toLowerCase();

            const allowedAlignments = [
                'left',
                'center',
                'right'
            ];

            if (!allowedAlignments.includes(alignment)) {
                return;
            }

            const element = Builder.getSelectedCanvasElement();

            if (!element.length) {
                return;
            }

            /*
             * Keep the hidden alignment field synchronized.
             */
            $('#ecm_properties_alignment')
                .val(alignment);

            /*
             * Update active icon state.
             */
            $('.ecm-alignment-button')
                .removeClass('is-active');

            button.addClass('is-active');

            /*
             * Update live Builder preview.
             */
            element.css(
                'text-align',
                alignment
            );

            updateElementData(
                element,
                'alignment',
                alignment
            );

            scheduleSave();
        }
    );

    /**
     * Horizontal position.
     */
    $('#ecm_properties_x_position').on(
        'input change',
        function () {
            const value =
                parseFloat($(this).val()) || 0;

            const element = Builder.getSelectedCanvasElement();

            if (!element.length) {
                return;
            }

            element.css(
                'left',
                value + 'px'
            );

            updateElementData(
                element,
                'x-position',
                value
            );

            scheduleSave();
        }
    );

    /**
     * Vertical position.
     */
    $('#ecm_properties_y_position').on(
        'input change',
        function () {
            const value =
                parseFloat($(this).val()) || 0;

            const element = Builder.getSelectedCanvasElement();

            if (!element.length) {
                return;
            }

            element.css(
                'top',
                value + 'px'
            );

            updateElementData(
                element,
                'y-position',
                value
            );

            scheduleSave();
        }
    );

    /**
    * Align the selected element against the certificate canvas.
    */
    $(document).on(
        'click',
        '.ecm-inline-alignment-button',
        function (event) {
            event.preventDefault();

            const button = $(this);

            const alignment = String(
                button.data('element-align') || ''
            ).toLowerCase();

            const element =
                Builder.getSelectedCanvasElement();

            if (!element.length) {
                return;
            }

            const canvas =
                $('.ecm-builder-canvas').first();

            if (!canvas.length) {
                return;
            }

            const canvasWidth =
                canvas.innerWidth();

            const canvasHeight =
                canvas.innerHeight();

            const elementWidth =
                element.outerWidth();

            const elementHeight =
                element.outerHeight();

            let xPosition =
                parseFloat(
                    $('#ecm_properties_x_position').val()
                ) || 0;

            let yPosition =
                parseFloat(
                    $('#ecm_properties_y_position').val()
                ) || 0;

            switch (alignment) {

                case 'left':
                    xPosition = 0;
                    break;

                case 'center':
                    xPosition =
                        (canvasWidth - elementWidth) / 2;
                    break;

                case 'right':
                    xPosition =
                        canvasWidth - elementWidth;
                    break;

                case 'top':
                    yPosition = 0;
                    break;

                case 'middle':
                    yPosition =
                        (canvasHeight - elementHeight) / 2;
                    break;

                case 'bottom':
                    yPosition =
                        canvasHeight - elementHeight;
                    break;

                default:
                    return;
            }

            /*
             * Keep values clean for display and persistence.
             */
            xPosition =
                Math.round(xPosition * 100) / 100;

            yPosition =
                Math.round(yPosition * 100) / 100;

            /*
             * Horizontal alignment updates X only.
             */
            if (
                alignment === 'left' ||
                alignment === 'center' ||
                alignment === 'right'
            ) {
                $('#ecm_properties_x_position')
                    .val(xPosition)
                    .trigger('input');
            }

            /*
             * Vertical alignment updates Y only.
             */
            if (
                alignment === 'top' ||
                alignment === 'middle' ||
                alignment === 'bottom'
            ) {
                $('#ecm_properties_y_position')
                    .val(yPosition)
                    .trigger('input');
            }
        }
    );

    /**
     * Rotation.
     */
    $('#ecm_properties_rotation').on(
        'input change',
        function () {
            const value =
                parseFloat($(this).val()) || 0;

            const element = Builder.getSelectedCanvasElement();

            if (!element.length) {
                return;
            }

            element.css(
                'transform',
                'rotate(' + value + 'deg)'
            );

            updateElementData(
                element,
                'rotation',
                value
            );

            scheduleSave();
        }
    );

    /**
     * Increment or decrement a numeric property field.
     */
    $(document).on(
        'click',
        '.ecm-stepper-button',
        function (event) {
            event.preventDefault();

            const button = $(this);

            const targetId =
                button.data('stepper-target');

            const direction =
                parseFloat(
                    button.data('stepper-direction')
                ) || 0;

            const input =
                $('#' + targetId);

            if (!input.length || !direction) {
                return;
            }

            const step =
                parseFloat(
                    input.attr('step')
                ) || 1;

            let value =
                parseFloat(
                    input.val()
                ) || 0;

            value += step * direction;

            const minimum =
                parseFloat(
                    input.attr('min')
                );

            const maximum =
                parseFloat(
                    input.attr('max')
                );

            if (!Number.isNaN(minimum)) {
                value = Math.max(
                    minimum,
                    value
                );
            }

            if (!Number.isNaN(maximum)) {
                value = Math.min(
                    maximum,
                    value
                );
            }

            value =
                Math.round(value * 100) / 100;

            input
                .val(value)
                .trigger('input');
        }
    );

    /**
     * Apply a preset font color.
     */
    $(document).on(
        'click',
        '.ecm-color-preset',
        function (event) {
            event.preventDefault();

            const color = String(
                $(this).data('color') ||
                '#000000'
            );

            $('#ecm_properties_font_color')
                .val(color)
                .trigger('input');

            $('.ecm-color-preset')
                .removeClass('is-selected');

            $(this)
                .addClass('is-selected');
        }
    );

    /**
     * Keep preset selection synchronized with custom colors.
     */
    $('#ecm_properties_font_color').on(
        'input change',
        function () {
            const selectedColor = String(
                $(this).val() || ''
            ).toUpperCase();

            $('.ecm-color-preset')
                .each(function () {
                    const preset = $(this);

                    const presetColor = String(
                        preset.data('color') || ''
                    ).toUpperCase();

                    preset.toggleClass(
                        'is-selected',
                        presetColor === selectedColor
                    );
                });
        }
    );

})(jQuery, window);