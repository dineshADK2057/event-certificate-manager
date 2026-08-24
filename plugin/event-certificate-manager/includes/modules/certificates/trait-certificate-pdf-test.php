<?php

/**
 * ECM Certificate PDF Test
 *
 * Provides a temporary administrator-only compatibility test for
 * the tc-lib-pdf installation.
 *
 * This trait will be removed after the PDF bootstrap has been
 * validated successfully.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Certificate_PDF_Test
{
    /**
     * Generate and stream one basic compatibility PDF.
     *
     * @return void
     */
    public function handle_pdf_compatibility_test()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You are not allowed to run this PDF test.',
                    'event-certificate-manager'
                ),
                esc_html__(
                    'Permission denied',
                    'event-certificate-manager'
                ),
                [
                    'response' => 403,
                ]
            );
        }

        check_admin_referer(
            'ecm_pdf_compatibility_test'
        );

        if (!class_exists('ECM_PDF_Bootstrap')) {
            wp_die(
                esc_html__(
                    'The ECM PDF bootstrap class is unavailable.',
                    'event-certificate-manager'
                ),
                esc_html__(
                    'PDF initialization failed',
                    'event-certificate-manager'
                )
            );
        }

        $pdf = ECM_PDF_Bootstrap::create_document(
            [
                'filename' => 'ecm-pdf-engine-test.pdf',
                'title'    => 'ECM PDF Compatibility Test',
                'subject'  => 'tc-lib-pdf compatibility test',
            ]
        );

        if (is_wp_error($pdf)) {
            wp_die(
                esc_html($pdf->get_error_message()),
                esc_html__(
                    'PDF initialization failed',
                    'event-certificate-manager'
                )
            );
        }

        try {
            /*
             * Register a built-in PDF font.
             */
            $font = $pdf->font->insert(
                $pdf->pon,
                'helvetica',
                '',
                12
            );

            /*
             * Create the first document page.
             */
            $pdf->addPage();

            /*
             * Register the font resource for the active page.
             */
            $pdf->page->addContent(
                $font['out']
            );

            /*
             * Render basic compatibility-test content.
             */
            $pdf->addHTMLCell(
                html: '<h1>ECM PDF Engine Working</h1>'
                    . '<p>tc-lib-pdf loaded successfully inside WordPress.</p>'
                    . '<p>Generated font assets were detected and loaded.</p>',
                posx: 15,
                posy: 20,
                width: 180
            );

            /*
             * Build the raw PDF binary.
             */
            $raw_pdf = $pdf->getOutPDFString();

            /*
             * Remove any WordPress or PHP output that could corrupt
             * the PDF response.
             */
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            /*
             * Stream the completed PDF to the browser.
             */
            $pdf->renderPDF($raw_pdf);

            exit;
        } catch (Throwable $exception) {
            wp_die(
                esc_html($exception->getMessage()),
                esc_html__(
                    'PDF generation failed',
                    'event-certificate-manager'
                )
            );
        }
    }

    /**
     * Temporary PDF rendering playground.
     *
     * @return void
     */
    public function handle_pdf_render_playground()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You are not allowed to run this test.',
                    'event-certificate-manager'
                )
            );
        }

        check_admin_referer(
            'ecm_pdf_render_playground'
        );

        $pdf = ECM_PDF_Bootstrap::create_document();

        if (is_wp_error($pdf)) {
            wp_die(
                esc_html($pdf->get_error_message())
            );
        }

        try {
            $request = $this->find_certificate_context_test_request();

            if (is_wp_error($request)) {
                wp_die(
                    esc_html(
                        $request->get_error_message()
                    )
                );
            }

            $context = ECM_Certificate_Data_Loader::load(
                $request
            );

            if (is_wp_error($context)) {
                wp_die(
                    esc_html(
                        $context->get_error_message()
                    )
                );
            }

            if (!$context instanceof ECM_Render_Context) {
                wp_die(
                    esc_html__(
                        'The certificate data loader returned an invalid render context.',
                        'event-certificate-manager'
                    )
                );
            }

            $certificate_renderer = new ECM_Certificate_Renderer();

            $result = $certificate_renderer->render(
                $pdf,
                $context
            );

            if (is_wp_error($result)) {
                wp_die(
                    esc_html(
                        $result->get_error_message()
                    )
                );
            }

            $raw_pdf = $pdf->getOutPDFString();

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $pdf->renderPDF($raw_pdf);

            exit;
        } catch (Throwable $exception) {
            wp_die(
                esc_html($exception->getMessage())
            );
        }
    }

    /**
     * Prepare one installed Google Font for tc-lib-pdf.
     *
     * Temporary developer diagnostic.
     *
     * @return void
     */
    public function handle_pdf_font_test()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You are not allowed to run this test.',
                    'event-certificate-manager'
                )
            );
        }

        check_admin_referer(
            'ecm_pdf_font_test'
        );

        if (!class_exists('ECM_Google_Fonts')) {
            wp_die(
                esc_html__(
                    'The Google Fonts service is unavailable.',
                    'event-certificate-manager'
                )
            );
        }

        $result = ECM_Google_Fonts::prepare_pdf_font(
            'Oswald'
        );

        if (is_wp_error($result)) {
            wp_die(
                esc_html(
                    $result->get_error_message()
                )
            );
        }

        wp_die(
            sprintf(
                'Oswald PDF font prepared successfully. PDF font name: %s',
                esc_html($result)
            ),
            'ECM PDF Font Test'
        );
    }




    /**
     * Display temporary certificate-engine diagnostic buttons.
     *
     * The notice appears only for administrators on ECM admin pages.
     *
     * @return void
     */
    public function render_certificate_engine_test_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!is_admin()) {
            return;
        }

        $page = isset($_GET['page'])
            ? sanitize_key(wp_unslash($_GET['page']))
            : '';

        /*
     * Show the developer tools only inside ECM pages.
     */
        if ($page === '' || strpos($page, 'ecm') !== 0) {
            return;
        }

        $context_test_url = wp_nonce_url(
            admin_url(
                'admin-post.php?action=ecm_certificate_context_test'
            ),
            'ecm_certificate_context_test'
        );

        $playground_url = wp_nonce_url(
            admin_url(
                'admin-post.php?action=ecm_pdf_render_playground'
            ),
            'ecm_pdf_render_playground'
        );

        $pdf_font_test_url = wp_nonce_url(
            admin_url(
                'admin-post.php?action=ecm_pdf_font_test'
            ),
            'ecm_pdf_font_test'
        );

?>
        <div class="notice notice-info">
            <p>
                <strong>ECM Certificate Engine Diagnostics</strong>
            </p>

            <p>
                <a
                    href="<?php echo esc_url($context_test_url); ?>"
                    class="button button-primary">
                    Run Certificate Context Test
                </a>

                <a
                    href="<?php echo esc_url($playground_url); ?>"
                    class="button button-secondary"
                    target="_blank">
                    PDF Render Playground
                </a>

                <a
                    href="<?php echo esc_url($pdf_font_test_url); ?>"
                    class="button button-secondary">
                    Prepare Oswald PDF Font
                </a>
            </p>
        </div>
    <?php
    }

    /**
     * Run the complete certificate render-context diagnostic.
     *
     * @return void
     */
    public function handle_certificate_context_test()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You are not allowed to run this test.',
                    'event-certificate-manager'
                ),
                esc_html__(
                    'Permission denied',
                    'event-certificate-manager'
                ),
                [
                    'response' => 403,
                ]
            );
        }

        check_admin_referer(
            'ecm_certificate_context_test'
        );

        /*
     * Confirm that all required engine classes are available.
     */
        if (!class_exists('ECM_Certificate_Data_Loader')) {
            wp_die(
                esc_html__(
                    'The certificate data loader is unavailable.',
                    'event-certificate-manager'
                ),
                esc_html__(
                    'Certificate engine error',
                    'event-certificate-manager'
                )
            );
        }

        if (!class_exists('ECM_Render_Context')) {
            wp_die(
                esc_html__(
                    'The render-context class is unavailable.',
                    'event-certificate-manager'
                ),
                esc_html__(
                    'Certificate engine error',
                    'event-certificate-manager'
                )
            );
        }

        if (!class_exists('ECM_Background_Renderer')) {
            wp_die(
                esc_html__(
                    'The certificate background renderer is unavailable.',
                    'event-certificate-manager'
                ),
                esc_html__(
                    'Certificate engine error',
                    'event-certificate-manager'
                )
            );
        }

        /*
     * Find one compatible event, participant and template.
     */
        $request = $this->find_certificate_context_test_request();

        if (is_wp_error($request)) {
            $this->render_certificate_context_test_page(
                $request
            );
        }

        /*
     * Build the render context.
     */
        $context = ECM_Certificate_Data_Loader::load(
            $request
        );

        if (is_wp_error($context)) {
            $this->render_certificate_context_test_page(
                $context
            );
        }

        if (!$context instanceof ECM_Render_Context) {
            $this->render_certificate_context_test_page(
                new WP_Error(
                    'ecm_invalid_render_context',
                    'The data loader did not return a valid ECM_Render_Context object.'
                )
            );
        }

        /*
     * Resolve and validate the assigned template background.
     */
        $background_renderer = new ECM_Background_Renderer(
            $context
        );

        $background_path = $background_renderer->resolve_background_path();

        if (is_wp_error($background_path)) {
            $this->render_certificate_context_test_page(
                $background_path
            );
        }

        /*
     * Display the successful diagnostic result.
     */
        $this->render_certificate_context_test_page(
            $context,
            $background_path
        );
    }

    /**
     * Find one compatible template and participant for testing.
     *
     * @return array|WP_Error
     */
    private function find_certificate_context_test_request()
    {
        global $wpdb;

        $templates_table = $wpdb->prefix . 'ecm_templates';
        $participants_table = $wpdb->prefix . 'ecm_participants';

        $templates = $wpdb->get_results(
            "SELECT *
        FROM {$templates_table}
        ORDER BY id DESC
        LIMIT 50"
        );

        if (empty($templates)) {
            return new WP_Error(
                'ecm_context_test_no_templates',
                'No certificate templates were found.'
            );
        }

        foreach ($templates as $template) {
            $event_id = isset($template->event_id)
                ? absint($template->event_id)
                : 0;

            if (!$event_id) {
                continue;
            }

            $participant = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                FROM {$participants_table}
                WHERE event_id = %d
                ORDER BY id ASC
                LIMIT 1",
                    $event_id
                )
            );

            if (!$participant) {
                continue;
            }

            return [
                'event_id'       => $event_id,
                'participant_id' => absint($participant->id),
                'template_id'    => absint($template->id),

                'session_id' => !empty($template->session_id)
                    ? absint($template->session_id)
                    : null,

                'force' => false,
            ];
        }

        return new WP_Error(
            'ecm_context_test_no_compatible_data',
            'Templates were found, but none of their events contain a participant.'
        );
    }

    /**
     * Render the certificate context diagnostic result.
     *
     * @param ECM_Render_Context|WP_Error $result Test result.
     *
     * @return void
     */
    private function render_certificate_context_test_page(
        $result,
        $background_path = ''
    ) {
    ?>
        <!doctype html>
        <html <?php language_attributes(); ?>>

        <head>
            <meta charset="<?php bloginfo('charset'); ?>">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1">

            <title>ECM Certificate Context Test</title>

            <?php wp_admin_css('install', true); ?>

            <style>
                body {
                    background: #f0f0f1;
                    padding: 40px 20px;
                }

                .ecm-context-test {
                    max-width: 900px;
                    margin: 0 auto;
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    padding: 30px;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
                }

                .ecm-context-test h1 {
                    margin-top: 0;
                }

                .ecm-context-test table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 25px;
                }

                .ecm-context-test th,
                .ecm-context-test td {
                    padding: 12px 14px;
                    border: 1px solid #dcdcde;
                    text-align: left;
                    vertical-align: top;
                }

                .ecm-context-test th {
                    width: 280px;
                    background: #f6f7f7;
                }

                .ecm-test-success {
                    color: #008a20;
                    font-weight: 600;
                }

                .ecm-test-error {
                    color: #b32d2e;
                    font-weight: 600;
                }

                .ecm-test-actions {
                    margin-top: 25px;
                }
            </style>
        </head>

        <body>
            <main class="ecm-context-test">
                <h1>ECM Certificate Context Test</h1>

                <?php if (is_wp_error($result)) : ?>

                    <p class="ecm-test-error">
                        Context test failed.
                    </p>

                    <table>
                        <tbody>
                            <tr>
                                <th>Error code</th>
                                <td>
                                    <code>
                                        <?php
                                        echo esc_html(
                                            $result->get_error_code()
                                        );
                                        ?>
                                    </code>
                                </td>
                            </tr>

                            <tr>
                                <th>Error message</th>
                                <td>
                                    <?php
                                    echo esc_html(
                                        $result->get_error_message()
                                    );
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                <?php else : ?>

                    <?php
                    $event = $result->get_event();
                    $participant = $result->get_participant();
                    $session = $result->get_session();
                    $template = $result->get_template();
                    $participant_meta = $result->get_participant_meta();
                    $elements = $result->get_elements();
                    ?>

                    <p class="ecm-test-success">
                        ✓ Render context created successfully.
                    </p>

                    <table>
                        <tbody>
                            <tr>
                                <th>Event loaded</th>
                                <td>
                                    ✓ ID
                                    <code>
                                        <?php
                                        echo esc_html(
                                            $result->get_event_id()
                                        );
                                        ?>
                                    </code>

                                    <?php
                                    echo esc_html(
                                        $this->get_certificate_test_label(
                                            $event,
                                            [
                                                'event_name',
                                                'name',
                                                'title',
                                            ]
                                        )
                                    );
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Participant loaded</th>
                                <td>
                                    ✓ ID
                                    <code>
                                        <?php
                                        echo esc_html(
                                            $result->get_participant_id()
                                        );
                                        ?>
                                    </code>

                                    <?php
                                    echo esc_html(
                                        $this->get_certificate_test_label(
                                            $participant,
                                            [
                                                'participant_name',
                                                'member_name',
                                                'full_name',
                                                'name',
                                            ]
                                        )
                                    );
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Participant metadata</th>
                                <td>
                                    ✓
                                    <?php
                                    echo esc_html(
                                        count($participant_meta)
                                    );
                                    ?>
                                    field(s)
                                </td>
                            </tr>

                            <tr>
                                <th>Session loaded</th>
                                <td>
                                    <?php if ($result->has_session()) : ?>
                                        ✓ ID
                                        <code>
                                            <?php
                                            echo esc_html(
                                                $result->get_session_id()
                                            );
                                            ?>
                                        </code>

                                        <?php
                                        echo esc_html(
                                            $this->get_certificate_test_label(
                                                $session,
                                                [
                                                    'session_name',
                                                    'name',
                                                    'title',
                                                ]
                                            )
                                        );
                                        ?>
                                    <?php else : ?>
                                        ✓ No session assigned
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Template loaded</th>
                                <td>
                                    ✓ ID
                                    <code>
                                        <?php
                                        echo esc_html(
                                            $result->get_template_id()
                                        );
                                        ?>
                                    </code>

                                    <?php
                                    echo esc_html(
                                        $this->get_certificate_test_label(
                                            $template,
                                            [
                                                'template_name',
                                                'name',
                                                'title',
                                            ]
                                        )
                                    );
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Background validated</th>
                                <td>
                                    ✓
                                    <code>
                                        <?php
                                        echo esc_html(
                                            $background_path
                                        );
                                        ?>
                                    </code>
                                </td>
                            </tr>

                            <tr>
                                <th>Template elements</th>
                                <td>
                                    ✓
                                    <?php
                                    echo esc_html(
                                        count($elements)
                                    );
                                    ?>
                                    element(s)
                                </td>
                            </tr>

                            <tr>
                                <th>Final object</th>
                                <td>
                                    ✓
                                    <code>ECM_Render_Context</code>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                <?php endif; ?>

                <div class="ecm-test-actions">
                    <button
                        type="button"
                        class="button button-primary"
                        onclick="window.history.back();">
                        Return to ECM
                    </button>
                </div>
            </main>
        </body>

        </html>
<?php

        exit;
    }

    /**
     * Get a readable label from a database record.
     *
     * @param object|null $record     Database record.
     * @param array       $properties Candidate label properties.
     *
     * @return string
     */
    private function get_certificate_test_label(
        $record,
        $properties
    ) {
        if (!is_object($record)) {
            return '';
        }

        foreach ($properties as $property) {
            if (
                isset($record->{$property}) &&
                trim((string) $record->{$property}) !== ''
            ) {
                return (string) $record->{$property};
            }
        }

        return '';
    }
}
