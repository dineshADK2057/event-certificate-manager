<?php

/**
 * ECM Certificate Overview UI
 *
 * Renders certificate generation controls and
 * event certificate statistics.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Certificate_Overview_UI
{
    /**
     * Render the manual certificate generation panel.
     *
     * @param object $event
     * @param array  $event_participants
     * @param array  $event_templates
     *
     * @return void
     */
    private function render_certificate_generation_ui(
        $event,
        $event_participants,
        $event_templates
    ) {
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
                    action="<?php echo esc_url(
                        admin_url('admin-post.php')
                    ); ?>"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="ecm_generate_event_certificate"
                    >

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?php echo esc_attr(
                            $event->id
                        ); ?>"
                    >

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

                                    <label
                                        for="ecm-certificate-participant"
                                    >
                                        Participant
                                    </label>

                                </th>

                                <td>

                                    <select
                                        name="participant_id"
                                        id="ecm-certificate-participant"
                                        required
                                    >

                                        <option value="">
                                            Select participant
                                        </option>

                                        <?php foreach (
                                            $event_participants
                                            as $participant
                                        ) : ?>

                                            <option
                                                value="<?php
                                                    echo esc_attr(
                                                        $participant->id
                                                    );
                                                ?>"
                                            >

                                                <?php

                                                $participant_label =
                                                    !empty(
                                                        $participant
                                                            ->member_name
                                                    )
                                                        ? $participant
                                                            ->member_name
                                                        : 'Participant';

                                                echo esc_html(
                                                    $participant_label
                                                        . ' — '
                                                        . $participant
                                                            ->member_id
                                                );

                                                ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </td>

                            </tr>

                            <tr>

                                <th scope="row">

                                    <label
                                        for="ecm-certificate-template"
                                    >
                                        Template
                                    </label>

                                </th>

                                <td>

                                    <select
                                        name="template_id"
                                        id="ecm-certificate-template"
                                        required
                                    >

                                        <option value="">
                                            Select template
                                        </option>

                                        <?php foreach (
                                            $event_templates
                                            as $template
                                        ) : ?>

                                            <option
                                                value="<?php
                                                    echo esc_attr(
                                                        $template->id
                                                    );
                                                ?>"
                                            >

                                                <?php

                                                $template_label =
                                                    $template
                                                        ->template_name;

                                                if (
                                                    !empty(
                                                        $template
                                                            ->session_name
                                                    )
                                                ) {
                                                    $template_label .=
                                                        ' — '
                                                        . $template
                                                            ->session_name;
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

        <?php
    }

    /**
     * Render event certificate statistics.
     *
     * @param int $total_certificates
     * @param int $generated_certificates
     * @param int $emailed_certificates
     *
     * @return void
     */
    private function render_certificate_statistics_ui(
        $total_certificates,
        $generated_certificates,
        $emailed_certificates
    ) {
        ?>

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
    }
}