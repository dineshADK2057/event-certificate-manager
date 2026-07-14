/**
 * ECM Template Builder Selection
 *
 * Handles selecting elements from the canvas or Elements sidebar.
 */
(function ($, window) {
    'use strict';

    const Builder = window.ECMBuilder || {};

    if (!Builder.isActive || !Builder.isActive()) {
        return;
    }

    /**
     * Select a Builder element and populate the properties panel.
     *
     * @param {string|number} elementId
     */
    Builder.selectElement = function (elementId) {
        const canvasElement = Builder.getCanvasElement(elementId);

        if (!canvasElement.length) {
            return;
        }

        const placeholder = canvasElement.data('placeholder-key');

        /*
         * Store the selected element in shared Builder state.
         */
        Builder.state.selectedElementId = String(elementId);


        /*
         * Remove the previous selection.
         */
        $('.ecm-selectable-builder-element')
            .removeClass('ecm-element-selected');

        $('.ecm-element-list-item')
            .removeClass('ecm-element-list-item-selected');

        /*
         * Highlight the selected canvas element and matching list item.
         */
        canvasElement.addClass('ecm-element-selected');

        Builder.getListItem(elementId)
            .addClass('ecm-element-list-item-selected');

        /*
         * Populate the properties panel.
         */
        $('#ecm_properties_element_id').val(elementId);

        $('#ecm_properties_placeholder').val(
            '{' + placeholder + '}'
        );

        const selectedFontFamily =
            canvasElement.data('font-family') || 'Arial';

        $('#ecm_properties_font_family').val(
            selectedFontFamily
        );

        if (typeof Builder.setFontPickerValue === 'function') {
            Builder.setFontPickerValue(
                'ecm-properties-font-picker',
                selectedFontFamily,
                false
            );
        }

        $('#ecm_properties_font_size').val(
            canvasElement.data('font-size')
        );

        $('#ecm_properties_font_color').val(
            canvasElement.data('font-color')
        );

        $('#ecm_properties_alignment').val(
            canvasElement.data('alignment')
        );

        $('#ecm_properties_x_position').val(
            canvasElement.data('x-position')
        );

        $('#ecm_properties_y_position').val(
            canvasElement.data('y-position')
        );

        $('#ecm_properties_rotation').val(
            canvasElement.data('rotation')
        );

        $('#ecm-properties-element-title').text(
            '{' + placeholder + '}'
        );

        /*
         * Show the properties view.
         */
        $('#ecm-elements-list-view').hide();
        $('#ecm-element-properties-view').show();



        /*
         * Reset the autosave status for the newly selected element.
         */
        $('.ecm-auto-save-status')
            .removeClass('is-hidden')
            .show();

        if (typeof Builder.setSaveStatus === 'function') {
            Builder.setSaveStatus(
                '',
                'Changes save automatically'
            );
        }
    };

    /**
     * Clear the active element selection.
     */
    Builder.clearSelection = function () {
        Builder.state.selectedElementId = null;

        $('.ecm-selectable-builder-element')
            .removeClass('ecm-element-selected');

        $('.ecm-element-list-item')
            .removeClass('ecm-element-list-item-selected');

        $('#ecm-element-properties-view').hide();
        $('#ecm-elements-list-view').show();

        $('#ecm_properties_element_id').val('');


    };

    /**
     * Select an element by clicking it on the canvas.
     */
    $(document).on(
        'click',
        '.ecm-selectable-builder-element',
        function (event) {
            event.preventDefault();
            event.stopPropagation();

            /*
             * Ignore the click that may fire immediately after dragging.
             */
            if ($(this).hasClass('ecm-element-dragging')) {
                return;
            }

            Builder.selectElement(
                $(this).data('element-id')
            );
        }
    );

    /**
     * Select an element from the Elements sidebar.
     */
    $(document).on(
        'click',
        '.ecm-select-element-from-list',
        function (event) {
            event.preventDefault();

            Builder.selectElement(
                $(this).data('element-id')
            );
        }
    );

    /**
     * Select an element through keyboard interaction.
     */
    $(document).on(
        'keydown',
        '.ecm-selectable-builder-element',
        function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            Builder.selectElement(
                $(this).data('element-id')
            );
        }
    );

    /**
     * Return from the Properties panel to the Elements list.
     */
    $(document).on(
        'click',
        '.ecm-back-to-elements',
        function (event) {
            event.preventDefault();

            Builder.clearSelection();
        }
    );
})(jQuery, window);