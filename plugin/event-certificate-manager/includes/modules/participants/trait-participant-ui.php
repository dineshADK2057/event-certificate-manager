<?php

/**
 * ECM Participant UI
 *
 * Handles the event Participants interface.
 *
 * Responsibilities:
 * - Participant search and bulk-action toolbar
 * - Participant table rendering
 * - Add/Edit Participant modal
 * - CSV upload modal
 *
 * This trait renders UI only. Participant database changes and CSV
 * processing are handled by separate Participants traits.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Participant_UI
{
    /* -------------------------------------------------------------------------
     * Participant Toolbar
     * ---------------------------------------------------------------------- */

    /**
     * Render participant bulk actions and search controls.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_participant_toolbar($event)
    {
        $search = isset($_GET['participant_search'])
            ? sanitize_text_field(
                wp_unslash($_GET['participant_search'])
            )
            : '';
        ?>
        <div class="ecm-list-toolbar">
            <form
                method="post"
                class="ecm-list-toolbar-left"
                id="ecm-bulk-participant-form"
            >
                <?php
                wp_nonce_field(
                    'ecm_bulk_participant_action',
                    'ecm_bulk_participant_nonce'
                );
                ?>

                <input
                    type="hidden"
                    name="event_id"
                    value="<?php echo esc_attr($event->id); ?>"
                >

                <select name="bulk_action">
                    <option value="">Bulk actions</option>
                    <option value="delete">Delete</option>
                </select>

                <button
                    type="submit"
                    name="ecm_bulk_participant_submit"
                    class="button"
                >
                    Apply
                </button>
            </form>

            <form
                method="get"
                class="ecm-list-toolbar-right"
            >
                <input
                    type="hidden"
                    name="page"
                    value="ecm-events"
                >

                <input
                    type="hidden"
                    name="action"
                    value="manage"
                >

                <input
                    type="hidden"
                    name="event_id"
                    value="<?php echo esc_attr($event->id); ?>"
                >

                <input
                    type="hidden"
                    name="tab"
                    value="participants"
                >

                <input
                    type="search"
                    name="participant_search"
                    value="<?php echo esc_attr($search); ?>"
                    placeholder="Search participants..."
                    class="regular-text"
                >

                <button
                    type="submit"
                    class="button"
                >
                    Search
                </button>

                <?php if ($search !== '') : ?>
                    <a
                        href="<?php echo esc_url(
                            admin_url(
                                'admin.php?page=ecm-events'
                                . '&action=manage'
                                . '&event_id='
                                . absint($event->id)
                                . '&tab=participants'
                            )
                        ); ?>"
                        class="button"
                    >
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /* -------------------------------------------------------------------------
     * Participant List
     * ---------------------------------------------------------------------- */

    /**
     * Query and render participants belonging to one event.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_participant_list_section($event)
    {
        global $wpdb;

        $participants_table =
            $wpdb->prefix . 'ecm_participants';

        $meta_table =
            $wpdb->prefix . 'ecm_participant_meta';

        $fields = $this->get_event_fields($event->id);

        if (empty($fields)) {
            ?>
            <div class="notice notice-warning inline">
                <p>
                    Participant fields are not configured for this event.
                </p>
            </div>
            <?php

            return;
        }

        $search = isset($_GET['participant_search'])
            ? sanitize_text_field(
                wp_unslash($_GET['participant_search'])
            )
            : '';

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';

            $participants = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT p.*
                    FROM {$participants_table} p
                    LEFT JOIN {$meta_table} m
                        ON p.id = m.participant_id
                    WHERE p.event_id = %d
                    AND (
                        p.member_id LIKE %s
                        OR m.meta_value LIKE %s
                    )
                    ORDER BY p.id DESC",
                    $event->id,
                    $like,
                    $like
                )
            );
        } else {
            $participants = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$participants_table}
                    WHERE event_id = %d
                    ORDER BY id DESC",
                    $event->id
                )
            );
        }

        if (empty($participants)) {
            ?>
            <div class="ecm-elements-empty-state">
                <h4>No participants found</h4>

                <p>
                    Add a participant manually or upload a CSV file
                    to begin managing this event.
                </p>

                <button
                    type="button"
                    class="button button-primary ecm-open-participant-modal"
                >
                    + Add Participant
                </button>
            </div>
            <?php

            return;
        }
        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th width="35">
                        <input
                            type="checkbox"
                            id="ecm-select-all-participants"
                        >
                    </th>

                    <?php foreach ($fields as $field) : ?>
                        <th>
                            <?php echo esc_html(
                                $field->field_label
                            ); ?>
                        </th>
                    <?php endforeach; ?>

                    <th>Created</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($participants as $participant) : ?>
                    <?php
                    $meta_rows = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT meta_key, meta_value
                            FROM {$meta_table}
                            WHERE participant_id = %d",
                            $participant->id
                        ),
                        OBJECT_K
                    );

                    $delete_url = wp_nonce_url(
                        admin_url(
                            'admin.php?page=ecm-events'
                            . '&action=delete_participant'
                            . '&event_id='
                            . absint($event->id)
                            . '&participant_id='
                            . absint($participant->id)
                        ),
                        'ecm_delete_participant_'
                        . absint($participant->id)
                    );
                    ?>

                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                name="participant_ids[]"
                                value="<?php echo esc_attr(
                                    $participant->id
                                ); ?>"
                                class="ecm-participant-checkbox"
                                form="ecm-bulk-participant-form"
                            >
                        </td>

                        <?php foreach ($fields as $field) : ?>
                            <?php
                            if ($field->field_key === 'member_id') {
                                $value = $participant->member_id;
                            } else {
                                $value = isset(
                                    $meta_rows[$field->field_key]
                                )
                                    ? $meta_rows[
                                        $field->field_key
                                    ]->meta_value
                                    : '';
                            }
                            ?>

                            <td>
                                <?php echo esc_html($value); ?>
                            </td>
                        <?php endforeach; ?>

                        <td>
                            <?php echo esc_html(
                                $participant->created_at
                            ); ?>
                        </td>

                        <td>
                            <a
                                href="#"
                                class="ecm-edit-participant"
                                data-participant-id="<?php
                                echo esc_attr($participant->id);
                                ?>"
                                <?php foreach ($fields as $field) : ?>
                                    <?php
                                    if (
                                        $field->field_key ===
                                        'member_id'
                                    ) {
                                        $data_value =
                                            $participant->member_id;
                                    } else {
                                        $data_value = isset(
                                            $meta_rows[
                                                $field->field_key
                                            ]
                                        )
                                            ? $meta_rows[
                                                $field->field_key
                                            ]->meta_value
                                            : '';
                                    }
                                    ?>

                                    data-<?php
                                    echo esc_attr(
                                        $field->field_key
                                    );
                                    ?>="<?php
                                    echo esc_attr($data_value);
                                    ?>"
                                <?php endforeach; ?>
                            >
                                Edit
                            </a>

                            |

                            <a
                                href="<?php echo esc_url(
                                    $delete_url
                                ); ?>"
                                onclick="return confirm('Are you sure you want to delete this participant?');"
                                class="ecm-danger-link"
                            >
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /* -------------------------------------------------------------------------
     * Add/Edit Participant Modal
     * ---------------------------------------------------------------------- */

    /**
     * Render the shared Add/Edit Participant modal.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_add_participant_modal($event)
    {
        $fields = $this->get_event_fields($event->id);

        if (empty($fields)) {
            return;
        }
        ?>
        <div
            id="ecm-add-participant-modal"
            class="ecm-modal"
            style="display:none;"
        >
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2 id="ecm-participant-modal-title">
                        Add Participant
                    </h2>

                    <button
                        type="button"
                        class="ecm-modal-close"
                        aria-label="Close participant form"
                    >
                        &times;
                    </button>
                </div>

                <form
                    method="post"
                    id="ecm-participant-form"
                >
                    <?php
                    wp_nonce_field(
                        'ecm_add_participant',
                        'ecm_add_participant_nonce'
                    );

                    wp_nonce_field(
                        'ecm_update_participant',
                        'ecm_update_participant_nonce'
                    );
                    ?>

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?php echo esc_attr($event->id); ?>"
                    >

                    <input
                        type="hidden"
                        name="participant_id"
                        id="ecm_participant_id"
                        value=""
                    >

                    <input
                        type="hidden"
                        name="ecm_form_mode"
                        id="ecm_form_mode"
                        value="add"
                    >

                    <div class="ecm-modal-body">
                        <?php foreach ($fields as $field) : ?>
                            <p>
                                <label
                                    for="ecm_modal_field_<?php
                                    echo esc_attr(
                                        $field->field_key
                                    );
                                    ?>"
                                >
                                    <strong>
                                        <?php echo esc_html(
                                            $field->field_label
                                        ); ?>
                                    </strong>

                                    <?php if (
                                        (int) $field->is_required === 1
                                    ) : ?>
                                        <span class="description">
                                            (required)
                                        </span>
                                    <?php endif; ?>
                                </label>

                                <input
                                    type="<?php
                                    echo $field->field_type === 'number'
                                        ? 'number'
                                        : 'text';
                                    ?>"
                                    id="ecm_modal_field_<?php
                                    echo esc_attr(
                                        $field->field_key
                                    );
                                    ?>"
                                    data-field-key="<?php
                                    echo esc_attr(
                                        $field->field_key
                                    );
                                    ?>"
                                    name="participant_fields[<?php
                                    echo esc_attr(
                                        $field->field_key
                                    );
                                    ?>]"
                                    class="widefat ecm-participant-field"
                                    <?php
                                    echo (int) $field->is_required === 1
                                        ? 'required'
                                        : '';
                                    ?>
                                >
                            </p>
                        <?php endforeach; ?>
                    </div>

                    <div class="ecm-modal-footer">
                        <button
                            type="submit"
                            name="ecm_add_participant_submit"
                            id="ecm_add_participant_submit"
                            class="button button-primary"
                        >
                            Save Participant
                        </button>

                        <button
                            type="submit"
                            name="ecm_update_participant_submit"
                            id="ecm_update_participant_submit"
                            class="button button-primary"
                            style="display:none;"
                        >
                            Update Participant
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

    /* -------------------------------------------------------------------------
     * CSV Upload Modal
     * ---------------------------------------------------------------------- */

    /**
     * Render the participant CSV upload modal.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function render_csv_upload_modal($event)
    {
        $fields = $this->get_event_fields($event->id);

        if (empty($fields)) {
            return;
        }

        $headers = [];

        foreach ($fields as $field) {
            $headers[] = $field->field_key;
        }

        $csv_format = implode(',', $headers);
        ?>
        <div
            id="ecm-csv-upload-modal"
            class="ecm-modal"
            style="display:none;"
        >
            <div class="ecm-modal-content">
                <div class="ecm-modal-header">
                    <h2>Upload Participants CSV</h2>

                    <button
                        type="button"
                        class="ecm-modal-close"
                        aria-label="Close CSV upload form"
                    >
                        &times;
                    </button>
                </div>

                <form
                    method="post"
                    enctype="multipart/form-data"
                >
                    <?php
                    wp_nonce_field(
                        'ecm_import_participants_csv',
                        'ecm_import_csv_nonce'
                    );
                    ?>

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?php echo esc_attr($event->id); ?>"
                    >

                    <div class="ecm-modal-body">
                        <p>
                            <strong>Required CSV header:</strong>
                        </p>

                        <pre class="ecm-code-preview"><?php
                        echo esc_html($csv_format);
                        ?></pre>

                        <p class="description">
                            The first CSV row must match this header
                            exactly. Number fields must contain digits only.
                        </p>

                        <p>
                            <input
                                type="file"
                                name="participants_csv"
                                accept=".csv,text/csv"
                                required
                            >
                        </p>
                    </div>

                    <div class="ecm-modal-footer">
                        <button
                            type="submit"
                            name="ecm_import_csv_submit"
                            class="button button-primary"
                        >
                            Upload CSV
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