<?php

/**
 * ECM Event Certificates
 *
 * Renders and manages certificates belonging to one event.
 *
 * Certificate rendering itself remains inside the dedicated
 * certificate engine. This trait is responsible only for the
 * event-scoped certificate management interface.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Certificates
{
    /**
     * Render the Certificates tab for one event.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function tab_certificates($event)
    {
        $page_data =
            $this->get_event_certificate_page_data(
                $event
            );

        $event_templates =
            $page_data['event_templates'];

        $event_participants =
            $page_data['event_participants'];

        $total_certificates =
            $page_data['total_certificates'];

        $generated_certificates =
            $page_data['generated_certificates'];

        $emailed_certificates =
            $page_data['emailed_certificates'];

        $certificate_participants =
            $page_data['certificate_participants'];

        $certificate_details =
            $page_data['certificate_details'];

?>


        <!-- notices -->
        <?php
        $this->render_certificate_notices();
        ?>





        <div class="ecm-panel ecm-panel-full">

            <h3>Generate Certificate</h3>

            <p class="description">
                Generate and store a certificate for one participant
                using a template belonging to this event.
            </p>

            <?php if (empty($event_participants)) : ?>

                <div class="notice notice-warning inline">
                    <p>
                        Add at least one participant to this event
                        before generating certificates.
                    </p>
                </div>

            <?php elseif (empty($event_templates)) : ?>

                <div class="notice notice-warning inline">
                    <p>
                        Create at least one certificate template
                        before generating certificates.
                    </p>
                </div>

            <?php else : ?>

                <form
                    method="post"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

                    <input
                        type="hidden"
                        name="action"
                        value="ecm_generate_event_certificate">

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?php echo esc_attr($event->id); ?>">

                    <?php
                    wp_nonce_field(
                        'ecm_generate_event_certificate',
                        'ecm_generate_certificate_nonce'
                    );
                    ?>

                    <table class="form-table">

                        <tbody>

                            <tr>

                                <th scope="row">
                                    <label for="ecm-certificate-participant">
                                        Participant
                                    </label>
                                </th>

                                <td>

                                    <select
                                        name="participant_id"
                                        id="ecm-certificate-participant"
                                        required>

                                        <option value="">
                                            Select participant
                                        </option>

                                        <?php foreach (
                                            $event_participants
                                            as $participant
                                        ) : ?>

                                            <option
                                                value="<?php echo esc_attr($participant->id); ?>">
                                                <?php
                                                $participant_label =
                                                    !empty($participant->member_name)
                                                    ? $participant->member_name
                                                    : 'Participant';

                                                echo esc_html(
                                                    $participant_label
                                                        . ' — '
                                                        . $participant->member_id
                                                );
                                                ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </td>

                            </tr>

                            <tr>

                                <th scope="row">
                                    <label for="ecm-certificate-template">
                                        Template
                                    </label>
                                </th>

                                <td>

                                    <select
                                        name="template_id"
                                        id="ecm-certificate-template"
                                        required>

                                        <option value="">
                                            Select template
                                        </option>

                                        <?php foreach (
                                            $event_templates
                                            as $template
                                        ) : ?>

                                            <option
                                                value="<?php echo esc_attr($template->id); ?>">
                                                <?php
                                                $template_label =
                                                    $template->template_name;

                                                if (
                                                    !empty($template->session_name)
                                                ) {
                                                    $template_label .=
                                                        ' — '
                                                        . $template->session_name;
                                                } else {
                                                    $template_label .=
                                                        ' — Event-wide';
                                                }

                                                echo esc_html(
                                                    $template_label
                                                );
                                                ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                    <?php
                    submit_button(
                        'Generate Certificate',
                        'primary',
                        'submit',
                        false
                    );
                    ?>

                </form>

            <?php endif; ?>

        </div>

        <div class="ecm-panel">

            <h3>Certificate Statistics</h3>

            <div class="ecm-stat-grid">

                <div class="ecm-stat-card">

                    <span class="ecm-stat-number">
                        <?php
                        echo esc_html(
                            $total_certificates
                        );
                        ?>
                    </span>

                    <span class="ecm-stat-label">
                        Total Certificates
                    </span>

                </div>

                <div class="ecm-stat-card">

                    <span class="ecm-stat-number">
                        <?php
                        echo esc_html(
                            $generated_certificates
                        );
                        ?>
                    </span>

                    <span class="ecm-stat-label">
                        Generated
                    </span>

                </div>

                <div class="ecm-stat-card">

                    <span class="ecm-stat-number">
                        <?php
                        echo esc_html(
                            $emailed_certificates
                        );
                        ?>
                    </span>

                    <span class="ecm-stat-label">
                        Emailed
                    </span>

                </div>

            </div>

        </div>


        <?php

        $this->render_certificate_recipients_ui(
            $event,
            $certificate_participants,
            $certificate_details
        );



    }
}
