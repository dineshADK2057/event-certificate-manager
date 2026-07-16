<?php

/**
 * ECM Participant Fields UI
 *
 * Renders participant-field configuration inside Event Settings.
 *
 * Responsibilities:
 * - Default-field empty state
 * - Participant-field table
 * - Add Custom Field form
 * - Shared Edit Participant Field modal
 *
 * Database mutations are handled by ECM_Participant_Fields_Actions.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Participant_Fields_UI
{
    /* -------------------------------------------------------------------------
     * Participant Fields Section
     * ---------------------------------------------------------------------- */

    /**
     * Render participant-field configuration for one event.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_participant_fields_section($event)
    {
        global $wpdb;

        $fields_table = $wpdb->prefix . 'ecm_event_fields';

        $fields = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$fields_table}
                WHERE event_id = %d
                ORDER BY field_order ASC, id ASC",
                $event->id
            )
        );
        ?>

        <div class="ecm-panel ecm-panel-full">
            <h3>Participant Fields</h3>

            <p class="description">
                These fields define the participant data required for
                manual entry, CSV import, and certificate placeholders.
            </p>

            <?php if (empty($fields)) : ?>

                <?php $this->render_default_fields_empty_state($event); ?>

            <?php else : ?>

                <?php
                $this->render_participant_fields_table(
                    $event,
                    $fields
                );
                ?>

            <?php endif; ?>

            <hr>

            <?php $this->render_add_custom_field_form($event); ?>
        </div>

        <?php
        /*
         * Render one shared modal only.
         *
         * Previously this modal was rendered inside every table row,
         * creating duplicate element IDs in the page.
         */
        if (!empty($fields)) {
            $this->render_edit_field_modal($event);
        }
    }

    /**
     * Render the empty state used before default fields exist.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_default_fields_empty_state($event)
    {
        ?>
        <div class="notice notice-info inline">
            <p>
                No participant fields were found. Add the default
                fields before creating or importing participants.
            </p>
        </div>

        <form method="post">
            <?php
            wp_nonce_field(
                'ecm_add_default_fields',
                'ecm_default_fields_nonce'
            );
            ?>

            <input
                type="hidden"
                name="event_id"
                value="<?php echo esc_attr($event->id); ?>"
            >

            <button
                type="submit"
                name="ecm_add_default_fields_submit"
                class="button button-primary"
            >
                Add Default Fields
            </button>
        </form>
        <?php
    }

    /* -------------------------------------------------------------------------
     * Participant Fields Table
     * ---------------------------------------------------------------------- */

    /**
     * Render configured participant fields.
     *
     * @param object $event  Event database record.
     * @param array  $fields Participant field records.
     *
     * @return void
     */
    private function render_participant_fields_table(
        $event,
        $fields
    ) {
        ?>
        <table class="widefat striped ecm-fields-table">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Key</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Order</th>
                    <th width="130">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($fields as $field) : ?>
                    <?php
                    $delete_field_url = wp_nonce_url(
                        admin_url(
                            'admin.php?page=ecm-events'
                            . '&action=delete_custom_field'
                            . '&event_id='
                            . absint($event->id)
                            . '&field_id='
                            . absint($field->id)
                        ),
                        'ecm_delete_custom_field_'
                        . absint($field->id)
                    );

                    $is_protected = in_array(
                        $field->field_key,
                        [
                            'member_id',
                            'member_name',
                            'home_club',
                        ],
                        true
                    );
                    ?>

                    <tr>
                        <td>
                            <strong>
                                <?php echo esc_html(
                                    $field->field_label
                                ); ?>
                            </strong>
                        </td>

                        <td>
                            <code>
                                <?php echo esc_html(
                                    $field->field_key
                                ); ?>
                            </code>
                        </td>

                        <td>
                            <?php echo esc_html(
                                ucfirst($field->field_type)
                            ); ?>
                        </td>

                        <td>
                            <?php
                            echo (int) $field->is_required === 1
                                ? 'Yes'
                                : 'No';
                            ?>
                        </td>

                        <td>
                            <?php echo esc_html(
                                $field->field_order
                            ); ?>
                        </td>

                        <td>
                            <a
                                href="#"
                                class="ecm-edit-field"
                                data-field-id="<?php
                                echo esc_attr($field->id);
                                ?>"
                                data-field-label="<?php
                                echo esc_attr($field->field_label);
                                ?>"
                                data-field-type="<?php
                                echo esc_attr($field->field_type);
                                ?>"
                                data-is-required="<?php
                                echo esc_attr($field->is_required);
                                ?>"
                                data-field-order="<?php
                                echo esc_attr($field->field_order);
                                ?>"
                            >
                                Edit
                            </a>

                            <?php if (!$is_protected) : ?>
                                |

                                <a
                                    href="<?php echo esc_url(
                                        $delete_field_url
                                    ); ?>"
                                    onclick="return confirm('Delete this participant field? Existing participant metadata may remain but will no longer display.');"
                                    class="ecm-danger-link"
                                >
                                    Delete
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /* -------------------------------------------------------------------------
     * Add Custom Field Form
     * ---------------------------------------------------------------------- */

    /**
     * Render the Add Custom Field form.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_add_custom_field_form($event)
    {
        ?>
        <h3>Add Custom Field</h3>

        <form
            method="post"
            class="ecm-inline-form"
        >
            <?php
            wp_nonce_field(
                'ecm_add_custom_field',
                'ecm_custom_field_nonce'
            );
            ?>

            <input
                type="hidden"
                name="event_id"
                value="<?php echo esc_attr($event->id); ?>"
            >

            <div class="ecm-participants-fields-form">
                <label>
                    Field Label

                    <input
                        type="text"
                        name="field_label"
                        class="regular-text"
                        placeholder="Example: Cluster, District, Country"
                        required
                    >
                </label>

                <label>
                    Field Type

                    <select name="field_type">
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="email">Email</option>
                        <option value="select">Select</option>
                    </select>
                </label>
            </div>

            <p>
                <label>
                    <input
                        type="checkbox"
                        name="is_required"
                        value="1"
                    >

                    Required field
                </label>
            </p>

            <p>
                <button
                    type="submit"
                    name="ecm_add_custom_field_submit"
                    class="button button-primary"
                >
                    Add Field
                </button>
            </p>
        </form>
        <?php
    }

    /* -------------------------------------------------------------------------
     * Edit Participant Field Modal
     * ---------------------------------------------------------------------- */

    /**
     * Render the shared Edit Participant Field modal.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_edit_field_modal($event)
    {
        ?>
        <div
            id="ecm-edit-field-modal"
            class="ecm-modal"
            style="display:none;"
        >
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2>Edit Participant Field</h2>

                    <button
                        type="button"
                        class="ecm-modal-close"
                        aria-label="Close field editor"
                    >
                        &times;
                    </button>
                </div>

                <form method="post">
                    <?php
                    wp_nonce_field(
                        'ecm_update_custom_field',
                        'ecm_update_custom_field_nonce'
                    );
                    ?>

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?php echo esc_attr($event->id); ?>"
                    >

                    <input
                        type="hidden"
                        name="field_id"
                        id="ecm_edit_field_id"
                        value=""
                    >

                    <div class="ecm-modal-body">
                        <p>
                            <label for="ecm_edit_field_label">
                                <strong>Field Label</strong>
                            </label>

                            <input
                                type="text"
                                name="field_label"
                                id="ecm_edit_field_label"
                                class="widefat"
                                required
                            >
                        </p>

                        <p>
                            <label for="ecm_edit_field_type">
                                <strong>Field Type</strong>
                            </label>

                            <select
                                name="field_type"
                                id="ecm_edit_field_type"
                                class="widefat"
                            >
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="email">Email</option>
                                <option value="select">Select</option>
                            </select>
                        </p>

                        <p>
                            <label>
                                <input
                                    type="checkbox"
                                    name="is_required"
                                    id="ecm_edit_is_required"
                                    value="1"
                                >

                                Required field
                            </label>
                        </p>

                        <p>
                            <label for="ecm_edit_field_order">
                                <strong>Order</strong>
                            </label>

                            <input
                                type="number"
                                name="field_order"
                                id="ecm_edit_field_order"
                                class="small-text"
                                min="0"
                            >
                        </p>
                    </div>

                    <div class="ecm-modal-footer">
                        <button
                            type="submit"
                            name="ecm_update_custom_field_submit"
                            class="button button-primary"
                        >
                            Update Field
                        </button>

                        <button
                            type="button"
                            class="button ecm-modal-cancel"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}