(function ($) {
    'use strict';

    $(document).ready(function () {
        $('.ecm-open-participant-modal').on('click', function () {
            $('#ecm-add-participant-modal').fadeIn(150);
        });

        $('.ecm-modal-close, .ecm-modal-cancel').on('click', function () {
            $('#ecm-add-participant-modal').fadeOut(150);
        });

        $('#ecm-add-participant-modal').on('click', function (e) {
            if ($(e.target).is('#ecm-add-participant-modal')) {
                $('#ecm-add-participant-modal').fadeOut(150);
            }
        });
    });

})(jQuery);