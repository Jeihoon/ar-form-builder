<?php
/**
 * Admin functionality for AR Form Builder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AR_Form_Builder_Admin {

    /*-------------------------------
      Properties
    -------------------------------*/
    private $option_name = 'amin_form_builder_forms';
    private $recaptcha_option = 'amin_form_builder_recaptcha';
    private $destination_option = 'amin_form_builder_destinations';

    /*-------------------------------
      Constructor
    -------------------------------*/
    public function __construct() {
        // Register admin menu pages.
        add_action( 'admin_menu', [ $this, 'create_admin_menu' ] );
        add_action( 'admin_menu', [ $this, 'add_settings_menu' ] );
        add_action( 'admin_menu', [ $this, 'add_destination_menu' ] );
        add_action( 'admin_menu', [ $this, 'add_entries_menu' ] );
        // Add new submenu for Submit Button Settings.
        add_action( 'admin_menu', [ $this, 'add_submit_button_settings_menu' ] );
        add_action( 'admin_post_delete_amin_entry', [ $this, 'delete_amin_entry' ] );
        
        // Enqueue admin scripts and styles.
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        
        // AJAX endpoints for saving, creating, and deleting forms.
        add_action( 'wp_ajax_save_amin_form', [ $this, 'save_amin_form' ] );
        add_action( 'wp_ajax_create_new_form', [ $this, 'create_new_form' ] );
        add_action( 'wp_ajax_delete_amin_form', [ $this, 'delete_amin_form' ] );
    }

    /*-------------------------------
      Admin Menu Registration
    -------------------------------*/
    public function create_admin_menu() {
        add_menu_page(
            'AR Form Builder',
            'AR Form Builder',
            'manage_options',
            'amin-form-builder',
            [ $this, 'admin_page' ],
            'dashicons-feedback',
            100
        );
    }

    public function add_settings_menu() {
        add_submenu_page(
            'amin-form-builder',
            'Google Settings',
            'Google Settings',
            'manage_options',
            'amin-form-builder-settings',
            [ $this, 'settings_page' ]
        );
    }

    public function add_destination_menu() {
        add_submenu_page(
            'amin-form-builder',
            'Forms Setting',
            'Forms Setting',
            'manage_options',
            'amin-form-builder-destinations',
            [ $this, 'destinations_page' ]
        );
    }

    public function add_entries_menu() {
        add_submenu_page(
            'amin-form-builder',
            'Form Entries',
            'Entries',
            'manage_options',
            'amin-form-builder-entries',
            [ $this, 'entries_page' ]
        );
    }
    
    // New submenu page for Submit Button Settings.
    public function add_submit_button_settings_menu() {
        add_submenu_page(
            'amin-form-builder',
            'Submit Button Settings',
            'Submit Button Settings',
            'manage_options',
            'amin-form-builder-submit-button',
            [ $this, 'submit_button_settings_page' ]
        );
    }
    
    public function submit_button_settings_page() {
        // Process form submission.
        if ( isset( $_POST['submit_button_settings'] ) && check_admin_referer( 'amin_form_builder_submit_button_settings', 'amin_form_builder_submit_button_nonce' ) ) {
            $bg = isset( $_POST['submit_button_bg'] ) ? sanitize_text_field( $_POST['submit_button_bg'] ) : '#0074aa';
            $hover = isset( $_POST['submit_button_hover'] ) ? sanitize_text_field( $_POST['submit_button_hover'] ) : '#005f8a';
            $settings = array( 'bg' => $bg, 'hover' => $hover );
            update_option( 'amin_form_builder_submit_button', json_encode( $settings ) );
            echo '<div class="notice notice-success is-dismissible"><p>Submit button settings saved successfully.</p></div>';
        }
        
        $settings = get_option( 'amin_form_builder_submit_button', '' );
        $settings = $settings ? json_decode( $settings, true ) : array( 'bg' => '#0074aa', 'hover' => '#005f8a' );
        ?>
        <div class="wrap">
            <h2>Submit Button Settings</h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'amin_form_builder_submit_button_settings', 'amin_form_builder_submit_button_nonce' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="submit_button_bg">Submit Button Background Color</label></th>
                        <td>
                            <input type="text" id="submit_button_bg" name="submit_button_bg" value="<?php echo esc_attr( $settings['bg'] ); ?>" class="regular-text color-picker" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="submit_button_hover">Submit Button Hover Color</label></th>
                        <td>
                            <input type="text" id="submit_button_hover" name="submit_button_hover" value="<?php echo esc_attr( $settings['hover'] ); ?>" class="regular-text color-picker" />
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Save Submit Button Settings', 'primary', 'submit_button_settings' ); ?>
            </form>
        </div>
        <?php
    }

    /*-------------------------------
      Enqueue Admin Scripts & Styles
    -------------------------------*/
    public function enqueue_admin_scripts( $hook ) {
        // Load Font Awesome from CDN.
        wp_enqueue_style( 'amin-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css' );
        // Enqueue admin CSS.
        wp_enqueue_style( 'amin-form-builder-admin-css', plugin_dir_url( __FILE__ ) . 'css/admin.css' );
        // Enqueue jQuery UI components.
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-droppable' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        // Enqueue custom admin JS.
        wp_enqueue_script( 'amin-form-builder-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', 
            [ 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable' ], 
            '1.6', 
            true 
        );
        // Pass saved forms data to admin JS.
        $saved_forms = get_option( $this->option_name, '[]' );
        $saved_forms = json_decode( $saved_forms, true );
        if ( ! is_array( $saved_forms ) ) {
            $saved_forms = [];
        }
        wp_localize_script( 'amin-form-builder-admin', 'AminFormBuilder', [
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'saved_forms' => $saved_forms
        ] );
    }

    /*-------------------------------
      Admin Pages
    -------------------------------*/
    public function admin_page() {
        $saved_forms = json_decode( get_option( $this->option_name, '[]' ), true );
        if ( ! is_array( $saved_forms ) ) {
            $saved_forms = [];
        }
        ?>
        <div class="wrap">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" class="formlogo" alt="AR Form Builder Logo">
            <!-- Create / Edit / Delete Panels -->
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div class="create-form" style="flex: 1;">
                    <h2>Create a New Form</h2>
                    <input type="text" id="new-form-name" placeholder="Enter form name" />
                    <button id="create-new-form" class="button button-primary">Create Form</button>
                </div>
                <div class="edit-form" style="flex: 1;">
                    <h2>Edit a Form</h2>
                    <select id="form-selector">
                        <option value="">Select a form to edit</option>
                        <?php foreach ( $saved_forms as $form_id => $fields ) : ?>
                            <option value="<?php echo esc_attr( $form_id ); ?>"><?php echo esc_html( $form_id ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="load-form" class="button">Edit Form</button>
                </div>
                <div class="delete-form" style="flex: 1;">
                    <h2>Delete a Form</h2>
                    <select id="delete-form-selector">
                        <option value="">Select a form to delete</option>
                        <?php foreach ( $saved_forms as $form_id => $fields ) : ?>
                            <option value="<?php echo esc_attr( $form_id ); ?>"><?php echo esc_html( $form_id ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="delete-form" class="button button-danger">Delete Form</button>
                </div>
            </div>
            <!-- Builder Container -->
            <div class="amin-form-builder-container" style="overflow: hidden; margin-top: 30px;">
                <!-- Field Items Box -->
                <div class="amin-form-fields-box">
                    <h2>Drag &amp; Drop Field Items</h2>
                    <div id="amin-form-fields" style="margin-top: 10px;">
                        <div class="field-item" data-field="name">Name</div>
                        <div class="field-item" data-field="email">Email</div>
                        <div class="field-item" data-field="phone">Phone</div>
                        <div class="field-item" data-field="text">Single Line Text</div>
                        <div class="field-item" data-field="text_area">Text Area</div>
                        <div class="field-item" data-field="drop_down_list">Drop Down List</div>
                        <div class="field-item" data-field="checkbox_group">Checkbox Group</div>
                        <div class="field-item" data-field="radio_group">Radio Group</div>
                        <div class="field-item" data-field="date">Date</div>
                        <div class="field-item" data-field="state">State</div>
                        <div class="field-item" data-field="zip">Zip Code</div>
                        <div class="field-item" data-field="address">Address</div>
                        <div class="field-item" data-field="file">File Upload</div>
                        <div class="field-item" data-field="number">Number</div>
                        <div class="field-item" data-field="html">HTML</div>
                        <div class="field-item" data-field="website">Website URL</div>
                        <div class="field-item" data-field="google_recaptcha">Google reCAPTCHA</div>
                    </div>
                </div>
                <!-- Form Layout Box -->
                <div class="amin-form-rows-box">
                    <h2 style="color: #0074aa;">Arrange Form Layout</h2>
                    <div id="amin-form-rows"></div>
                    <button id="add-new-row" class="button button-secondary">➕ Add New Row</button>
                    <button id="save-amin-form" class="button button-primary">Save Form</button>
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
        <!-- Modal for Field Options -->
        <div id="field-options-modal" >
            <h2>Edit Field Options</h2>
            <label for="field-label">Field Label:</label>
            <input type="text" id="field-label" style="width:100%; margin-bottom:10px;" />
            <br/>
            <label for="field-required">Required:</label>
            <input type="checkbox" id="field-required" style="margin-bottom:10px;" />
            <br/>
            <div id="options-container" style="display:none; margin-bottom:20px;">
                <label for="field-options">Options (comma separated):</label>
                <input type="text" id="field-options" style="width:100%; margin-bottom:10px;" />
                <br/>
                <label for="field-options-alignment" style="display: none;">Options Alignment:</label>
                <select id="field-options-alignment" style="width:100%; margin-bottom:10px; display: none;">
                    <option value="vertical">Vertical</option>
                    <option value="horizontal">Horizontal</option>
                </select>
            </div>
            <button id="save-field-options" class="button button-primary" style="margin-right:10px;">Save</button>
            <button id="cancel-field-options" class="button">Cancel</button>
        </div>
        <?php
    }

 public function settings_page() {
    // Process form submission.
    if ( isset( $_POST['submit'] ) && check_admin_referer( 'amin_form_builder_recaptcha_settings', 'amin_form_builder_nonce' ) ) {
        // Save reCAPTCHA settings.
        $site_key   = isset( $_POST['site_key'] ) ? sanitize_text_field( $_POST['site_key'] ) : '';
        $secret_key = isset( $_POST['secret_key'] ) ? sanitize_text_field( $_POST['secret_key'] ) : '';
        $settings = [
            'site_key'   => $site_key,
            'secret_key' => $secret_key
        ];
        update_option( $this->recaptcha_option, json_encode( $settings ) );
        
        // Save Submit Button settings.
        $submit_bg    = isset( $_POST['submit_bg'] ) ? sanitize_text_field( $_POST['submit_bg'] ) : '#0074aa';
        $submit_hover = isset( $_POST['submit_hover'] ) ? sanitize_text_field( $_POST['submit_hover'] ) : '#005f8a';
        $submit_settings = [
            'bg'    => $submit_bg,
            'hover' => $submit_hover
        ];
        update_option( 'amin_form_builder_submit_button', json_encode( $submit_settings ) );
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
    }
    
    // Get current settings.
    $settings = get_option( $this->recaptcha_option, '' );
    $settings = $settings ? json_decode( $settings, true ) : [ 'site_key' => '', 'secret_key' => '' ];
    
    $settings_submit = get_option( 'amin_form_builder_submit_button', '' );
    $settings_submit = $settings_submit ? json_decode( $settings_submit, true ) : [ 'bg' => '#0074aa', 'hover' => '#005f8a' ];
    ?>
    <div class="wrap" style="padding: 20px;">
        <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" class="formlogo" alt="AR Form Builder Logo">
        <form method="post" action="">
            <?php wp_nonce_field( 'amin_form_builder_recaptcha_settings', 'amin_form_builder_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="site_key">Google reCAPTCHA Site Key</label></th>
                    <td>
                        <input type="text" id="site_key" name="site_key" value="<?php echo esc_attr( $settings['site_key'] ); ?>" class="regular-text">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="secret_key">Google reCAPTCHA Secret Key</label></th>
                    <td>
                        <input type="text" id="secret_key" name="secret_key" value="<?php echo esc_attr( $settings['secret_key'] ); ?>" class="regular-text">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="submit_bg">Submit Button Background Color</label></th>
                    <td>
                        <input type="text" id="submit_bg" name="submit_bg" value="<?php echo esc_attr( $settings_submit['bg'] ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="submit_hover">Submit Button Hover Color</label></th>
                    <td>
                        <input type="text" id="submit_hover" name="submit_hover" value="<?php echo esc_attr( $settings_submit['hover'] ); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


    public function destinations_page() {
        if ( isset( $_POST['submit_destinations'] ) && check_admin_referer( 'amin_form_builder_destinations', 'amin_form_builder_dest_nonce' ) ) {
            $destinations = get_option( $this->destination_option, '' );
            $destinations = $destinations ? json_decode( $destinations, true ) : [];
            if ( isset( $_POST['destination'] ) && is_array( $_POST['destination'] ) ) {
                foreach ( $_POST['destination'] as $form_id => $settings ) {
                    $destinations[ $form_id ] = [
                        'emails'      => isset( $settings['emails'] ) ? trim( $settings['emails'] ) : '',
                        'subject'     => isset( $settings['subject'] ) ? trim( $settings['subject'] ) : '',
                        'from_email'  => isset( $settings['from_email'] ) ? trim( $settings['from_email'] ) : '',
                        'sender_name' => isset( $settings['sender_name'] ) ? trim( $settings['sender_name'] ) : ''
                    ];
                }
            }
            update_option( $this->destination_option, json_encode( $destinations ) );
            echo '<div class="notice notice-success is-dismissible"><p>Form destination settings saved successfully.</p></div>';
        }
        $saved_forms = json_decode( get_option( $this->option_name, '[]' ), true );
        if ( ! is_array( $saved_forms ) ) {
            $saved_forms = [];
        }
        $destinations = get_option( $this->destination_option, '' );
        $destinations = $destinations ? json_decode( $destinations, true ) : [];
        ?>
        <div class="wrap">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" class="formlogo" alt="AR Form Builder Logo">
            <form method="post" action="">
                <?php wp_nonce_field( 'amin_form_builder_destinations', 'amin_form_builder_dest_nonce' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Form ID / Shortcode</th>
                        <th scope="row">Destination Emails<br/>(comma separated)</th>
                        <th scope="row">Email Subject</th>
                        <th scope="row">Sender Email</th>
                        <th scope="row">Sender Name<br/>(optional)</th>
                    </tr>
                    <?php if ( ! empty( $saved_forms ) ) : ?>
                        <?php foreach ( $saved_forms as $form_id => $fields ) : ?>
                            <tr style="border-bottom:1px solid #ccc;">
                                <td style="padding:8px;">
                                    <?php echo esc_html( $form_id ); ?><br/>
                                    <small>[amin_form id="<?php echo esc_attr( $form_id ); ?>"]</small>
                                </td>
                                <td style="padding:8px;">
                                    <input type="text" name="destination[<?php echo esc_attr( $form_id ); ?>][emails]" value="<?php echo isset( $destinations[$form_id]['emails'] ) ? esc_attr( $destinations[$form_id]['emails'] ) : ''; ?>" style="width:100%;" placeholder="Destination Emails" />
                                </td>
                                <td style="padding:8px;">
                                    <input type="text" name="destination[<?php echo esc_attr( $form_id ); ?>][subject]" value="<?php echo isset( $destinations[$form_id]['subject'] ) ? esc_attr( $destinations[$form_id]['subject'] ) : ''; ?>" style="width:100%;" placeholder="Custom Email Subject" />
                                </td>
                                <td style="padding:8px;">
                                    <input type="text" name="destination[<?php echo esc_attr( $form_id ); ?>][from_email]" value="<?php echo isset( $destinations[$form_id]['from_email'] ) ? esc_attr( $destinations[$form_id]['from_email'] ) : ''; ?>" style="width:100%;" placeholder="Sender Email" />
                                </td>
                                <td style="padding:8px;">
                                    <input type="text" name="destination[<?php echo esc_attr( $form_id ); ?>][sender_name]" value="<?php echo isset( $destinations[$form_id]['sender_name'] ) ? esc_attr( $destinations[$form_id]['sender_name'] ) : ''; ?>" style="width:100%;" placeholder="Sender Name" />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding:8px;">No forms available.</td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php submit_button( 'Save Destination Settings', 'primary', 'submit_destinations' ); ?>
            </form>
        </div>
        <?php
    }

    public function entries_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'amin_form_entries';
        if ( isset( $_GET['entry_id'] ) && ! empty( $_GET['entry_id'] ) ) {
            $entry_id = intval( $_GET['entry_id'] );
            $entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $entry_id ) );
            if ( $entry ) {
                $submission_data = json_decode( $entry->submission, true );
                $saved_forms = json_decode( get_option( $this->option_name, '[]' ), true );
                $labels = [];
                if ( isset( $saved_forms[$entry->form_id] ) ) {
                    foreach ( $saved_forms[$entry->form_id] as $field ) {
                        $labels[$field['field']] = ( isset( $field['title'] ) && ! empty( $field['title'] ) )
                            ? $field['title'] : ucfirst( $field['field'] );
                    }
                }
                ?>
                <div class="wrap">
                    <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" class="formlogo" alt="AR Form Builder Logo">
                    <h1>Entry Details (ID: <?php echo esc_html( $entry->id ); ?>)</h1>
                    <table class="widefat fixed" cellspacing="0" style="margin-bottom:20px;">
                        <tbody>
                            <tr>
                                <th>Form ID</th>
                                <td><?php echo esc_html( $entry->form_id ); ?></td>
                            </tr>
                            <tr>
                                <th>Submission Date</th>
                                <td><?php echo esc_html( $entry->submission_date ); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <h2>Submission Data</h2>
                    <table class="widefat fixed" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $submission_data = maybe_unserialize( $entry->submission );
                            // Ensure $submission_data is an array.
                            if ( ! is_array( $submission_data ) ) {
                                $submission_data = array();
                            }
                            
                            foreach ( $submission_data as $key => $value ) {
                                // If value is an array (for example, from a checkbox group), convert it to a comma-separated string.
                                if ( is_array( $value ) ) {
                                    $value = implode(', ', $value);
                                }
                                
                                $field_key = ( strpos( $key, 'amin_' ) === 0 ) ? substr( $key, 5 ) : $key;
                                $field_label = isset( $labels[$field_key] ) ? $labels[$field_key] : ucfirst( str_replace('_', ' ', $field_key) );
                                
                                echo '<tr>';
                                echo '<td>' . esc_html( $field_label ) . '</td>';
                                echo '<td>' . esc_html( $value ) . '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <p>
                        <a href="<?php echo admin_url( 'admin.php?page=amin-form-builder-entries' ); ?>">&larr; Back to Summary</a>
                        | <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=delete_amin_entry&entry_id=' . intval( $entry->id ) ), 'delete_entry_nonce' ); ?>" onclick="return confirm('Are you sure you want to delete this entry?');">Delete Entry</a>
                    </p>
                </div>
                <?php
            } else {
                echo '<div class="wrap"><h1>Entry Not Found</h1></div>';
            }
            return;
        }
        if ( isset( $_GET['form_id'] ) && ! empty( $_GET['form_id'] ) ) {
            $form_id = sanitize_text_field( $_GET['form_id'] );
            $entries = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE form_id = %s ORDER BY submission_date DESC", $form_id ) );
            ?>
            <div class="wrap">
                <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" class="formlogo" alt="AR Form Builder Logo">
                <h1>Entries for Form: <?php echo esc_html( $form_id ); ?></h1>
                <?php if ( ! empty( $entries ) ) : ?>
                    <table class="widefat fixed" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Submission Date</th>
                                <th>View Details</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $entries as $entry ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $entry->id ); ?></td>
                                    <td><?php echo esc_html( $entry->submission_date ); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url( 'admin.php?page=amin-form-builder-entries&entry_id=' . intval( $entry->id ) ); ?>">
                                            View
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=delete_amin_entry&entry_id=' . intval( $entry->id ) . '&form_id=' . urlencode( $form_id ) ), 'delete_entry_nonce' ); ?>" onclick="return confirm('Are you sure you want to delete this entry?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>
                        <a href="<?php echo admin_url( 'admin.php?page=amin-form-builder-entries' ); ?>">
                            &larr; Back to Forms Summary
                        </a>
                    </p>
                <?php else : ?>
                    <p>No entries found for this form.</p>
                    <p>
                        <a href="<?php echo admin_url( 'admin.php?page=amin-form-builder-entries' ); ?>">
                            &larr; Back to Forms Summary
                        </a>
                    </p>
                <?php endif; ?>
            </div>
            <?php
            return;
        }
        $results = $wpdb->get_results( "SELECT form_id, COUNT(*) as total FROM $table_name GROUP BY form_id ORDER BY form_id ASC" );
        ?>
        <div class="wrap">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" class="formlogo" alt="AR Form Builder Logo">
            <h1>Forms Summary</h1>
            <?php if ( ! empty( $results ) ) : ?>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Form ID</th>
                            <th>Total Entries</th>
                            <th>View Entries</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $results as $row ) : ?>
                            <tr>
                                <td><?php echo esc_html( $row->form_id ); ?></td>
                                <td><?php echo esc_html( $row->total ); ?></td>
                                <td>
                                    <a href="<?php echo admin_url( 'admin.php?page=amin-form-builder-entries&form_id=' . urlencode( $row->form_id ) ); ?>">
                                        View Entries
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No entries found.</p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    
/**
 * Deletes a form entry.
 */
public function delete_amin_entry() {
    // Only allow users with the appropriate capability.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized request.' );
    }
    
    // Verify nonce.
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_entry_nonce' ) ) {
        wp_die( 'Invalid nonce.' );
    }
    
    // Check that the entry ID is provided.
    if ( ! isset( $_GET['entry_id'] ) || empty( $_GET['entry_id'] ) ) {
        wp_die( 'Missing entry ID.' );
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'amin_form_entries';
    $entry_id = intval( $_GET['entry_id'] );
    
    // Delete the entry.
    $deleted = $wpdb->delete( $table_name, array( 'id' => $entry_id ), array( '%d' ) );
    if ( false === $deleted ) {
        wp_die( 'There was an error deleting the entry. Please try again.' );
    }
    
    // Redirect back to the entries page.
    $redirect_url = admin_url( 'admin.php?page=amin-form-builder-entries' );
    if ( isset( $_GET['form_id'] ) && ! empty( $_GET['form_id'] ) ) {
        $redirect_url = add_query_arg( 'form_id', sanitize_text_field( $_GET['form_id'] ), $redirect_url );
    }
    wp_redirect( $redirect_url );
    exit;
}
    /*-------------------------------
      AJAX Functions for Form Operations
    -------------------------------*/
public function save_amin_form() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized request.' ] );
    }
    if ( ! isset( $_POST['form_id'] ) || empty( trim( $_POST['form_id'] ) ) ) {
        wp_send_json_error( [ 'message' => 'Missing form ID.' ] );
    }
    $form_id = sanitize_text_field( $_POST['form_id'] );
    $fields = ( isset( $_POST['fields'] ) && ! empty( $_POST['fields'] ) )
        ? json_decode( wp_unslash( $_POST['fields'] ), true )
        : [];
    if ( ! is_array( $fields ) ) {
        wp_send_json_error( [ 'message' => 'Invalid fields data.' ] );
    }
    $saved_forms = json_decode( get_option( $this->option_name, '[]' ), true );
    if ( ! is_array( $saved_forms ) ) {
        $saved_forms = [];
    }
    $saved_forms[$form_id] = $fields;
    $updated = update_option( $this->option_name, json_encode( $saved_forms ) );
    if ( false === $updated ) {
        delete_option( $this->option_name );
        $updated = add_option( $this->option_name, json_encode( $saved_forms ) );
    }
    if ( $updated ) {
        wp_send_json_success( [ 'message' => "Form '$form_id' saved successfully." ] );
    } else {
        wp_send_json_error( [ 'message' => "Failed to save form '$form_id'." ] );
    }
}

public function delete_amin_form() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized request.' ] );
    }
    if ( ! isset( $_POST['form_id'] ) || empty( trim( $_POST['form_id'] ) ) ) {
        wp_send_json_error( [ 'message' => 'Missing form ID.' ] );
    }
    $form_id = sanitize_text_field( $_POST['form_id'] );
    $saved_forms = json_decode( get_option( $this->option_name, '[]' ), true );
    if ( ! is_array( $saved_forms ) || ! isset( $saved_forms[$form_id] ) ) {
        wp_send_json_error( [ 'message' => 'Form not found.' ] );
    }
    unset( $saved_forms[$form_id] );
    update_option( $this->option_name, json_encode( $saved_forms ) );
    wp_send_json_success( [
        'message' => "Form '$form_id' deleted successfully.",
        'form_id' => $form_id
    ] );
}

public function create_new_form() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized request.' ] );
    }
    if ( ! isset( $_POST['form_name'] ) || empty( trim( $_POST['form_name'] ) ) ) {
        wp_send_json_error( [ 'message' => 'Missing form name.' ] );
    }
    $form_name = sanitize_text_field( $_POST['form_name'] );
    $form_id = strtolower( str_replace( ' ', '_', $form_name ) );
    $saved_forms = json_decode( get_option( $this->option_name, '[]' ), true );
    if ( ! is_array( $saved_forms ) ) {
        $saved_forms = [];
    }
    if ( isset( $saved_forms[$form_id] ) ) {
        wp_send_json_error( [ 'message' => 'Form already exists.' ] );
    }
    $saved_forms[$form_id] = [
        [ 'field' => 'name',  'column' => 1 ],
        [ 'field' => 'email', 'column' => 2 ],
        [ 'field' => 'phone', 'column' => 3 ]
    ];
    update_option( $this->option_name, json_encode( $saved_forms ) );
    wp_send_json_success( [
        'message' => "Form '$form_name' created successfully.",
        'form_id' => $form_id,
        'fields'  => $saved_forms[$form_id]
    ] );
}
}
