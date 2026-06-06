<?php

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Admin {

    public function init() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu() {

        add_menu_page(
            'Event Certificate Manager',
            'ECM',
            'manage_options',
            'ecm-dashboard',
            [$this, 'dashboard_page'],
            'dashicons-awards',
            25
        );

    }

    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1>Event Certificate Manager</h1>
            <p>Plugin foundation loaded successfully.</p>
        </div>
        <?php
    }
}