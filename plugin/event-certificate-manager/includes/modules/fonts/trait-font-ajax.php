<?php

/**
 * ECM Font AJAX Actions
 *
 * Handles secure font installation requests from the Builder.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Font_Ajax
{
    /**
     * Install one curated Google Font locally.
     *
     * @return void
     */
    public function ajax_install_google_font()
    {
        check_ajax_referer(
            'ecm_install_google_font',
            'nonce'
        );

        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                [
                    'message' => 'You do not have permission to install fonts.',
                ],
                403
            );
        }

        $family = isset($_POST['family'])
            ? sanitize_text_field(
                wp_unslash($_POST['family'])
            )
            : '';

        if ($family === '') {
            wp_send_json_error(
                [
                    'message' => 'A font family is required.',
                ],
                400
            );
        }

        if (!class_exists('ECM_Google_Fonts')) {
            wp_send_json_error(
                [
                    'message' =>
                    'The Google Fonts installer is unavailable.',
                ],
                500
            );
        }

        $result = ECM_Google_Fonts::install_font($family);

        if (is_wp_error($result)) {
            wp_send_json_error(
                [
                    'message' => $result->get_error_message(),
                ],
                500
            );
        }

        wp_send_json_success($result);
    }
}
