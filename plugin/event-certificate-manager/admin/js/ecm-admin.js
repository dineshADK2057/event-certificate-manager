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
            $('.ecm-modal').fadeOut(150);
        });

        $('.ecm-modal').on('click', function (e) {
            if ($(e.target).hasClass('ecm-modal')) {
                $('.ecm-modal').fadeOut(150);
            }
        });

        $('#ecm-add-participant-modal').on('click', function (e) {
            if ($(e.target).is('#ecm-add-participant-modal')) {
                closeParticipantModal();
            }
        });
        $('#ecm-select-all-participants').on('change', function () {
            $('.ecm-participant-checkbox').prop('checked', $(this).is(':checked'));
        });
        $('.ecm-open-csv-modal').on('click', function () {
            $('#ecm-csv-upload-modal').fadeIn(150);
        });
        $('.ecm-open-session-modal').on('click', function () {
            $('#ecm-add-session-modal').fadeIn(150);
        });
        $('.ecm-edit-session').on('click', function (e) {
            e.preventDefault();

            let button = $(this);

            $('#ecm-session-modal-title').text('Edit Session');
            $('#ecm_session_id').val(button.data('session-id'));
            $('#session_name').val(button.data('session-name'));
            $('#tutor_name').val(button.data('tutor-name'));
            $('#session_date').val(button.data('session-date'));
            $('#session_status').val(button.data('status'));

            $('#ecm_add_session_submit').hide();
            $('#ecm_update_session_submit').show();

            $('#ecm-add-session-modal').fadeIn(150);
        });

        $('.ecm-open-session-modal').on('click', function () {
            $('#ecm-session-modal-title').text('Add Session');
            $('#ecm_session_id').val('');
            $('#session_name').val('');
            $('#tutor_name').val('');
            $('#session_date').val('');
            $('#session_status').val('active');

            $('#ecm_add_session_submit').show();
            $('#ecm_update_session_submit').hide();

            $('#ecm-add-session-modal').fadeIn(150);
        });
        $('#ecm-select-all-session-available').on('change', function () {
            $('.ecm-session-available-checkbox').prop('checked', $(this).is(':checked'));
        });

        $('.ecm-open-template-modal').on('click', function () {
            $('#ecm-template-modal-title').text('Create Template');
            $('#ecm_template_id').val('');
            $('#ecm_template_name').val('');
            $('#ecm_certificate_type').val('participant');
            $('#ecm_template_session_id').val('0');
            $('#ecm_template_orientation').val('landscape');
            $('#ecm_template_page_size').val('A4');

            $('#ecm_add_template_submit').show();
            $('#ecm_update_template_submit').hide();

            $('#ecm-add-template-modal').fadeIn(150);
        });

        $('.ecm-edit-template').on('click', function (e) {
            e.preventDefault();

            let button = $(this);

            $('#ecm-template-modal-title').text('Edit Template');
            $('#ecm_template_id').val(button.data('template-id'));
            $('#ecm_template_name').val(button.data('template-name'));
            $('#ecm_certificate_type').val(button.data('certificate-type'));
            $('#ecm_template_session_id').val(String(button.data('session-id')));
            $('#ecm_template_orientation').val(button.data('orientation'));
            $('#ecm_template_page_size').val(button.data('page-size'));

            $('#ecm_add_template_submit').hide();
            $('#ecm_update_template_submit').show();

            $('#ecm-add-template-modal').fadeIn(150);
        });

        let ecmSelectedSessionParticipants = [];

        function ecmUpdateSelectedCount() {
            $('#ecm-session-selected-count').text(ecmSelectedSessionParticipants.length);
        }

        function ecmLoadSessionParticipants() {
            let eventId = $('#ecm_session_modal_event_id').val();
            let sessionId = $('#ecm_session_modal_session_id').val();
            let search = $('#ecm-session-participant-search').val();
            let nonce = $('#ecm_session_participant_ajax_nonce').val();

            $('#ecm-session-participant-results').html('<p>Loading participants...</p>');

            $.post(ajaxurl, {
                action: 'ecm_search_session_available_participants',
                nonce: nonce,
                event_id: eventId,
                session_id: sessionId,
                search: search
            }, function (response) {
                if (response.success) {
                    $('#ecm-session-participant-results').html(response.data.html);

                    $('.ecm-session-participant-select').each(function () {
                        let id = String($(this).val());

                        if (ecmSelectedSessionParticipants.includes(id)) {
                            $(this).prop('checked', true);
                        }
                    });
                } else {
                    $('#ecm-session-participant-results').html('<p>' + response.data + '</p>');
                }
            });
        }

        $('.ecm-open-session-participants-modal').on('click', function () {
            ecmSelectedSessionParticipants = [];
            ecmUpdateSelectedCount();

            $('#ecm-session-participant-search').val('');
            $('#ecm-session-participants-modal').fadeIn(150);

            ecmLoadSessionParticipants();
        });

        $('#ecm-session-participant-search-btn').on('click', function () {
            ecmLoadSessionParticipants();
        });

        $('#ecm-session-participant-search').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                ecmLoadSessionParticipants();
            }
        });

        $(document).on('change', '.ecm-session-participant-select', function () {
            let id = String($(this).val());

            if ($(this).is(':checked')) {
                if (!ecmSelectedSessionParticipants.includes(id)) {
                    ecmSelectedSessionParticipants.push(id);
                }
            } else {
                ecmSelectedSessionParticipants = ecmSelectedSessionParticipants.filter(function (item) {
                    return item !== id;
                });
            }

            ecmUpdateSelectedCount();
        });

        $('#ecm-add-selected-session-participants').on('click', function () {
            let eventId = $('#ecm_session_modal_event_id').val();
            let sessionId = $('#ecm_session_modal_session_id').val();
            let nonce = $('#ecm_session_participant_ajax_nonce').val();

            if (ecmSelectedSessionParticipants.length === 0) {
                alert('Please select at least one participant.');
                return;
            }

            $.post(ajaxurl, {
                action: 'ecm_add_session_participants_ajax',
                nonce: nonce,
                event_id: eventId,
                session_id: sessionId,
                participant_ids: ecmSelectedSessionParticipants
            }, function (response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data);
                }
            });
        });

        $('.ecm-edit-field').on('click', function (e) {
            e.preventDefault();

            let button = $(this);

            $('#ecm_edit_field_id').val(button.data('field-id'));
            $('#ecm_edit_field_label').val(button.data('field-label'));
            $('#ecm_edit_field_type').val(button.data('field-type'));
            $('#ecm_edit_field_order').val(button.data('field-order'));

            if (parseInt(button.data('is-required')) === 1) {
                $('#ecm_edit_is_required').prop('checked', true);
            } else {
                $('#ecm_edit_is_required').prop('checked', false);
            }

            $('#ecm-edit-field-modal').fadeIn(150);
        });
    });

})(jQuery);