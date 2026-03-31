<?php
/**
 * Front-End functionality for AR Form Builder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AR_Form_Builder_Frontend {

    private $option_name = 'amin_form_builder_forms';
    private $recaptcha_option = 'amin_form_builder_recaptcha';

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
        add_shortcode( 'amin_form', [ $this, 'render_form_shortcode' ] );
        // Add hooks to process form submission for both logged-in and non-logged-in users.
        add_action( 'admin_post_process_amin_form', [ $this, 'process_form_submission' ] );
        add_action( 'admin_post_nopriv_process_amin_form', [ $this, 'process_form_submission' ] );
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style( 'amin-form-builder-frontend-css', plugin_dir_url( __FILE__ ) . 'css/frontend.css' );
        wp_enqueue_script( 'amin-form-builder-frontend-js', plugin_dir_url( __FILE__ ) . 'js/frontend.js', [ 'jquery' ], '1.0', true );
        wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true );
    }

    public function render_form_shortcode( $atts ) {
        $atts = shortcode_atts( [ 'id' => '' ], $atts );
        if ( empty( $atts['id'] ) ) {
            return '<p>Error: No form ID provided.</p>';
        }
        $saved_forms = json_decode( get_option( $this->option_name, '[]' ), true );
        if ( ! isset( $saved_forms[$atts['id']] ) ) {
            return '<p>No form found.</p>';
        }
        $fields_array = $saved_forms[$atts['id']];
        ob_start();
        
        // Display success message if submission was successful.
        if ( isset( $_GET['form_submission'] ) && $_GET['form_submission'] === 'success' ) {
            echo '<p class="amin-success-message" style="color: green; font-weight: bold;">Thank you! Your submission has been received.</p>';
        }
        ?>
        <div class="amin-frontend-form">
            <form class="amin-frontend-form-container" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="process_amin_form">
                <input type="hidden" name="form_id" value="<?php echo esc_attr( $atts['id'] ); ?>">
                <?php 
                // Organize fields into rows based on 'row' and 'column'
                $rows = [];
                foreach ( $fields_array as $field ) {
                    $row = isset( $field['row'] ) ? $field['row'] : 1;
                    $column = isset( $field['column'] ) ? $field['column'] : 1;
                    $field['row'] = $row;
                    $field['column'] = $column;
                    $rows[$row][] = $field;
                }
                foreach ( $rows as $row_number => $fields_in_row ) {
                    echo '<div class="amin-frontend-columns" data-row="' . esc_attr( $row_number ) . '">';
                    $filled_columns = array_unique( array_column( $fields_in_row, 'column' ) );
                    $total_columns = count( $filled_columns ) > 0 ? count( $filled_columns ) : 1;
                    for ( $i = 1; $i <= 3; $i++ ) {
                        if ( ! in_array( $i, $filled_columns ) ) {
                            continue;
                        }
                        $column_width = 100 / $total_columns;
                        echo '<div class="amin-frontend-column" data-column="' . esc_attr( $i ) . '" style="width: ' . esc_attr( $column_width ) . '%;">';
                        foreach ( $fields_in_row as $field ) {
                            if ( $field['column'] == $i ) {
                                $is_required = ( isset( $field['required'] ) && ( $field['required'] === "true" || $field['required'] === true ) );
                                $req_mark = $is_required ? "<span style='color:red; margin-left:3px;'>*</span>" : "";
                                // Render field based on type.
                                if ( $field['field'] === 'google_recaptcha' ) {
                                    $settings = get_option( $this->recaptcha_option, '' );
                                    $settings = $settings ? json_decode( $settings, true ) : [];
                                    if ( isset( $settings['site_key'] ) && ! empty( $settings['site_key'] ) ) {
                                        echo '<div class="amin-field"><div class="g-recaptcha" data-sitekey="' . esc_attr( $settings['site_key'] ) . '"></div></div>';
                                    } else {
                                        echo '<p>Google reCAPTCHA is not configured.</p>';
                                    }
                                } elseif ( $field['field'] === 'checkbox_group' ) {
                                    $field_title = !empty($field['title']) ? $field['title'] : 'Checkbox Group';
                                    $alignment = isset( $field['optionsAlignment'] ) ? $field['optionsAlignment'] : 'vertical';
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_title ) . $req_mark . ':</label>';
                                    if ( isset( $field['options'] ) && ! empty( $field['options'] ) ) {
                                        $options = is_array( $field['options'] ) ? $field['options'] : explode( ',', $field['options'] );
                                        echo '<div class="options ' . esc_attr( $alignment ) . ($is_required ? ' required-group' : '') . '">';
                                        foreach ( $options as $option ) {
                                            echo '<label class="checkbox-label"><input type="checkbox" name="amin_checkbox_group[]" value="' . esc_attr( trim( $option ) ) . '"> ' . esc_html( trim( $option ) ) . '</label> ';
                                        }
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                } elseif ( $field['field'] === 'radio_group' ) {
                                    $field_title = !empty($field['title']) ? $field['title'] : 'Radio Group';
                                    $alignment = isset( $field['optionsAlignment'] ) ? $field['optionsAlignment'] : 'vertical';
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_title ) . $req_mark . ':</label>';
                                    if ( isset( $field['options'] ) && ! empty( $field['options'] ) ) {
                                        $options = is_array( $field['options'] ) ? $field['options'] : explode( ',', $field['options'] );
                                        echo '<div class="options ' . esc_attr( $alignment ) . '">';
                                        $first = true;
                                        foreach ( $options as $option ) {
                                            $req_attr = ($is_required && $first) ? ' required' : '';
                                            $first = false;
                                            echo '<label class="radio-label"><input type="radio" name="amin_radio_group" value="' . esc_attr( trim( $option ) ) . '" ' . $req_attr . '> ' . esc_html( trim( $option ) ) . '</label> ';
                                        }
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                } elseif ( $field['field'] === 'text_area' ) {
                                    $field_title = !empty($field['title']) ? $field['title'] : 'Text Area';
                                    echo '<div class="amin-field amin-text-area">';
                                    echo '<label class="field-title">' . esc_html( $field_title ) . $req_mark . ':</label>';
                                    echo '<textarea name="amin_text_area" ' . ($is_required ? 'required' : '') . '></textarea>';
                                    echo '</div>';
                                } elseif ( $field['field'] === 'drop_down_list' ) {
                                    $field_title = !empty($field['title']) ? $field['title'] : 'Drop Down List';
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_title ) . $req_mark . ':</label>';
                                    echo '<div class="dropdown-wrapper" style="position:relative;">';
                                    echo '<select name="amin_drop_down_list" ' . ($is_required ? 'required' : '') . '>';
                                    echo '<option value="">Select</option>';
                                    if ( isset( $field['options'] ) && ! empty( $field['options'] ) ) {
                                        $options = is_array( $field['options'] ) ? $field['options'] : explode( ',', $field['options'] );
                                        foreach ( $options as $option ) {
                                            echo '<option value="' . esc_attr( trim( $option ) ) . '">' . esc_html( trim( $option ) ) . '</option>';
                                        }
                                    }
                                    echo '</select>';
                                    echo '<span class="dropdown-icon"><i class="fas fa-chevron-down"></i></span>';
                                    echo '</div>';
                                    echo '</div>';
                                } elseif ( $field['field'] === 'date' ) {
                                    $field_title = !empty($field['title']) ? $field['title'] : 'Date';
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_title ) . $req_mark . ':</label>';
                                    echo '<input type="date" name="amin_date" ' . ($is_required ? 'required' : '') . ' />';
                                    echo '</div>';
                                } elseif ( $field['field'] === 'phone' ) {
                                    $field_title = !empty($field['title']) ? $field['title'] : 'Phone';
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_title ) . $req_mark . ':</label>';
                                    echo '<input type="tel" name="amin_phone" placeholder="(123) 456-7890" ' . ($is_required ? 'required' : '') . ' />';
                                    echo '</div>';
                                } elseif ( $field['field'] === 'website' ) {
                                    $field_title = !empty($field['title']) ? $field['title'] : 'Website URL';
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_title ) . $req_mark . ':</label>';
                                    echo '<input type="url" name="amin_website" placeholder="https://example.com" ' . ($is_required ? 'required' : '') . ' />';
                                    echo '</div>';
                                } elseif ( $field['field'] === 'state' ) {
                                    $field_title = !empty($field['title']) ? $field['title'] : 'State';
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_title ) . $req_mark . ':</label>';
                                    echo '<div class="dropdown-wrapper" style="position:relative;">';
                                    echo '<select name="amin_state" ' . ($is_required ? 'required' : '') . '>';
                                    echo '<option value="">Select</option>';
                                    $states = [
                                        'AL' => 'Alabama',
                                        'AK' => 'Alaska',
                                        'AZ' => 'Arizona',
                                        'AR' => 'Arkansas',
                                        'CA' => 'California',
                                        'CO' => 'Colorado',
                                        'CT' => 'Connecticut',
                                        'DE' => 'Delaware',
                                        'FL' => 'Florida',
                                        'GA' => 'Georgia',
                                        'HI' => 'Hawaii',
                                        'ID' => 'Idaho',
                                        'IL' => 'Illinois',
                                        'IN' => 'Indiana',
                                        'IA' => 'Iowa',
                                        'KS' => 'Kansas',
                                        'KY' => 'Kentucky',
                                        'LA' => 'Louisiana',
                                        'ME' => 'Maine',
                                        'MD' => 'Maryland',
                                        'MA' => 'Massachusetts',
                                        'MI' => 'Michigan',
                                        'MN' => 'Minnesota',
                                        'MS' => 'Mississippi',
                                        'MO' => 'Missouri',
                                        'MT' => 'Montana',
                                        'NE' => 'Nebraska',
                                        'NV' => 'Nevada',
                                        'NH' => 'New Hampshire',
                                        'NJ' => 'New Jersey',
                                        'NM' => 'New Mexico',
                                        'NY' => 'New York',
                                        'NC' => 'North Carolina',
                                        'ND' => 'North Dakota',
                                        'OH' => 'Ohio',
                                        'OK' => 'Oklahoma',
                                        'OR' => 'Oregon',
                                        'PA' => 'Pennsylvania',
                                        'RI' => 'Rhode Island',
                                        'SC' => 'South Carolina',
                                        'SD' => 'South Dakota',
                                        'TN' => 'Tennessee',
                                        'TX' => 'Texas',
                                        'UT' => 'Utah',
                                        'VT' => 'Vermont',
                                        'VA' => 'Virginia',
                                        'WA' => 'Washington',
                                        'WV' => 'West Virginia',
                                        'WI' => 'Wisconsin',
                                        'WY' => 'Wyoming'
                                    ];
                                    foreach ( $states as $abbr => $state_name ) {
                                        echo '<option value="' . esc_attr( $abbr ) . '">' . esc_html( $state_name ) . '</option>';
                                    }
                                    echo '</select>';
                                    echo '<span class="dropdown-icon"><i class="fas fa-chevron-down"></i></span>';
                                    echo '</div>';
                                    echo '</div>';
                                } elseif ( $field['field'] === 'zip' ) {
                                    $field_label = !empty($field['title']) ? $field['title'] : 'Zip Code';
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_label ) . $req_mark . ':</label>';
                                    echo '<input type="text" name="amin_zip" placeholder="Zip Code" pattern="[0-9]*" ' . ($is_required ? 'required' : '') . ' />';
                                    echo '</div>';
} elseif ( $field['field'] === 'file' ) {
    $field_title = !empty($field['title']) ? $field['title'] : 'File Upload';
    echo '<div class="amin-field file-upload-field">';
    echo '<label class="field-title">' . esc_html($field_title) . $req_mark . ':</label>';

    echo '<div class="custom-file-wrapper">';
    echo '<label class="custom-file-button">';
    echo 'Choose Files';
    echo '<input type="file" name="amin_file[]" class="custom-file-upload" ' . ($is_required ? 'required' : '') . ' multiple />';
    echo '</label>';
    echo '</div>';

    echo '<span class="selected-files"></span>';
    echo '</div>';
}


 elseif ( $field['field'] === 'image' ) {
                                    $field_title = !empty($field['title']) ? $field['title'] : 'Image';
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_title ) . $req_mark . ':</label>';
                                    // File input for images.
                                    echo '<div class="custom-file-wrapper">';
echo '<label class="custom-file-button">';
echo 'Choose Image';
echo '<input type="file" name="amin_image" accept="image/*" class="custom-file-upload" ' . ($is_required ? 'required' : '') . ' />';
echo '</label>';
echo '</div>';
echo '<span class="selected-files"></span>';

                                    echo '</div>';
                                } elseif ( $field['field'] === 'number' ) {
                                    $field_title = !empty($field['title']) ? $field['title'] : 'Number';
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_title ) . $req_mark . ':</label>';
                                    echo '<input type="number" name="amin_number" ' . ($is_required ? 'required' : '') . ' />';
                                    echo '</div>';
                                } elseif ( $field['field'] === 'html' ) {
                                    // Display the HTML field content as rendered HTML in the form preview.
                                    echo '<div class="amin-field amin-html-preview">' . $field['title'] . '</div>';
                                    // Include a hidden input so the raw HTML value is submitted.
                                    echo '<input type="hidden" name="amin_html" value="' . $field['title'] . '">';
                                } else {
                                    // Generic text field.
                                    $field_label = !empty($field['title']) ? $field['title'] : ucfirst( $field['field'] );
                                    echo '<div class="amin-field">';
                                    echo '<label class="field-title">' . esc_html( $field_label ) . $req_mark . ':</label> ';
                                    if ( $field['field'] === 'email' ) {
                                        echo '<input type="email" name="amin_email" placeholder="example@example.com" ' . ($is_required ? 'required' : '') . ' />';
                                    } elseif ( $field['field'] === 'phone' ) {
                                        echo '<input type="tel" name="amin_phone" placeholder="(123) 456-7890" ' . ($is_required ? 'required' : '') . ' />';
                                    } else {
                                        echo '<input type="text" name="amin_' . esc_attr( $field['field'] ) . '" ' . ($is_required ? 'required' : '') . ' />';
                                    }
                                    echo '</div>';
                                }
                            }
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                }
                ?>
                <p>
                    <input type="submit" class="amin-submit-button" value="Submit" />
                </p>
                <script>
                // Custom validation for checkbox groups.
                document.querySelector('.amin-frontend-form-container').addEventListener('submit', function(e) {
                    var requiredGroups = document.querySelectorAll('.required-group');
                    for (var j = 0; j < requiredGroups.length; j++) {
                        var checkboxes = requiredGroups[j].querySelectorAll('input[type="checkbox"][name="amin_checkbox_group[]"]');
                        var atLeastOne = false;
                        for (var i = 0; i < checkboxes.length; i++) {
                            if (checkboxes[i].checked) {
                                atLeastOne = true;
                                break;
                            }
                        }
                        if (!atLeastOne) {
                            e.preventDefault();
                            alert('Please select at least one option in the checkbox group.');
                            return;
                        }
                    }
                });
                </script>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /*** Process the front-end form submission. */
    public function process_form_submission() {
        global $wpdb;

        // Ensure the form_id is present.
        if ( empty( $_POST['form_id'] ) ) {
            wp_die( 'Form ID is missing.' );
        }
        $form_id = sanitize_text_field( $_POST['form_id'] );

        // --- Verify reCAPTCHA ---
        $recaptcha_settings = get_option( $this->recaptcha_option, '' );
        $recaptcha_settings = $recaptcha_settings ? json_decode( $recaptcha_settings, true ) : array();
        $secret_key = isset( $recaptcha_settings['secret_key'] ) ? $recaptcha_settings['secret_key'] : '';

        // Get the reCAPTCHA response.
        $recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '';
        if ( empty( $recaptcha_response ) ) {
            $referer = wp_get_referer() ? wp_get_referer() : home_url();
            $redirect_url = add_query_arg( 'form_submission', 'recaptcha_error', $referer );
            wp_redirect( $redirect_url );
            exit;
        }

        // Verify the reCAPTCHA response.
        $response = wp_remote_post( "https://www.google.com/recaptcha/api/siteverify", array(
            'body' => array(
                'secret'   => $secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            )
        ) );
        if ( is_wp_error( $response ) ) {
            $referer = wp_get_referer() ? wp_get_referer() : home_url();
            $redirect_url = add_query_arg( 'form_submission', 'recaptcha_error', $referer );
            wp_redirect( $redirect_url );
            exit;
        }
        $response_body = wp_remote_retrieve_body( $response );
        $result = json_decode( $response_body, true );
        if ( ! isset( $result['success'] ) || $result['success'] !== true ) {
            $referer = wp_get_referer() ? wp_get_referer() : home_url();
            $redirect_url = add_query_arg( 'form_submission', 'recaptcha_error', $referer );
            wp_redirect( $redirect_url );
            exit;
        }
        // --- End reCAPTCHA verification ---

        // Get submission data (all POST values except the action and form_id)
        $submission = $_POST;
        unset( $submission['action'], $submission['form_id'] );

        // Remove reCAPTCHA fields so they don't appear in entries or emails.
        if ( isset( $submission['g-recaptcha-response'] ) ) {
            unset( $submission['g-recaptcha-response'] );
        }
        if ( isset( $submission['amin_google_recaptcha'] ) ) {
            unset( $submission['amin_google_recaptcha'] );
        }

        // Retrieve the saved form configuration to map field keys to custom titles.
        $saved_forms = json_decode( get_option( $this->option_name, '[]' ), true );
        $labels = [];
        if ( isset( $saved_forms[$form_id] ) && is_array( $saved_forms[$form_id] ) ) {
            foreach ( $saved_forms[$form_id] as $field ) {
                // Assuming input names use the prefix "amin_"
                $submission_key = 'amin_' . $field['field'];
                $labels[$submission_key] = ( isset( $field['title'] ) && ! empty( $field['title'] ) )
                    ? $field['title']
                    : ucfirst( $field['field'] );
            }
        }

        // Build the email message using custom labels.
        $message  = "You have a new submission for form {$form_id}:<br /><br />";
        foreach ( $submission as $key => $value ) {
            // If $value is an array (e.g., checkbox group), implode it.
            if ( is_array( $value ) ) {
                $value = implode( ', ', $value );
            }
            if ( $key === 'amin_html' ) {
                // Define allowed HTML tags, including <img>.
                $allowed_tags = array(
                    'h1'    => array( 'style' => true, 'class' => true, 'id' => true ),
                    'h2'    => array( 'style' => true, 'class' => true, 'id' => true ),
                    'h3'    => array( 'style' => true, 'class' => true, 'id' => true ),
                    'h4'    => array( 'style' => true, 'class' => true, 'id' => true ),
                    'h5'    => array( 'style' => true, 'class' => true, 'id' => true ),
                    'h6'    => array( 'style' => true, 'class' => true, 'id' => true ),
                    'p'     => array( 'style' => true, 'class' => true, 'id' => true ),
                    'br'    => array(),
                    'strong'=> array( 'style' => true ),
                    'em'    => array( 'style' => true ),
                    'span'  => array( 'style' => true, 'class' => true ),
                    'img'   => array( 'src' => true, 'alt' => true, 'style' => true, 'class' => true )
                    // Add other tags if needed.
                );
                $message .= "HTML Content:<br />" . wp_kses( $value, $allowed_tags ) . "<br /><br />";
            } else {
                $label = isset( $labels[$key] ) ? $labels[$key] : $key;
                $message .= "{$label}: " . nl2br( esc_html( $value ) ) . "<br />";
            }
        }

        // Process file uploads if any files were submitted.
        $uploaded_files = array();
        $attachments    = array();
        if ( ! empty( $_FILES['amin_file'] ) && ! empty( $_FILES['amin_file']['name'][0] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            $files = $_FILES['amin_file'];
            $file_count = count( $files['name'] );
            for ( $i = 0; $i < $file_count; $i++ ) {
                if ( $files['error'][$i] === UPLOAD_ERR_OK ) {
                    $file = array(
                        'name'     => $files['name'][$i],
                        'type'     => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i]
                    );
                    $upload_overrides = array( 'test_form' => false );
                    $movefile = wp_handle_upload( $file, $upload_overrides );
                    if ( $movefile && ! isset( $movefile['error'] ) ) {
                        $uploaded_files[] = $movefile['url'];
                        $attachments[] = $movefile['file'];
                    } else {
                        error_log( 'File upload error: ' . $movefile['error'] );
                    }
                }
            }
            if ( ! empty( $uploaded_files ) ) {
                $submission['amin_file'] = $uploaded_files;
                $message .= "Uploaded Files: " . implode( ', ', $uploaded_files ) . "<br />";
            }
        }
        
        // Process image upload from the image field.
        if ( ! empty( $_FILES['amin_image'] ) && ! empty( $_FILES['amin_image']['name'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            $upload_overrides = array( 'test_form' => false );
            $movefile = wp_handle_upload( $_FILES['amin_image'], $upload_overrides );
            if ( $movefile && ! isset( $movefile['error'] ) ) {
                // Save the image URL in the submission.
                $submission['amin_image'] = $movefile['url'];
                // Append a thumbnail preview to the email message.
                $message .= "Uploaded Image:<br /><img src='" . esc_url( $movefile['url'] ) . "' style='max-width:200px;'><br /><br />";
                // Optionally attach the image file.
                $attachments[] = $movefile['file'];
            } else {
                error_log( 'Image upload error: ' . $movefile['error'] );
            }
        }
        
        // Save the submission into the custom entries table.
        $table_name = $wpdb->prefix . 'amin_form_entries';
        $inserted = $wpdb->insert( 
            $table_name, 
            array(
                'form_id'         => $form_id,
                'submission'      => maybe_serialize( $submission ),
                'submission_date' => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s' )
        );
        
        if ( ! $inserted ) {
            wp_die( 'There was an error saving your submission. Please try again.' );
        }
        
        // Retrieve destination settings from admin.
        $destinations = get_option( 'amin_form_builder_destinations', '' );
        $destinations = $destinations ? json_decode( $destinations, true ) : [];
        $email_settings = isset( $destinations[$form_id] ) ? $destinations[$form_id] : [];
        
        // Use admin-set values with fallbacks.
        $to_emails = ! empty( $email_settings['emails'] ) ? $email_settings['emails'] : get_bloginfo( 'admin_email' );
        $to_emails = array_map( 'trim', explode( ',', $to_emails ) );
        $subject = ! empty( $email_settings['subject'] ) ? $email_settings['subject'] : "New Form Submission for {$form_id}";
        
        // Retrieve the email provided by the user in the form.
        $user_email = isset( $submission['amin_email'] ) ? sanitize_email( $submission['amin_email'] ) : '';
        if ( empty( $user_email ) ) {
            $user_email = get_bloginfo( 'admin_email' );
        }
        $reply_to = $user_email;
        
        // Retrieve sender name and sender email from admin settings.
        $sender_name = ! empty( $email_settings['sender_name'] ) ? $email_settings['sender_name'] : get_bloginfo( 'name' );
        $from_email = ! empty( $email_settings['from_email'] ) ? $email_settings['from_email'] : get_bloginfo( 'admin_email' );
        
        // Prepare email headers with "Reply-To" properly wrapped.
        $headers = array(
            "From: {$sender_name} <{$from_email}>",
            "Reply-To: <{$reply_to}>"
        );
        
        // Set the email content type to HTML.
        add_filter( 'wp_mail_content_type', function() {
            return 'text/html';
        } );
        
        // Send email notification using wp_mail with attachments.
        wp_mail( $to_emails, $subject, $message, $headers, $attachments );
        // Remove the email content type filter.
        remove_filter( 'wp_mail_content_type', '__return_true' );
        
        // Redirect back to the referring page with a success parameter.
        $referer = wp_get_referer();
        if ( ! $referer ) {
            $referer = home_url();
        }
        $redirect_url = add_query_arg( 'form_submission', 'success', $referer );
        wp_redirect( $redirect_url );
        exit;
    }
}
