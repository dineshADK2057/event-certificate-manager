<?php

/**
 * Template Preview Module
 *
 * Handles image and PDF preview preparation, PDF-to-image conversion,
 * builder background resolution, and sample placeholder values.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Template_Preview
{

    private function generate_pdf_preview_image($pdf_path, $preview_path, $template)
    {
        if (!class_exists('Imagick')) {
            return new WP_Error(
                'imagick_missing',
                'Imagick is not available. PDF preview generation requires the Imagick PHP extension.'
            );
        }

        $page_size   = strtolower($template->page_size);
        $orientation = strtolower($template->orientation);

        if ($page_size === 'letter') {
            $width  = $orientation === 'portrait' ? 816 : 1056;
            $height = $orientation === 'portrait' ? 1056 : 816;
        } else {
            $width  = $orientation === 'portrait' ? 794 : 1123;
            $height = $orientation === 'portrait' ? 1123 : 794;
        }

        try {
            $imagick = new Imagick();

            $imagick->setResolution(150, 150);
            $imagick->readImage($pdf_path . '[0]');
            $imagick->setImageFormat('png');
            $imagick->setImageBackgroundColor('white');
            $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);

            $flattened = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

            $flattened->resizeImage(
                $width,
                $height,
                Imagick::FILTER_LANCZOS,
                1,
                false
            );

            $flattened->writeImage($preview_path);

            $flattened->clear();
            $flattened->destroy();
            $imagick->clear();
            $imagick->destroy();

            return true;
        } catch (Throwable $exception) {
            return new WP_Error(
                'pdf_preview_failed',
                $exception->getMessage()
            );
        }
    }

    private function get_template_builder_background($template)
    {
        if (empty($template->background_file)) {
            return [
                'url'   => '',
                'path'  => '',
                'error' => '',
            ];
        }

        $upload_dir   = wp_upload_dir();
        $relative     = ltrim($template->background_file, '/');
        $original_path = trailingslashit($upload_dir['basedir']) . $relative;
        $original_url  = trailingslashit($upload_dir['baseurl']) . $relative;

        if (!file_exists($original_path)) {
            return [
                'url'   => '',
                'path'  => '',
                'error' => 'The uploaded template background file could not be found.',
            ];
        }

        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

        // PNG/JPG files can be used directly.
        if ($extension !== 'pdf') {
            return [
                'url'   => $original_url,
                'path'  => $original_path,
                'error' => '',
            ];
        }

        $directory        = dirname($relative);
        $filename         = pathinfo($relative, PATHINFO_FILENAME);
        $preview_relative = trailingslashit($directory) . $filename . '-preview.png';

        $preview_path = trailingslashit($upload_dir['basedir']) . $preview_relative;
        $preview_url  = trailingslashit($upload_dir['baseurl']) . $preview_relative;

        // Generate preview automatically for old or newly uploaded PDFs.
        if (!file_exists($preview_path)) {
            $generated = $this->generate_pdf_preview_image(
                $original_path,
                $preview_path,
                $template
            );

            if (is_wp_error($generated)) {
                return [
                    'url'   => '',
                    'path'  => '',
                    'error' => $generated->get_error_message(),
                ];
            }
        }

        return [
            'url'   => $preview_url,
            'path'  => $preview_path,
            'error' => '',
        ];
    }

    private function get_template_element_sample_value($element, $event, $template)
    {
        $key = $element->placeholder_key;

        $samples = [
            'member_id'        => '123456',
            'member_name'      => 'Lion Dinesh Adhikari',
            'home_club'        => 'LC Chitwan Nirvana',
            'event_name'       => $event->event_name ?? 'Event Name',
            'event_type'       => $event->event_type ?? 'Event Type',
            'event_venue'      => $event->venue ?? 'Venue',
            'event_start_date' => $event->start_date ?? 'Start Date',
            'event_end_date'   => $event->end_date ?? 'End Date',
            'session_name'     => 'Leadership Session',
            'session_code'     => 'SES-001',
            'tutor_name'       => 'Tutor Name',
            'session_date'     => 'Session Date',
            'issue_date'       => date_i18n('Y-m-d'),
            'certificate_id'   => 'ECM-000001',
            'qr_code'          => '[QR]',
        ];

        return $samples[$key] ?? '{' . $key . '}';
    }
}
