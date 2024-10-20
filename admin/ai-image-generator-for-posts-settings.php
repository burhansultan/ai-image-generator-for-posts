<?php
// Add settings page for Hugging Face API Key
add_action('admin_menu', 'aigfp_image_generator_add_settings_page');

/**
 * Adds a settings page for AI Image Generator under the Settings menu.
 *
 * The settings page allows users to enter their Together API key.
 *
 * @since 1.0
 */
function aigfp_image_generator_add_settings_page()
{
    add_options_page(
        'AI Image Generator Settings',
        'AI Image Generator',
        'manage_options',
        'ai-image-generator-for-posts-settings',
        'aigfp_image_generator_settings_page'
    );
}


/**
 * Outputs the settings page for AI Image Generator.
 *
 * Displays the settings page allowing users to enter their Together API key.
 *
 * @since 1.0
 */
function aigfp_image_generator_settings_page()
{
?>
    <div class="wrap container text-center col-6 aigfp-container">
        <img src="<?php echo  AIGFP_BASE_PATH . 'images/icon-256x256.png' ?>" width="120" class="img-responsive">
        <h1 class="text-center"><?php esc_html_e('AI Image Generator Settings', 'ai-image-generator-for-posts'); ?></h1>
        <form class="form table" method="post" action="options.php">
            <?php
            settings_fields('aigfp_image_generator_settings');
            do_settings_sections('ai-image-generator-for-posts-settings');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

add_action('admin_init', 'aigfp_image_generator_register_settings');

/**
 * Registers the plugin's settings with WordPress.
 *
 * Registers the 'aigfp_api_key' setting with WordPress, adds a settings section
 * to the settings page, and adds a field to enter the Together API key.
 *
 * @since 1.0
 */
function aigfp_image_generator_register_settings()
{
    register_setting(
        'aigfp_image_generator_settings',
        'aigfp_api_key',
        array(
            'sanitize_callback' => 'sanitize_text_field'
        )
    );

    // Register selected CPTs
    register_setting(
        'aigfp_image_generator_settings',
        'aigfp_selected_cpts',
        array(
            'sanitize_callback' => 'aigfp_sanitize_cpts'
        )
    );

    add_settings_section(
        'aigfp_image_generator_section',
        esc_html__('Together API Key', 'ai-image-generator-for-posts'),
        'aigfp_image_generator_section_callback',
        'ai-image-generator-for-posts-settings'
    );

    add_settings_field(
        'aigfp_api_key',
        esc_html__('Together API Key', 'ai-image-generator-for-posts'),
        'aigfp_image_generator_api_key_field_callback',
        'ai-image-generator-for-posts-settings',
        'aigfp_image_generator_section'
    );

    add_settings_field(
        'aigfp_selected_cpts',
        esc_html__('Select Post Types for AI Image Generator', 'ai-image-generator-for-posts'),
        'aigfp_image_generator_cpts_field_callback',
        'ai-image-generator-for-posts-settings',
        'aigfp_image_generator_section'
    );
}


/**
 * Outputs the settings section description for the Together API key field.
 *
 * Displays a message above the API key input field with a link to create a
 * new API key at Together AI.
 *
 * @since 1.0
 */
function aigfp_image_generator_section_callback()
{
    echo esc_html_e('Enter your Together API key below.', 'ai-image-generator-for-posts');
    echo '<br>';
    echo esc_html_e('Create your API Key here https://api.together.xyz/', 'ai-image-generator-for-posts');
}


/**
 * Outputs the form field for the Together API key.
 *
 * Retrieves the current value of the 'aigfp_api_key' option and outputs an
 * input field with that value. The input field is given a class of
 * 'regular-text' to match the styling of other text input fields in the
 * WordPress admin.
 *
 * @since 1.0
 */

function aigfp_image_generator_api_key_field_callback()
{
    $api_key = get_option('aigfp_api_key');
    echo '<input type="text" class="form-control form-control-lg" name="aigfp_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
}

/**
 * Outputs the form field for selecting custom post types.
 */
function aigfp_image_generator_cpts_field_callback()
{
    $selected_cpts = get_option('aigfp_selected_cpts', array());
    $post_types = get_post_types(array('public' => true), 'objects');
    echo '<div class="text-left row">';
    foreach ($post_types as $post_type) {
        $checked = in_array($post_type->name, $selected_cpts) ? 'checked' : '';
        echo '<div class="list-group-item  text-start col-3">
        <input type="checkbox" class="form-check-input" name="aigfp_selected_cpts[]" value="' . esc_attr($post_type->name) . '" ' . esc_attr($checked) . '>
        <label class="form-check-label">' . esc_html($post_type->label) . '</label>
        </div>';
    }
    echo '</div>';
}

/**
 * Sanitizes the selected CPTs.
 */
function aigfp_sanitize_cpts($input)
{
    return is_array($input) ? array_map('sanitize_text_field', $input) : array();
}