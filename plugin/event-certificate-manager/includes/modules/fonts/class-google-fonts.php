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
    public static function get_preview_url($family)
    {
        $family = str_replace(' ', '+', trim($family));

        return add_query_arg(
            [
                'family'  => $family . ':wght@400',
                'display' => 'swap',
            ],
            'https://fonts.googleapis.com/css2'
        );
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
}