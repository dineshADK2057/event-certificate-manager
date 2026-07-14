<?php

/**
 * ECM Font Manager
 *
 * Manages fonts available to the Template Builder and certificate renderer.
 *
 * Font metadata is stored in:
 * wp-content/uploads/ecm/fonts/fonts.json
 *
 * Font files are stored under:
 * wp-content/uploads/ecm/fonts/
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Font_Manager
{
    /**
     * Manifest version.
     */
    private const MANIFEST_VERSION = 1;

    /**
     * Return the absolute ECM fonts directory.
     *
     * @return string
     */
    public static function get_fonts_directory()
    {
        $upload_dir = wp_upload_dir();

        if (!empty($upload_dir['error'])) {
            return '';
        }

        return trailingslashit($upload_dir['basedir']) . 'ecm/fonts/';
    }

    /**
     * Return the public URL for the ECM fonts directory.
     *
     * @return string
     */
    public static function get_fonts_url()
    {
        $upload_dir = wp_upload_dir();

        if (!empty($upload_dir['error'])) {
            return '';
        }

        return trailingslashit($upload_dir['baseurl']) . 'ecm/fonts/';
    }

    /**
     * Return the absolute manifest path.
     *
     * @return string
     */
    public static function get_manifest_path()
    {
        $directory = self::get_fonts_directory();

        if (!$directory) {
            return '';
        }

        return trailingslashit($directory) . 'fonts.json';
    }

    /**
     * Ensure the fonts directory and manifest exist.
     *
     * @return bool
     */
    public static function initialize()
    {
        $directory = self::get_fonts_directory();

        if (!$directory) {
            return false;
        }

        if (!file_exists($directory) && !wp_mkdir_p($directory)) {
            return false;
        }

        $subdirectories = [
            $directory . 'google/',
            $directory . 'custom/',
        ];

        foreach ($subdirectories as $subdirectory) {
            if (!file_exists($subdirectory)) {
                wp_mkdir_p($subdirectory);
            }
        }

        $manifest_path = self::get_manifest_path();

        if (!$manifest_path) {
            return false;
        }

        if (!file_exists($manifest_path)) {
            return self::write_manifest(
                self::get_default_manifest()
            );
        }

        return true;
    }

    /**
     * Return the default manifest structure.
     *
     * @return array
     */
    private static function get_default_manifest()
    {
        return [
            'version'    => self::MANIFEST_VERSION,
            'updated_at' => current_time('mysql'),
            'fonts'      => self::get_builtin_fonts(),
        ];
    }

    /**
     * Return built-in browser-safe font definitions.
     *
     * These fonts do not require downloaded font files for the
     * Builder preview. The PDF renderer will later map them to
     * supported renderer font families or local font files.
     *
     * @return array
     */
    public static function get_builtin_fonts()
    {
        return [
            [
                'family'   => 'Arial',
                'slug'     => 'arial',
                'source'   => 'builtin',
                'category' => 'sans-serif',
                'files'    => [],
                'status'   => 'active',
            ],
            [
                'family'   => 'Helvetica',
                'slug'     => 'helvetica',
                'source'   => 'builtin',
                'category' => 'sans-serif',
                'files'    => [],
                'status'   => 'active',
            ],
            [
                'family'   => 'Verdana',
                'slug'     => 'verdana',
                'source'   => 'builtin',
                'category' => 'sans-serif',
                'files'    => [],
                'status'   => 'active',
            ],
            [
                'family'   => 'Tahoma',
                'slug'     => 'tahoma',
                'source'   => 'builtin',
                'category' => 'sans-serif',
                'files'    => [],
                'status'   => 'active',
            ],
            [
                'family'   => 'Trebuchet MS',
                'slug'     => 'trebuchet-ms',
                'source'   => 'builtin',
                'category' => 'sans-serif',
                'files'    => [],
                'status'   => 'active',
            ],
            [
                'family'   => 'Times New Roman',
                'slug'     => 'times-new-roman',
                'source'   => 'builtin',
                'category' => 'serif',
                'files'    => [],
                'status'   => 'active',
            ],
            [
                'family'   => 'Georgia',
                'slug'     => 'georgia',
                'source'   => 'builtin',
                'category' => 'serif',
                'files'    => [],
                'status'   => 'active',
            ],
            [
                'family'   => 'Garamond',
                'slug'     => 'garamond',
                'source'   => 'builtin',
                'category' => 'serif',
                'files'    => [],
                'status'   => 'active',
            ],
            [
                'family'   => 'Courier New',
                'slug'     => 'courier-new',
                'source'   => 'builtin',
                'category' => 'monospace',
                'files'    => [],
                'status'   => 'active',
            ],
        ];
    }

    /**
     * Read and decode the font manifest.
     *
     * @return array
     */
    public static function get_manifest()
    {
        self::initialize();

        $manifest_path = self::get_manifest_path();

        if (!$manifest_path || !file_exists($manifest_path)) {
            return self::get_default_manifest();
        }

        $contents = file_get_contents($manifest_path);

        if ($contents === false || trim($contents) === '') {
            return self::get_default_manifest();
        }

        $manifest = json_decode($contents, true);

        if (
            !is_array($manifest) ||
            !isset($manifest['fonts']) ||
            !is_array($manifest['fonts'])
        ) {
            return self::get_default_manifest();
        }

        return wp_parse_args(
            $manifest,
            self::get_default_manifest()
        );
    }

    /**
     * Write the complete manifest safely.
     *
     * @param array $manifest Manifest data.
     *
     * @return bool
     */
    public static function write_manifest($manifest)
    {
        $manifest_path = self::get_manifest_path();

        if (!$manifest_path) {
            return false;
        }

        $manifest['version'] = self::MANIFEST_VERSION;
        $manifest['updated_at'] = current_time('mysql');

        if (!isset($manifest['fonts']) || !is_array($manifest['fonts'])) {
            $manifest['fonts'] = [];
        }

        $encoded = wp_json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        if (!$encoded) {
            return false;
        }

        return file_put_contents(
            $manifest_path,
            $encoded,
            LOCK_EX
        ) !== false;
    }

    /**
     * Return all active fonts.
     *
     * @param string $source Optional source filter.
     *
     * @return array
     */
    public static function get_available_fonts($source = '')
    {
        $manifest = self::get_manifest();
        $fonts = $manifest['fonts'];

        $fonts = array_filter(
            $fonts,
            static function ($font) use ($source) {
                $status = isset($font['status'])
                    ? $font['status']
                    : 'active';

                if ($status !== 'active') {
                    return false;
                }

                if (
                    $source !== '' &&
                    ($font['source'] ?? '') !== $source
                ) {
                    return false;
                }

                return true;
            }
        );

        usort(
            $fonts,
            static function ($first, $second) {
                return strcasecmp(
                    $first['family'] ?? '',
                    $second['family'] ?? ''
                );
            }
        );

        return array_values($fonts);
    }

    /**
     * Find a font using its slug and optional source.
     *
     * @param string $slug   Font slug.
     * @param string $source Optional source.
     *
     * @return array|null
     */
    public static function get_font($slug, $source = '')
    {
        $slug = sanitize_title($slug);

        foreach (self::get_available_fonts() as $font) {
            if (($font['slug'] ?? '') !== $slug) {
                continue;
            }

            if (
                $source !== '' &&
                ($font['source'] ?? '') !== $source
            ) {
                continue;
            }

            return $font;
        }

        return null;
    }

    /**
     * Add or update a font in the manifest.
     *
     * Fonts are uniquely identified by slug and source.
     *
     * @param array $font Font information.
     *
     * @return bool|WP_Error
     */
    public static function register_font($font)
    {
        $family = sanitize_text_field($font['family'] ?? '');
        $source = sanitize_key($font['source'] ?? 'custom');
        $slug   = sanitize_title($font['slug'] ?? $family);

        if ($family === '' || $slug === '') {
            return new WP_Error(
                'ecm_invalid_font',
                'The font family and slug are required.'
            );
        }

        $allowed_sources = [
            'builtin',
            'google',
            'custom',
            'wordpress',
        ];

        if (!in_array($source, $allowed_sources, true)) {
            return new WP_Error(
                'ecm_invalid_font_source',
                'The supplied font source is not supported.'
            );
        }

        $normalized_font = [
            'family'   => $family,
            'slug'     => $slug,
            'source'   => $source,
            'category' => sanitize_key(
                $font['category'] ?? 'sans-serif'
            ),
            'files'    => self::sanitize_font_files(
                $font['files'] ?? []
            ),
            'status'   => isset($font['status'])
                ? sanitize_key($font['status'])
                : 'active',
        ];

        if (!empty($font['variants']) && is_array($font['variants'])) {
            $normalized_font['variants'] = array_values(
                array_map(
                    'sanitize_text_field',
                    $font['variants']
                )
            );
        }

        $manifest = self::get_manifest();
        $replaced = false;

        foreach ($manifest['fonts'] as $index => $existing_font) {
            if (
                ($existing_font['slug'] ?? '') === $slug &&
                ($existing_font['source'] ?? '') === $source
            ) {
                $manifest['fonts'][$index] = $normalized_font;
                $replaced = true;
                break;
            }
        }

        if (!$replaced) {
            $manifest['fonts'][] = $normalized_font;
        }

        if (!self::write_manifest($manifest)) {
            return new WP_Error(
                'ecm_font_manifest_write_failed',
                'The font manifest could not be updated.'
            );
        }

        return true;
    }

    /**
     * Remove a font from the manifest.
     *
     * Built-in fonts cannot be removed.
     *
     * @param string $slug   Font slug.
     * @param string $source Font source.
     *
     * @return bool|WP_Error
     */
    public static function unregister_font($slug, $source)
    {
        $slug   = sanitize_title($slug);
        $source = sanitize_key($source);

        if ($source === 'builtin') {
            return new WP_Error(
                'ecm_builtin_font',
                'Built-in fonts cannot be removed.'
            );
        }

        $manifest = self::get_manifest();
        $original_count = count($manifest['fonts']);

        $manifest['fonts'] = array_values(
            array_filter(
                $manifest['fonts'],
                static function ($font) use ($slug, $source) {
                    return !(
                        ($font['slug'] ?? '') === $slug &&
                        ($font['source'] ?? '') === $source
                    );
                }
            )
        );

        if (count($manifest['fonts']) === $original_count) {
            return new WP_Error(
                'ecm_font_not_found',
                'The requested font could not be found.'
            );
        }

        if (!self::write_manifest($manifest)) {
            return new WP_Error(
                'ecm_font_manifest_write_failed',
                'The font manifest could not be updated.'
            );
        }

        return true;
    }

    /**
     * Sanitize font-file manifest values.
     *
     * Example:
     * [
     *     '400-normal' => 'google/poppins/poppins-400.ttf',
     *     '700-normal' => 'google/poppins/poppins-700.ttf',
     * ]
     *
     * @param array $files Font files.
     *
     * @return array
     */
    private static function sanitize_font_files($files)
    {
        if (!is_array($files)) {
            return [];
        }

        $sanitized = [];

        foreach ($files as $variant => $file) {
            $variant = sanitize_key($variant);
            $file = sanitize_text_field($file);

            if ($variant === '' || $file === '') {
                continue;
            }

            /*
             * Store only paths relative to the ECM fonts directory.
             */
            $file = ltrim(
                str_replace(['../', '..\\'], '', $file),
                '/\\'
            );

            $sanitized[$variant] = $file;
        }

        return $sanitized;
    }

    /**
     * Return an absolute font-file path.
     *
     * @param string $relative_path Relative path from the fonts directory.
     *
     * @return string
     */
    public static function get_font_file_path($relative_path)
    {
        $relative_path = ltrim(
            str_replace(['../', '..\\'], '', $relative_path),
            '/\\'
        );

        return trailingslashit(
            self::get_fonts_directory()
        ) . $relative_path;
    }

    /**
     * Return a public font-file URL.
     *
     * @param string $relative_path Relative path from the fonts directory.
     *
     * @return string
     */
    public static function get_font_file_url($relative_path)
    {
        $relative_path = ltrim(
            str_replace(['../', '..\\'], '', $relative_path),
            '/\\'
        );

        return trailingslashit(
            self::get_fonts_url()
        ) . $relative_path;
    }

    /**
     * Build @font-face CSS for locally installed fonts.
     *
     * Built-in fonts have no local files and therefore generate no CSS.
     *
     * @return string
     */
    public static function get_font_face_css()
    {
        $css = '';

        foreach (self::get_available_fonts() as $font) {
            if ($variant === 'stylesheet') {
                continue;
            }
            if (empty($font['files']) || !is_array($font['files'])) {
                continue;
            }

            foreach ($font['files'] as $variant => $relative_file) {
                $parts = explode('-', $variant, 2);

                $weight = isset($parts[0]) && is_numeric($parts[0])
                    ? absint($parts[0])
                    : 400;

                $style = isset($parts[1])
                    ? sanitize_key($parts[1])
                    : 'normal';

                $url = self::get_font_file_url($relative_file);

                if (!$url) {
                    continue;
                }

                $format = self::get_font_format($relative_file);

                $css .= sprintf(
                    '@font-face{font-family:"%1$s";src:url("%2$s") format("%3$s");font-weight:%4$d;font-style:%5$s;font-display:swap;}',
                    esc_attr($font['family']),
                    esc_url($url),
                    esc_attr($format),
                    $weight,
                    esc_attr($style)
                );
            }
        }

        return $css;
    }

    /**
     * Return locally stored font stylesheets.
     *
     * @return array
     */
    public static function get_local_stylesheet_urls()
    {
        $stylesheets = [];

        foreach (self::get_available_fonts() as $font) {
            if (
                empty($font['files']['stylesheet'])
            ) {
                continue;
            }

            $stylesheets[] = self::get_font_file_url(
                $font['files']['stylesheet']
            );
        }

        return array_values(
            array_unique($stylesheets)
        );
    }

    /**
     * Determine the CSS format name from a font filename.
     *
     * @param string $file Font filename.
     *
     * @return string
     */
    private static function get_font_format($file)
    {
        $extension = strtolower(
            pathinfo($file, PATHINFO_EXTENSION)
        );

        $formats = [
            'woff2' => 'woff2',
            'woff'  => 'woff',
            'ttf'   => 'truetype',
            'otf'   => 'opentype',
        ];

        return $formats[$extension] ?? 'truetype';
    }
}
