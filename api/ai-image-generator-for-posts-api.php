<?php

/**
 * Generates an AI image based on a given title and content using the Together API.
 *
 * @since 1.0
 *
 * @param string $title The title of the post.
 * @param string $content The content of the post.
 * @param string $api_key The user's Together AI API key.
 *
 * @return string|false The generated image as a base64-encoded string, or false if the image generation failed.
 */
function aigfp_image_generator_generate_image($title, $content, $size, $api_key)
{
    $api_url = 'https://api.together.xyz/v1/images/generations';
    $prompt = sanitize_text_field($title . ' ' . $content);
    $image_size = $size;


    if ($image_size == '1') {
        $image_width = 400;
        $image_height = 400;
    } elseif ($image_size = '3') {
        $image_width = 960;
        $image_height = 640;
    } else {
        $image_width = 1280;
        $image_height = 720;
    }

    $response_body = array(
        "model" => "black-forest-labs/FLUX.1-schnell-Free",
        "prompt" => $prompt,
        "width" => $image_width,
        "height" => $image_height,
        "steps" => 1,
        "n" => 1,
        "response_format" => "b64_json"
    );

    $response = wp_remote_post($api_url, array(
        'body' => wp_json_encode($response_body),
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'timeout' => 60,
    ));

    if (is_wp_error($response)) {
        return false;
    }


    $body = wp_remote_retrieve_body($response);

    // Decode the JSON response
    $result = json_decode($body, true);

    $b64image = $result['data'][0]['b64_json'];

    // Check if the 'image' field exists
    if (isset($b64image)) {
        return $b64image;
    }

    return false;
}
