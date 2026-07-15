<?php

/**
 * ECM Event Participants
 *
 * Coordinates the Participants tab inside an event workspace.
 *
 * This trait is intentionally kept small. The detailed UI rendering,
 * participant CRUD actions, CSV import, and CSV export logic are
 * handled by their dedicated Participants traits.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

trait ECM_Event_Participants
{
    /**
     * Render the Participants tab for one event.
     *
     * @param object $event Event database record.
     *
     * @return void
     */
    private function tab_participants($event)
    {
        ?>
        <div class="ecm-tab-header">
            <div>
                <h2>Participants</h2>

                <p>
                    View, search, and manage participants for this event.
                </p>
            </div>

            <div class="ecm-tab-actions">
                <button
                    type="button"
                    class="button button-primary ecm-open-participant-modal"
                >
                    + Add Participant
                </button>

                <button
                    type="button"
                    class="button ecm-open-csv-modal"
                >
                    Upload CSV
                </button>

                <a
                    href="<?php echo esc_url(
                        wp_nonce_url(
                            admin_url(
                                'admin.php?page=ecm-events'
                                . '&action=download_sample_csv'
                                . '&event_id='
                                . absint($event->id)
                            ),
                            'ecm_download_sample_csv_'
                            . absint($event->id)
                        )
                    ); ?>"
                    class="button"
                >
                    Download Sample CSV
                </a>

                <a
                    href="<?php echo esc_url(
                        wp_nonce_url(
                            admin_url(
                                'admin.php?page=ecm-events'
                                . '&action=export_participants_csv'
                                . '&event_id='
                                . absint($event->id)
                            ),
                            'ecm_export_participants_csv_'
                            . absint($event->id)
                        )
                    ); ?>"
                    class="button"
                >
                    Export CSV
                </a>
            </div>
        </div>

        <?php $this->render_participant_notices(); ?>

        <?php $this->render_participant_toolbar($event); ?>

        <?php $this->render_participant_list_section($event); ?>

        <?php $this->render_add_participant_modal($event); ?>

        <?php $this->render_csv_upload_modal($event); ?>
        <?php
    }

    /**
     * Render Participants success and error notices.
     *
     * @return void
     */
    private function render_participant_notices()
    {
        if (isset($_GET['participant_added'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Participant added successfully.</strong>
                </p>
            </div>
            <?php
        }

        if (isset($_GET['participant_updated'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Participant updated successfully.</strong>
                </p>
            </div>
            <?php
        }

        if (isset($_GET['participant_deleted'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>Participant deleted successfully.</strong>
                </p>
            </div>
            <?php
        }

        if (isset($_GET['bulk_deleted'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>
                        Selected participants deleted successfully.
                    </strong>
                </p>
            </div>
            <?php
        }

        if (
            isset($_GET['bulk_no_selection']) ||
            isset($_GET['bulk_error'])
        ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>
                        Please select participants and choose a valid bulk action.
                    </strong>
                </p>
            </div>
            <?php
        }

        if (isset($_GET['bulk_empty_action'])) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>Please choose a bulk action.</strong>
                </p>
            </div>
            <?php
        }

        if (isset($_GET['csv_imported'])) {
            $inserted = isset($_GET['inserted'])
                ? absint($_GET['inserted'])
                : 0;

            $skipped = isset($_GET['skipped'])
                ? absint($_GET['skipped'])
                : 0;
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>CSV import completed.</strong>

                    Inserted:
                    <?php echo esc_html($inserted); ?>,

                    Skipped:
                    <?php echo esc_html($skipped); ?>.
                </p>
            </div>
            <?php
        }
    }
}