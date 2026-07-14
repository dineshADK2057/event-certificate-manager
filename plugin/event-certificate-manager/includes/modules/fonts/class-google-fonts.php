<?php

/**
 * ECM Google Fonts Catalogue
 *
 * Provides a curated list of Google Fonts suitable for certificates.
 * This class currently supplies catalogue metadata and preview URLs.
 * Local font installation will be handled in the next step.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Google_Fonts
{
    /**
     * Return the curated Google Fonts catalogue.
     *
     * @return array
     */
    public static function get_catalogue()
    {
        return [
            // Sans Serif.
            self::font('Poppins', 'sans-serif'),
            self::font('Roboto', 'sans-serif'),
            self::font('Montserrat', 'sans-serif'),
            self::font('Open Sans', 'sans-serif'),
            self::font('Lato', 'sans-serif'),
            self::font('Nunito', 'sans-serif'),
            self::font('Raleway', 'sans-serif'),
            self::font('Oswald', 'sans-serif'),
            self::font('Bebas Neue', 'display'),
            self::font('Inter', 'sans-serif'),

            // Serif.
            self::font('Playfair Display', 'serif'),
            self::font('Merriweather', 'serif'),
            self::font('Libre Baskerville', 'serif'),
            self::font('Cormorant Garamond', 'serif'),
            self::font('Lora', 'serif'),
            self::font('Cinzel', 'serif'),

            // Script and handwritten.
            self::font('Great Vibes', 'handwriting'),
            self::font('Birthstone', 'handwriting'),
            self::font('Allura', 'handwriting'),
            self::font('Alex Brush', 'handwriting'),
            self::font('Dancing Script', 'handwriting'),
            self::font('Parisienne', 'handwriting'),
            self::font('Sacramento', 'handwriting'),
            self::font('Satisfy', 'handwriting'),

            // Display.
            self::font('Anton', 'display'),
            self::font('Abril Fatface', 'display'),
            self::font('DM Serif Display', 'display'),
        ];
    }

    /**
     * Create a normalized font definition.
     *
     * @param string $family   Font family.
     * @param string $category Font category.
     *
     * @return array
     */
    private static function font($family, $category)
    {
        return [
            'family'      => $family,
            'slug'        => sanitize_title($family),
            'source'      => 'google',
            'category'    => $category,
            'files'       => [],
            'status'      => 'available',
            'installed'   => false,
            'preview_url' => self::get_preview_url($family),
        ];
    }

    /**
     * Return a Google Fonts CSS2 preview URL.
     *
     * @param string $family Font family.
     *
     * @return string
     */
    /**
     * Return the Google Fonts CSS URL used for browser previews.
     *
     * The CSS2 API requires spaces in family names to be represented
     * with plus signs. The URL is assembled directly to avoid
     * double-encoding by add_query_arg().
     *
     * @param string $family Font family.
     *
     * @return string
     */
    public static function get_preview_url($family)
    {
        $family = sanitize_text_field($family);

        if ($family === '') {
            return '';
        }

        $encoded_family = str_replace(
            '%20',
            '+',
            rawurlencode(trim($family))
        );

        return 'https://fonts.googleapis.com/css2?family='
            . $encoded_family
            . ':wght@400&display=swap';
    }

    /**
     * Combine installed fonts with the curated Google catalogue.
     *
     * Installed Google fonts replace their uninstalled catalogue entry.
     *
     * @param array $installed_fonts Fonts from ECM_Font_Manager.
     *
     * @return array
     */
    public static function merge_with_installed($installed_fonts)
    {
        $fonts_by_key = [];

        foreach ($installed_fonts as $font) {
            $source = $font['source'] ?? 'builtin';
            $slug   = $font['slug'] ?? sanitize_title(
                $font['family'] ?? ''
            );

            if (!$slug) {
                continue;
            }

            $font['installed'] = true;

            $fonts_by_key[$source . ':' . $slug] = $font;
        }

        foreach (self::get_catalogue() as $font) {
            $key = 'google:' . $font['slug'];

            if (isset($fonts_by_key[$key])) {
                continue;
            }

            $fonts_by_key[$key] = $font;
        }

        $fonts = array_values($fonts_by_key);

        usort(
            $fonts,
            static function ($first, $second) {
                $source_order = [
                    'builtin'   => 1,
                    'google'    => 2,
                    'wordpress' => 3,
                    'custom'    => 4,
                ];

                $first_source  = $first['source'] ?? 'builtin';
                $second_source = $second['source'] ?? 'builtin';

                $first_order  = $source_order[$first_source] ?? 99;
                $second_order = $source_order[$second_source] ?? 99;

                if ($first_order !== $second_order) {
                    return $first_order <=> $second_order;
                }

                return strcasecmp(
                    $first['family'] ?? '',
                    $second['family'] ?? ''
                );
            }
        );

        return $fonts;
    }

    /**
     * Install a curated Google Font locally.
     *
     * Downloads the Google Fonts CSS and all referenced WOFF/WOFF2
     * assets, rewrites the CSS to local URLs, and registers the font
     * in the ECM font manifest.
     *
     * @param string $family Font family.
     *
     * @return array|WP_Error
     */
    public static function install_font($family)
    {
        $family = sanitize_text_field($family);

        if ($family === '') {
            return new WP_Error(
                'ecm_google_font_missing',
                'A Google Font family is required.'
            );
        }

        $catalogue_font = self::get_catalogue_font($family);

        if (!$catalogue_font) {
            return new WP_Error(
                'ecm_google_font_not_allowed',
                'This font is not available in the ECM Google Fonts catalogue.'
            );
        }

        if (!class_exists('ECM_Font_Manager')) {
            return new WP_Error(
                'ecm_font_manager_missing',
                'The ECM Font Manager is unavailable.'
            );
        }

        ECM_Font_Manager::initialize();

        $existing = ECM_Font_Manager::get_font(
            $catalogue_font['slug'],
            'google'
        );

        if ($existing && !empty($existing['files'])) {
            return [
                'installed' => true,
                'font'      => $existing,
                'message'   => 'The font is already installed.',
            ];
        }

        $fonts_directory = ECM_Font_Manager::get_fonts_directory();

        if (!$fonts_directory) {
            return new WP_Error(
                'ecm_fonts_directory_missing',
                'The ECM fonts directory is unavailable.'
            );
        }

        $relative_directory = 'google/' . $catalogue_font['slug'] . '/';
        $absolute_directory = trailingslashit($fonts_directory)
            . $relative_directory;

        if (
            !file_exists($absolute_directory) &&
            !wp_mkdir_p($absolute_directory)
        ) {
            return new WP_Error(
                'ecm_google_font_directory_failed',
                'The local Google Font directory could not be created.'
            );
        }

        $css_url = self::get_install_css_url($family);

        $css_response = wp_safe_remote_get(
            $css_url,
            [
                'timeout'     => 30,
                'redirection' => 3,
                /*
             * Request modern WOFF2 assets.
             */
                'user-agent'  =>
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                    . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                    . 'Chrome/124.0 Safari/537.36',
            ]
        );

        if (is_wp_error($css_response)) {
            return new WP_Error(
                'ecm_google_font_css_failed',
                $css_response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($css_response);
        $css         = wp_remote_retrieve_body($css_response);

        if ($status_code !== 200 || trim($css) === '') {
            return new WP_Error(
                'ecm_google_font_css_invalid',
                'Google Fonts did not return valid font CSS.'
            );
        }

        $font_urls = self::extract_font_urls($css);

        if (empty($font_urls)) {
            return new WP_Error(
                'ecm_google_font_files_missing',
                'No downloadable font files were found in the Google Fonts response.'
            );
        }

        $registered_files = [];
        $rewritten_css     = $css;

        foreach ($font_urls as $index => $remote_url) {
            $download = self::download_font_file(
                $remote_url,
                $absolute_directory,
                $catalogue_font['slug'],
                $index + 1
            );

            if (is_wp_error($download)) {
                self::delete_directory_contents($absolute_directory);

                return $download;
            }

            $registered_files['web-' . ($index + 1)] = $relative_directory . $download['filename'];

            $rewritten_css = str_replace(
                $remote_url,
                $download['local_url'],
                $rewritten_css
            );
        }

        $css_filename = $catalogue_font['slug'] . '.css';
        $css_path     = $absolute_directory . $css_filename;

        if (
            file_put_contents(
                $css_path,
                $rewritten_css,
                LOCK_EX
            ) === false
        ) {
            self::delete_directory_contents($absolute_directory);

            return new WP_Error(
                'ecm_google_font_css_write_failed',
                'The local Google Font stylesheet could not be written.'
            );
        }

        $registered_files['stylesheet'] =
            $relative_directory . $css_filename;

        $registered = ECM_Font_Manager::register_font(
            [
                'family'   => $catalogue_font['family'],
                'slug'     => $catalogue_font['slug'],
                'source'   => 'google',
                'category' => $catalogue_font['category'],
                'files'    => $registered_files,
                'variants' => ['400-normal'],
                'status'   => 'active',
            ]
        );

        if (is_wp_error($registered)) {
            self::delete_directory_contents($absolute_directory);

            return $registered;
        }

        $installed_font = ECM_Font_Manager::get_font(
            $catalogue_font['slug'],
            'google'
        );

        return [
            'installed' => true,
            'font'      => $installed_font,
            'css_url'   => ECM_Font_Manager::get_font_file_url(
                $registered_files['stylesheet']
            ),
            'message'   => sprintf(
                '%s was installed successfully.',
                $catalogue_font['family']
            ),
        ];
    }

    /**
     * Find a curated catalogue font by family name.
     *
     * @param string $family Font family.
     *
     * @return array|null
     */
    private static function get_catalogue_font($family)
    {
        foreach (self::get_catalogue() as $font) {
            if (
                isset($font['family']) &&
                strcasecmp($font['family'], $family) === 0
            ) {
                return $font;
            }
        }

        return null;
    }

    /**
     * Return the CSS2 URL used for local installation.
     *
     * @param string $family Font family.
     *
     * @return string
     */
    /**
     * Return the Google Fonts CSS2 URL used for installation.
     *
     * @param string $family Font family.
     *
     * @return string
     */
    private static function get_install_css_url($family)
    {
        $family = sanitize_text_field($family);

        if ($family === '') {
            return '';
        }

        $encoded_family = str_replace(
            '%20',
            '+',
            rawurlencode(trim($family))
        );

        return 'https://fonts.googleapis.com/css2?family='
            . $encoded_family
            . ':wght@400&display=swap';
    }

    /**
     * Extract unique font-file URLs from Google Fonts CSS.
     *
     * @param string $css Stylesheet content.
     *
     * @return array
     */
    private static function extract_font_urls($css)
    {
        preg_match_all(
            '/url\((["\']?)(https:\/\/fonts\.gstatic\.com\/[^)"\']+)\1\)/i',
            $css,
            $matches
        );

        if (empty($matches[2])) {
            return [];
        }

        return array_values(
            array_unique(
                array_map(
                    'esc_url_raw',
                    $matches[2]
                )
            )
        );
    }

    /**
     * Download one font file into the local family directory.
     *
     * @param string $remote_url         Remote Google font URL.
     * @param string $absolute_directory Local directory.
     * @param string $font_slug          Font slug.
     * @param int    $sequence           File sequence.
     *
     * @return array|WP_Error
     */
    private static function download_font_file(
        $remote_url,
        $absolute_directory,
        $font_slug,
        $sequence
    ) {
        $validated_url = wp_http_validate_url($remote_url);

        if (!$validated_url) {
            return new WP_Error(
                'ecm_google_font_url_invalid',
                'Google returned an invalid font URL.'
            );
        }

        $host = wp_parse_url($validated_url, PHP_URL_HOST);

        if (
            !$host ||
            !preg_match('/(^|\.)fonts\.gstatic\.com$/i', $host)
        ) {
            return new WP_Error(
                'ecm_google_font_host_invalid',
                'The font file did not come from an approved Google Fonts host.'
            );
        }

        $response = wp_safe_remote_get(
            $validated_url,
            [
                'timeout'     => 45,
                'redirection' => 3,
            ]
        );

        if (is_wp_error($response)) {
            return new WP_Error(
                'ecm_google_font_download_failed',
                $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body        = wp_remote_retrieve_body($response);

        if ($status_code !== 200 || $body === '') {
            return new WP_Error(
                'ecm_google_font_download_invalid',
                'A Google Font file could not be downloaded.'
            );
        }

        $content_type = strtolower(
            (string) wp_remote_retrieve_header(
                $response,
                'content-type'
            )
        );

        $extension = self::get_download_extension(
            $validated_url,
            $content_type
        );

        $filename = sanitize_file_name(
            $font_slug . '-' . absint($sequence) . '.' . $extension
        );

        $local_path = trailingslashit($absolute_directory) . $filename;

        if (
            file_put_contents(
                $local_path,
                $body,
                LOCK_EX
            ) === false
        ) {
            return new WP_Error(
                'ecm_google_font_file_write_failed',
                'A downloaded Google Font file could not be saved.'
            );
        }

        $relative_path = 'google/' . $font_slug . '/' . $filename;

        return [
            'filename'   => $filename,
            'local_path' => $local_path,
            'local_url'  => ECM_Font_Manager::get_font_file_url(
                $relative_path
            ),
        ];
    }

    /**
     * Determine the downloaded font extension.
     *
     * @param string $url          Font URL.
     * @param string $content_type Response content type.
     *
     * @return string
     */
    private static function get_download_extension($url, $content_type)
    {
        $path_extension = strtolower(
            pathinfo(
                (string) wp_parse_url($url, PHP_URL_PATH),
                PATHINFO_EXTENSION
            )
        );

        if (
            in_array(
                $path_extension,
                ['woff2', 'woff', 'ttf', 'otf'],
                true
            )
        ) {
            return $path_extension;
        }

        if (strpos($content_type, 'woff2') !== false) {
            return 'woff2';
        }

        if (strpos($content_type, 'woff') !== false) {
            return 'woff';
        }

        if (
            strpos($content_type, 'truetype') !== false ||
            strpos($content_type, 'ttf') !== false
        ) {
            return 'ttf';
        }

        return 'woff2';
    }

    /**
     * Remove partially downloaded files after a failed installation.
     *
     * @param string $directory Absolute font directory.
     *
     * @return void
     */
    private static function delete_directory_contents($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob(
            trailingslashit($directory) . '*'
        );

        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                wp_delete_file($file);
            }
        }
    }
}
