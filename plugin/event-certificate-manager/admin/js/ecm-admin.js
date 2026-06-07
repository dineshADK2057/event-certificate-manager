(function ($) {
    'use strict';

    function openParticipantModal() {
        $('#ecm-add-participant-modal').fadeIn(150);
    }

    function closeParticipantModal() {
        $('#ecm-add-participant-modal').fadeOut(150);
    }

    function resetParticipantModal() {
        $('#ecm-participant-modal-title').text('Add Participant');
        $('#ecm_form_mode').val('add');
        $('#ecm_participant_id').val('');

        $('.ecm-participant-field').val('');

        $('#ecm_add_participant_submit').show();
        $('#ecm_update_participant_submit').hide();
    }

    $(document).ready(function () {
        $('.ecm-open-participant-modal').on('click', function () {
            resetParticipantModal();
            openParticipantModal();
        });

        $('.ecm-edit-participant').on('click', function (e) {
            e.preventDefault();

            let button = $(this);

            resetParticipantModal();

            $('#ecm-participant-modal-title').text('Edit Participant');
            $('#ecm_form_mode').val('edit');
            $('#ecm_participant_id').val(button.data('participant-id'));

            $('.ecm-participant-field').each(function () {
                let fieldKey = $(this).data('field-key');
                let value = button.data(fieldKey);

                if (typeof value !== 'undefined') {
                    $(this).val(value);
                }
            });

            $('#ecm_add_participant_submit').hide();
            $('#ecm_update_participant_submit').show();

            openParticipantModal();
        });

        $('.ecm-modal-close, .ecm-modal-cancel').on('click', function () {
            closeParticipantModal();
        });

        $('#ecm-add-participant-modal').on('click', function (e) {
            if ($(e.target).is('#ecm-add-participant-modal')) {
                closeParticipantModal();
            }
        });
        $('#ecm-select-all-participants').on('change', function () {
            $('.ecm-participant-checkbox').prop('checked', $(this).is(':checked'));
        });
    });

})(jQuery);