<?php

/**
 * Plugin Name: AI Featured Image Generator for Posts
 * Description: Adds a button to generate AI images for posts based on title and content using Together API.
 * Version: 1.0
 * Author: Muhammad Burhan Sultan
 * License: GPL2
 * Text Domain: ai-image-generator-for-posts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include admin settings
require_once(plugin_dir_path(__FILE__) . 'admin/ai-image-generator-for-posts-settings.php');

// Enqueue JavaScript for handling the button and AJAX
add_action('admin_enqueue_scripts', 'aigfp_image_generator_enqueue_scripts');
/**
 * Enqueue JavaScript for handling the button and AJAX
 *
 * Enqueues the JavaScript file from the `js` folder if we are on the post.php or post-new.php page.
 *
 * @since 1.0
 *
 * @param string $hook_suffix The current page hook suffix.
 */
function aigfp_image_generator_enqueue_scripts($hook_suffix)
{
    if ('post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
        wp_enqueue_script(
            'ai-image-generator-for-posts-js',
            plugin_dir_url(__FILE__) . 'js/ai-image-generator-for-posts.js',
            array('jquery'),
            1.0,
            true
        );

        wp_localize_script('ai-image-generator-for-posts-js', 'aigfp_image_generator', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aigfp_image_generator_nonce')
        ));
    }
}

// Add the button below the Featured Image meta box
add_action('do_meta_boxes', 'aigfp_image_generator_add_below_featured_image_meta_box');
/**
 * Add the button below the Featured Image meta box
 *
 * Adds a meta box below the Featured Image meta box on the post editor page. This
 * meta box contains the "Generate AI Image" button.
 *
 * @since 1.0
 */
function aigfp_image_generator_add_below_featured_image_meta_box()
{   
    $selected_post_type = get_option('aigfp_selected_cpts');
    add_meta_box('aigfp_image_generator', __('AI Image Generator', 'ai-image-generator-for-posts'), 'aigfp_image_generator_meta_box_callback', $selected_post_type, 'side', 'low');
}

// Callback function to display the button in the new meta box
function aigfp_image_generator_meta_box_callback($post)
{
    echo '<div id="ai-image-generator-for-posts-container" style="margin-top: 10px;">
    <label for="size-aigfp-image"><p>'. esc_html__('Select Image Size', 'ai-image-generator-for-posts') .'<p></label>        
    <select id="size-aigfp-image" name="size-aigfp-image">
            <option value="1" selected>' . esc_html__('1:1 = 400 x 400 pixels', 'ai-image-generator-for-posts') . '</option>
            <option value="3">' . esc_html__('3:2 = 960 x 640 pixels', 'ai-image-generator-for-posts') . '</option>
            <option value="16">' . esc_html__('16:9 = 1280 x 720 pixels', 'ai-image-generator-for-posts') . '</option>    
            </select><br><br>
            <button type="button" class="button button-primary" id="generate-aigfp-image">' . esc_html__('Generate AI Image', 'ai-image-generator-for-posts') . '</button>
            <div id="aigfp-image-result"></div>
          </div>';
}




// Handle AJAX request to generate the AI image
add_action('wp_ajax_generate_aigfp_image', 'aigfp_image_generator_ajax_generate_image');
/**
 * Handles an AJAX request to generate an AI image for the given post.
 *
 * Verifies the nonce, retrieves the post data, and calls the image generation
 * function. Sends a JSON response containing the image URL on success, or an
 * error message on failure.
 *
 * @since 1.0
 */
function aigfp_image_generator_ajax_generate_image()
{
    check_ajax_referer('aigfp_image_generator_nonce', 'nonce');

    // Get the post ID from the AJAX request
    if (isset($_POST['post_id'])) {
        $post_id = intval($_POST['post_id']);
    }
    if(isset($_POST['image_size'])){
        $image_size = intval($_POST['image_size']);
    }
    if (!$post_id) {
        wp_send_json_error(__('Invalid post ID.', 'ai-image-generator-for-posts'));
        return;
    }

    if (!$image_size) {
        $image_size = '16';
    }

    // Get the post data
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(__('Post not found.', 'ai-image-generator-for-posts'));
        return;
    }

    // Get Huugine Face AI API key from options
    $api_key = get_option('aigfp_api_key');
    if (!$api_key) {
        wp_send_json_error(__('Together AI API key is missing.', 'ai-image-generator-for-posts'));
        return;
    }

    // Call the image generation function
    $image_url = aigfp_image_generator_generate_image($post->post_title, $post->post_content, $image_size,$api_key);

    // Check if image generation was successful
    if ($image_url) {
        wp_send_json_success(array('image_url' => $image_url));
    } else {
        wp_send_json_error(__('Failed to generate the image.', 'ai-image-generator-for-posts'));
    }
}

include('api/ai-image-generator-for-posts-api.php');

// Handle setting the Base64 image and storing the metadata temporarily
add_action('wp_ajax_set_aigfp_image_as_featured', 'aigfp_image_generator_set_temp_featured_image');
/**
 * Handles setting the Base64 image and storing the metadata temporarily via AJAX.
 *
 * When the `set_aigfp_image_as_featured` action is triggered via AJAX, this function
 * decodes the Base64 image data, saves it to a file, checks the file type, inserts
 * the image into the media library, and temporarily stores the image attachment ID
 * in post meta.
 *
 * @since 1.0
 */
function aigfp_image_generator_set_temp_featured_image()
{
    check_ajax_referer('aigfp_image_generator_nonce', 'nonce');
    if (isset($_POST['post_id'])) {
        $post_id = intval($_POST['post_id']);
    }
    if (isset($_POST['base64_image'])) {
        $base64_image = sanitize_text_field(wp_unslash($_POST['base64_image']));
    }

    // Check if it's a valid post type (only for "post")
    $post_type = get_post_type($post_id);
    $selected_post_type = get_option('aigfp_selected_cpts');
    if (!in_array($post_type, $selected_post_type)) {
        wp_send_json_error(__('Invalid post type. This action only applies to the selected post types.', 'ai-image-generator-for-posts'));
    }

    if (!$post_id || !$base64_image) {
        wp_send_json_error(__('Invalid post or Base64 image data.', 'ai-image-generator-for-posts'));
    }

    // Decode the Base64 image data
    $image_data = base64_decode($base64_image);

    if (!$image_data) {
        wp_send_json_error(__('Failed to decode Base64 image.', 'ai-image-generator-for-posts'));
    }

    // Create a unique filename for the image
    $upload_dir = wp_upload_dir();
    $file_name = uniqid() . '.jpg';  // Save as JPEG
    $file_path = wp_mkdir_p($upload_dir['path']) ? $upload_dir['path'] . '/' . $file_name : $upload_dir['basedir'] . '/' . $file_name;

    // Save the decoded image data to a file
    global $wp_filesystem;

    if (! function_exists('request_filesystem_credentials')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    WP_Filesystem();

    if (! $wp_filesystem->put_contents($file_path, $image_data, FS_CHMOD_FILE)) {
        wp_send_json_error(__('Failed to save image file.', 'ai-image-generator-for-posts'));
    }


    // Check the file type and insert into the media library
    $wp_filetype = wp_check_filetype($file_name, null);
    if (!$wp_filetype['type']) {
        wp_send_json_error(__('Invalid file type.', 'ai-image-generator-for-posts'));
    }

    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name($file_name),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    // Insert the image into the media library
    $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Temporarily store the image attachment ID in post meta
    update_post_meta($post_id, '_temp_aigfp_featured_image', $attach_id);

    wp_send_json_success(array(
        'message' => __('Image ready to be set as featured image upon saving the post.', 'ai-image-generator-for-posts'),
        'attachment_id' => $attach_id  // Return the attachment ID for JavaScript
    ));
}

add_action('wp_ajax_set_native_featured_image', 'aigfp_set_native_featured_image');
/**
 * Handles an AJAX request to set a native image as the featured image for a given post.
 *
 * Verifies the nonce, retrieves the post ID and image ID from the request, and sets the
 * featured image. Sends a JSON response on success, or an error message on failure.
 *
 * @since 1.0
 */
function aigfp_set_native_featured_image()
{
    check_ajax_referer('aigfp_image_generator_nonce', 'nonce');

    if (isset($_POST['post_id'])) {
        $post_id = intval($_POST['post_id']);
    }
    if (isset($_POST['image_id'])) {
        $image_id = intval($_POST['image_id']);
    }
    if ($post_id && $image_id) {
        // Set the selected image as the post's featured image
        set_post_thumbnail($post_id, $image_id);
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid post ID or image ID.');
    }
}

add_action('save_post', 'aigfp_image_generator_save_featured_image', 10, 2);
/**
 * Handles saving the post, setting the AI-generated image as the featured image if needed.
 *
 * Checks if an AI image is set in post meta and sets it as the featured image if so.
 * Clears the temp meta after use.
 *
 * @since 1.0
 *
 * @param int $post_id The post ID.
 * @param WP_Post $post The post object.
 */
function aigfp_image_generator_save_featured_image($post_id, $post)
{
    // Skip if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $selected_post_type = get_option('aigfp_selected_cpts');

    // Only apply to 'post' type
    if (!in_array($post->post_type, $selected_post_type))  return;

    // Check if an AI image is set in meta
    $aigfp_image_id = get_post_meta($post_id, '_temp_aigfp_featured_image', true);
    if ($aigfp_image_id) {
        set_post_thumbnail($post_id, $aigfp_image_id);
        delete_post_meta($post_id, '_temp_aigfp_featured_image'); // Clear temp meta after use
    }
}
