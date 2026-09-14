<?php

/**
 * Template Builder Module
 *
 * Handles the visual certificate builder screen, builder layout,
 * available variables, canvas presentation, and builder routing.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}


require_once ECM_PLUGIN_PATH
    . 'includes/modules/templates/builder/trait-builder-canvas.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/templates/builder/trait-builder-sidebar.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/templates/builder/trait-builder-variables.php';

require_once ECM_PLUGIN_PATH
    . 'includes/modules/templates/builder/trait-builder-participant-preview.php';

trait ECM_Template_Builder
{
    use ECM_Builder_Canvas;
    use ECM_Builder_Sidebar;
    use ECM_Builder_Variables;
    use ECM_Builder_Participant_Preview;

    private function render_template_builder_page($event_id, $template_id)
    {

        global $wpdb;

        $events_table    = $wpdb->prefix . 'ecm_events';
        $templates_table = $wpdb->prefix . 'ecm_templates';
        $elements_table  = $wpdb->prefix . 'ecm_template_elements';

        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $events_table WHERE id = %d",
                $event_id
            )
        );

        $template = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $templates_table WHERE id = %d AND event_id = %d",
                $template_id,
                $event_id
            )
        );

        if (!$event || !$template) {
            echo '<div class="notice notice-error"><p>Template not found.</p></div>';
            return;
        }

        $elements = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $elements_table WHERE template_id = %d ORDER BY element_order ASC, id ASC",
                $template_id
            )
        );

        $builder_background = $this->get_template_builder_background($template);

        $background_url   = $builder_background['url'];
        $background_error = $builder_background['error'];

        $back_url = admin_url(
            'admin.php?page=ecm-events&action=manage&event_id=' . absint($event_id) . '&tab=templates'
        );

        $variables = $this->get_template_variables($event, $template);

        /*
        * Fonts available to the Builder.
        */
        $installed_fonts = class_exists('ECM_Font_Manager')
            ? ECM_Font_Manager::get_available_fonts()
            : [];

        $available_fonts = class_exists('ECM_Google_Fonts')
            ? ECM_Google_Fonts::merge_with_installed($installed_fonts)
            : $installed_fonts;

        $font_groups = class_exists('ECM_Google_Fonts')
            ? ECM_Google_Fonts::group_fonts_for_picker($available_fonts)
            : [
                'builtin' => $available_fonts,
                'google'  => [],
            ];

        /*
        * Local @font-face declarations for installed Google/custom fonts.
        */
        $font_face_css = class_exists('ECM_Font_Manager')
            ? ECM_Font_Manager::get_font_face_css()
            : '';

?>

        <div class="wrap ecm-wrap">

            <?php if (!empty($font_face_css)) : ?>
                <style id="ecm-builder-font-faces">
                    <?php echo wp_strip_all_tags($font_face_css); ?>
                </style>
            <?php endif; ?>

            <div class="ecm-form-header">
                <a href="<?php echo esc_url($back_url); ?>" class="button">
                    ← Back to Templates
                </a>
            </div>

            <?php if (isset($_GET['element_added'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Template element added successfully.</strong></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['element_updated'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Template element updated successfully.</strong></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['element_deleted'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Template element deleted successfully.</strong></p>
                </div>
            <?php endif; ?>

            <div class="ecm-event-heading">
                <div>
                    <h2>Template Builder: <?php echo esc_html($template->template_name); ?></h2>
                    <p>
                        <strong>Event:</strong> <?php echo esc_html($event->event_name); ?>
                        &nbsp; | &nbsp;
                        <strong>Type:</strong> <?php echo esc_html($template->certificate_type); ?>
                        &nbsp; | &nbsp;
                        <strong>Page:</strong> <?php echo esc_html($template->page_size); ?> / <?php echo esc_html($template->orientation); ?>
                    </p>
                </div>
            </div>

            <input
                type="hidden"
                id="ecm_element_properties_nonce"
                value="<?php echo esc_attr(
                            wp_create_nonce('ecm_update_template_element_properties')
                        ); ?>">

            <input
                type="hidden"
                id="ecm_builder_event_id"
                value="<?php echo esc_attr($event->id); ?>">

            <input
                type="hidden"
                id="ecm_builder_template_id"
                value="<?php echo esc_attr($template->id); ?>">

            <input
                type="hidden"
                id="ecm_install_google_font_nonce"
                value="<?php echo esc_attr(
                            wp_create_nonce('ecm_install_google_font')
                        ); ?>">


            <div class="ecm-builder-layout">


                <?php
                $this->render_builder_canvas(
                    $event,
                    $template,
                    $elements,
                    $background_url,
                    $background_error
                );
                ?>

                <?php
                $this->render_builder_sidebar(
                    $event,
                    $template,
                    $elements,
                    $font_groups
                );
                ?>

            </div>
        </div>
        <?php $this->render_add_element_modal($event, $template, $variables); ?>
<?php
    }
}
