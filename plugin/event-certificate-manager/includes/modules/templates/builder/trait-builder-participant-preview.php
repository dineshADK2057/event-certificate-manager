<?php

/**
 * Template Builder Participant Preview
 *
 * Provides participant selection and preview data for
 * the visual certificate Builder.
 *
 * The selected participant is used only for previewing
 * participant placeholders inside the Builder.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Builder_Participant_Preview
{
    /**
     * Get participants available for preview.
     *
     * Event-wide templates:
     * - participants associated with the event
     *
     * Session templates:
     * - participants associated with the event
     * - participants assigned to the template session
     *
     * @param object $event
     * @param object $template
     *
     * @return array
     */
    private function get_builder_preview_participants(
        $event,
        $template
    ) {
        global $wpdb;

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $event_participants_table =
            $wpdb->prefix . 'ecm_event_participants';

        $session_participants_table =
            $wpdb->prefix . 'ecm_session_participants';

        $participant_meta_table =
            $wpdb->prefix . 'ecm_participant_meta';


        /*
         * Session-specific template.
         */
        if (!empty($template->session_id)) {

            $participants = $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT DISTINCT p.*
                    FROM {$participants_table} p

                    INNER JOIN {$event_participants_table} ep
                        ON ep.participant_id = p.id

                    INNER JOIN {$session_participants_table} sp
                        ON sp.participant_id = p.id

                    WHERE ep.event_id = %d
                      AND sp.session_id = %d

                    ORDER BY p.member_id ASC
                    ",
                    $event->id,
                    $template->session_id
                )
            );
        } else {

            /*
             * Event-wide template.
             */
            $participants = $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT DISTINCT p.*
                    FROM {$participants_table} p

                    INNER JOIN {$event_participants_table} ep
                        ON ep.participant_id = p.id

                    WHERE ep.event_id = %d

                    ORDER BY p.member_id ASC
                    ",
                    $event->id
                )
            );
        }


        if (empty($participants)) {
            return [];
        }


        /*
         * Attach participant meta values to each participant.
         */
        foreach ($participants as $participant) {

            $meta_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT
                        meta_key,
                        meta_value
                    FROM {$participant_meta_table}
                    WHERE participant_id = %d
                    ",
                    $participant->id
                ),
                OBJECT_K
            );


            $participant->preview_values = [
                'member_id' => (string) $participant->member_id,
            ];


            foreach ($meta_rows as $meta_key => $meta_row) {
                $participant->preview_values[$meta_key] =
                    (string) $meta_row->meta_value;
            }
        }


        return $participants;
    }


    /**
     * Render Participant Preview controls.
     *
     * @param object $event
     * @param object $template
     *
     * @return void
     */
    private function render_builder_participant_preview(
        $event,
        $template
    ) {
        $participants =
            $this->get_builder_preview_participants(
                $event,
                $template
            );

?>

        <section class="ecm-property-card ecm-builder-participant-preview">

            <div class="ecm-property-card-header">

                <h4>
                    Participant Preview
                </h4>

                <p>
                    Preview this certificate using a real participant.
                </p>

            </div>


            <?php if (empty($participants)) : ?>

                <div class="ecm-builder-preview-empty">

                    No eligible participants are available
                    for this template.

                </div>

            <?php else : ?>

                <div class="ecm-property-field">

                    <label>
                        Participant
                    </label>


                    <div
                        class="ecm-builder-participant-combobox"
                        id="ecm_builder_participant_combobox">


                        <!-- Selected participant / trigger -->

                        <button
                            type="button"
                            class="ecm-builder-participant-trigger"
                            id="ecm_builder_participant_trigger"
                            aria-haspopup="listbox"
                            aria-expanded="false">

                            <span
                                class="ecm-builder-participant-trigger-text"
                                id="ecm_builder_participant_trigger_text">

                                Select participant

                            </span>

                            <span
                                class="dashicons dashicons-arrow-down-alt2"
                                aria-hidden="true">
                            </span>

                        </button>


                        <!-- Dropdown -->

                        <div
                            class="ecm-builder-participant-dropdown"
                            id="ecm_builder_participant_dropdown"
                            style="display:none;">


                            <!-- Search inside dropdown -->

                            <div class="ecm-builder-participant-search">

                                <span
                                    class="dashicons dashicons-search"
                                    aria-hidden="true">
                                </span>

                                <input
                                    type="search"
                                    id="ecm_builder_participant_search"
                                    autocomplete="off"
                                    placeholder="Search participant...">

                            </div>


                            <!-- Results -->

                            <div
                                class="ecm-builder-participant-options"
                                id="ecm_builder_participant_options"
                                role="listbox">


                                <?php foreach ($participants as $participant) : ?>

                                    <?php

                                    $values =
                                        $participant->preview_values;


                                    $member_name =
                                        !empty($values['member_name'])
                                        ? $values['member_name']
                                        : (
                                            !empty($values['name'])
                                            ? $values['name']
                                            : 'Participant'
                                        );


                                    $home_club =
                                        !empty($values['home_club'])
                                        ? $values['home_club']
                                        : (
                                            !empty($values['club'])
                                            ? $values['club']
                                            : ''
                                        );


                                    $email =
                                        !empty($values['email'])
                                        ? $values['email']
                                        : '';


                                    $search_text =
                                        strtolower(
                                            implode(
                                                ' ',
                                                [
                                                    $member_name,
                                                    $participant->member_id,
                                                    $home_club,
                                                    $email,
                                                ]
                                            )
                                        );

                                    ?>


                                    <button
                                        type="button"

                                        class="ecm-builder-participant-option"

                                        role="option"

                                        data-participant-id="<?php
                                                                echo esc_attr(
                                                                    $participant->id
                                                                );
                                                                ?>"

                                        data-participant-label="<?php
                                                                echo esc_attr(
                                                                    $member_name
                                                                        . ' — '
                                                                        . $participant->member_id
                                                                );
                                                                ?>"

                                        data-search="<?php
                                                        echo esc_attr(
                                                            $search_text
                                                        );
                                                        ?>"

                                        data-preview-values="<?php
                                                                echo esc_attr(
                                                                    wp_json_encode(
                                                                        $values
                                                                    )
                                                                );
                                                                ?>">


                                        <span class="ecm-builder-participant-option-main">

                                            <?php
                                            echo esc_html(
                                                $member_name
                                            );
                                            ?>

                                        </span>


                                        <span class="ecm-builder-participant-option-id">

                                            <?php
                                            echo esc_html(
                                                $participant->member_id
                                            );
                                            ?>

                                        </span>


                                    </button>


                                <?php endforeach; ?>


                                <div
                                    class="ecm-builder-participant-no-results"
                                    id="ecm_builder_participant_no_results"
                                    style="display:none;">

                                    No participants found.

                                </div>


                            </div>

                        </div>


                        <!-- Hidden value -->

                        <input
                            type="hidden"
                            id="ecm_builder_preview_participant"
                            value="">

                    </div>

                </div>


                <div
                    class="ecm-builder-preview-participant-details"
                    id="ecm_builder_preview_participant_details">


                    <div class="ecm-builder-preview-member-id-value"
                        id="ecm_preview_participant_member_id">
                        —
                    </div>


                    <div
                        class="ecm-builder-preview-member-name"
                        id="ecm_preview_participant_name">
                        —
                    </div>


                    <div
                        class="ecm-builder-preview-home-club"
                        id="ecm_preview_participant_home_club">
                        —
                    </div>


                    <a
                        class="ecm-builder-preview-email"
                        id="ecm_preview_participant_email"
                        href=""
                        style="display:none;">
                    </a>

                </div>

            <?php endif; ?>

        </section>

<?php
    }
}
