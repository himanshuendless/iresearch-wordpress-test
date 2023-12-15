<?php
/**
 * Plugin Name: Ticket Book for CF7
 * Description: A custom plugin to add ticket booking functionality with country names to Contact Form 7.
 * Version: 1.0
 * Author: Your Name
 */
// CSS Styling and JS

function tb_cf7_enqueue_scripts() {
    wp_enqueue_style('tb-cf7-styles', plugin_dir_url(__FILE__) . 'styles.css');
    wp_enqueue_script('tb-cf7-script', plugin_dir_url(__FILE__) . 'scripts.js', array(), false, true);
}

add_action('wp_enqueue_scripts', 'tb_cf7_enqueue_scripts');

// Activation Hook
register_activation_hook(__FILE__, 'tb_cf7_create_table');
function tb_cf7_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tb_cf7_tickets';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        field_id mediumint(9) NOT NULL AUTO_INCREMENT,
        field_value tinyint(1) NOT NULL DEFAULT 0,
        field_label varchar(255) NOT NULL,
        PRIMARY KEY  (field_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Array of 100 random country names
    $countries = [
        "Afghanistan", "Albania", "Algeria", "Andorra", "Angola",
        "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria",
        "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados",
        "Belarus", "Belgium", "Belize", "Benin", "Bhutan",
        "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei",
        "Bulgaria", "Burkina Faso", "Burundi", "Cabo Verde", "Cambodia",
        "Cameroon", "Canada", "Central African Republic", "Chad", "Chile",
        "China", "Colombia", "Comoros", "Congo", "Costa Rica",
        "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark",
        "Djibouti", "Dominica", "Dominican Republic", "Ecuador", "Egypt",
        "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Eswatini",
        "Ethiopia", "Fiji", "Finland", "France", "Gabon",
        "Gambia", "Georgia", "Germany", "Ghana", "Greece",
        "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana",
        "Haiti", "Honduras", "Hungary", "Iceland", "India",
        "Indonesia", "Iran", "Iraq", "Ireland", "Israel",
        "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan",
        "Kenya", "Kiribati", "Korea", "Kosovo", "Kuwait",
        "Kyrgyzstan", "Laos", "Latvia", "Lebanon", "Lesotho"
    ];

    // Insert 100 records with country names as labels
    foreach ($countries as $country) {
        $wpdb->insert($table_name, array('field_label' => $country, 'field_value' => 0));
    }
}

// Shortcode for displaying tickets
add_shortcode('ticket_book_cf7', 'tb_cf7_shortcode');
function tb_cf7_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tb_cf7_tickets';
    $tickets = $wpdb->get_results("SELECT * FROM $table_name");

    $html = '<div class="ticket-book-cf7">';
    foreach ($tickets as $ticket) {
        $disabled = $ticket->field_value ? 'disabled' : '';
        $disabledLabelClass = $ticket->field_value ? 'disabled-label' : '';
        $html .= "<label class='{$disabledLabelClass}'><input type='checkbox' name='tickets[]' value='{$ticket->field_id}' $disabled /> {$ticket->field_label}</label><br />";
    }
    $html .= '</div>';

    return $html;
}


// Handle form submission (assuming CF7 submits to the same URL)
add_action('init', 'tb_cf7_handle_form_submission');
function tb_cf7_handle_form_submission() {
    if (isset($_POST['tickets']) && is_array($_POST['tickets'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tb_cf7_tickets';

        foreach ($_POST['tickets'] as $ticket_id) {
            $wpdb->update($table_name, array('field_value' => 1), array('field_id' => intval($ticket_id)));
        }
    }
}

// Handle the shortcode

add_action('wpcf7_before_send_mail', 'tb_cf7_handle_form_submission_cf7');
function tb_cf7_handle_form_submission_cf7($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $posted_data = $submission->get_posted_data();

        // Assuming 'tickets' is the name of your checkbox field
        if (isset($posted_data['tickets'])) {
            // Handle the tickets data as before
            global $wpdb;
            $table_name = $wpdb->prefix . 'tb_cf7_tickets';

            foreach ($posted_data['tickets'] as $ticket_id) {
                $wpdb->update($table_name, array('field_value' => 1), array('field_id' => intval($ticket_id)));
            }
        }
    }
}

add_filter('wpcf7_special_mail_tags', 'tb_cf7_special_mail_tag', 10, 3);
function tb_cf7_special_mail_tag($output, $name, $html) {
    if ('ticket-book-cf7' == $name) {
        return tb_cf7_shortcode(); // This calls your shortcode function
    }
    return $output;
}

add_filter('wpcf7_form_elements', 'do_shortcode');


?>

