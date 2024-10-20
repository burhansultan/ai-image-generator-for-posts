=== AI Image Generator for Posts ===
Contributors: Muhammad Burhan Sultan
Tags: AI, image generator, post automation, Together API
Requires at least: 5.0
Tested up to: 6.6.2
Requires PHP: 7.0
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate AI images for your WordPress posts based on the title and content, Powered by Together API.

== Description ==
AI Image Generator for Posts is a powerful tool that allows WordPress users to automatically generate AI-generated images for their posts based on the title and content. Utilizing state-of-the-art AI models, such as Stable Diffusion via Together AI, the plugin provides a seamless integration into the WordPress editor. Users can generate images and set them as the post's featured image with ease, improving content visual appeal. The plugin also includes settings for custom API keys and offers flexibility for image generation.

== Installation ==
1. Upload the `ai-image-generator-for-posts` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to Settings > AI Image Generator to enter your API key.

== External Services ==
This plugin connects to the Together API to generate AI images for posts. The API is used to generate images based on the post title and content.

The following data is sent to the Together API:

* Post title
* Post content
* API key (provided by the user)

The API is used to generate images in the following conditions:

* When the user clicks the "Generate AI Image" button
* When the user saves the post

Please note that the Together API has its own terms of service and privacy policy, which can be found at [Terms and Conditons](https://www.together.ai/terms-of-service) and [Privacy](https://www.together.ai/privacy) respectively.

== Frequently Asked Questions ==

= Which AI service does this plugin use? =
This plugin integrates with Together free, decentralized API.

== Screenshots ==
1. AI Image Generator settings page.

== Changelog ==
= 1.0 =
* Initial release.
