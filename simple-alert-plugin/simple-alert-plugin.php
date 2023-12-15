<?php
/**
 * Plugin Name: Simple Alert Plugin
 * Description: Display alerts on selected posts and pages.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function sap_enqueue_scripts() {
    if (is_admin()) {
        wp_enqueue_script('jquery');
    }
}

add_action('admin_enqueue_scripts', 'sap_enqueue_scripts');

function sap_admin_menu() {
    add_options_page('Simple Alert Plugin Settings', 'Alert Settings', 'manage_options', 'sap-settings', 'sap_settings_page');
}

add_action('admin_menu', 'sap_admin_menu');

function sap_get_post_types() {
    $args = array(
       'public'   => true,
    );

    return get_post_types($args, 'objects');
}

function sap_settings_page() {
    ?>
    <div class="wrap">
        <h2>Simple Alert Plugin Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('sap-settings-group');
            do_settings_sections('sap-settings-group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Alert Text</th>
                    <td><input type="text" name="sap_alert_text" value="<?php echo esc_attr(get_option('sap_alert_text')); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Select Post Type</th>
                    <td>
                        <select id="sap_post_type" name="sap_post_type">
                            <option value="">Select a Post Type</option>
                            <?php
                            $post_types = sap_get_post_types();
                            foreach ($post_types as $post_type) {
                                echo '<option value="' . $post_type->name . '">' . $post_type->labels->singular_name . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <tr valign="top" style="display:none;" id="sap_posts_row">
                    <th scope="row">Select Posts</th>
                        <td id="sap_posts">
                            <!-- Checkboxes will be loaded via JavaScript -->
                        </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#sap_post_type').change(function() {
            var postType = $(this).val();
            $('#sap_posts').html(''); // Clear the posts box
            if (postType) {
                $('#sap_posts_row').show();
                // Fetch posts for the selected post type
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sap_get_posts',
                        post_type: postType,
                    },
                    success: function(response) {
                        $('#sap_posts').html(response);
                    }
                });
            } else {
                $('#sap_posts_row').hide();
            }
        });
    });
    </script>

    <?php
}

function sap_register_settings() {
    register_setting('sap-settings-group', 'sap_alert_text');
    register_setting('sap-settings-group', 'sap_posts');
}

add_action('admin_init', 'sap_register_settings');

function sap_ajax_get_posts() {
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
    $posts = get_posts(array('post_type' => $post_type, 'numberposts' => -1));
    $selected_posts = get_option('sap_posts') ?: array();

    foreach ($posts as $post) {
        $checked = in_array($post->ID, $selected_posts) ? 'checked' : '';
        echo '<label><input type="checkbox" name="sap_posts[]" value="' . $post->ID . '" ' . $checked . '> ' . $post->post_title . '</label><br>';
    }
    wp_die();
}


add_action('wp_ajax_sap_get_posts', 'sap_ajax_get_posts');

function sap_display_alert() {
    global $post;
    $alert_text = get_option('sap_alert_text');
    $selected_posts = get_option('sap_posts');

    if (!empty($alert_text) && is_array($selected_posts) && in_array($post->ID, $selected_posts)) {
        echo '<div class="sap-alert" style="position: fixed; top: 0; width: 100%; z-index: 9999;">' . esc_html($alert_text) . '</div>';
    }
}

add_action('wp_head', 'sap_display_alert');

