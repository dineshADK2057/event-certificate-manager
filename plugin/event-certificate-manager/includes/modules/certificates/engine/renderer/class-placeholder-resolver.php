<?php

/**
 * ECM Placeholder Resolver
 *
 * Resolves template placeholders into their final values.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Placeholder_Resolver
{
    /**
     * Resolve one placeholder.
     *
     * @param string             $placeholder_key
     * @param string             $source_type
     * @param ECM_Render_Context $context
     *
     * @return string
     */
    public function resolve(
        $placeholder_key,
        $source_type,
        ECM_Render_Context $context
    ) {
        $placeholder_key = trim(
            (string) $placeholder_key
        );

        $source_type = strtolower(
            trim((string) $source_type)
        );

        switch ($source_type) {

            case 'participant':

                $participant = $context->get_participant();

                if (
                    isset($participant->{$placeholder_key})
                ) {
                    return (string)
                        $participant->{$placeholder_key};
                }

                return (string)
                    $context->get_participant_meta_value(
                        $placeholder_key,
                        ''
                    );

            case 'event':

                $event = $context->get_event();

                if (
                    isset($event->{$placeholder_key})
                ) {
                    return (string)
                        $event->{$placeholder_key};
                }

                return '';

            case 'session':

                if (!$context->has_session()) {
                    return '';
                }

                $session = $context->get_session();

                if (
                    isset($session->{$placeholder_key})
                ) {
                    return (string)
                        $session->{$placeholder_key};
                }

                return '';

            case 'template':

                $template = $context->get_template();

                if (
                    isset($template->{$placeholder_key})
                ) {
                    return (string)
                        $template->{$placeholder_key};
                }

                return '';

            default:

                return '';
        }
    }
}