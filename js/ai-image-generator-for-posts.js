jQuery(document).ready(function ($) {
    $('#generate-aigfp-image').click(function () {
        var postId = $('#post_ID').val();
        var imageSize = $('#size-aigfp-image').val();

        // Disable the button to prevent multiple clicks
        $('#generate-aigfp-image').prop('disabled', true).text('Generating...');

        // Prepare data for AJAX request
        var data = {
            action: 'generate_aigfp_image',
            post_id: postId,
            image_size: imageSize,
            nonce: aigfp_image_generator.nonce
        };
        console.log(data);
        // Clear previous results and show loading message
        $('#aigfp-image-result').html('<p>Generating image...</p>');

        // Make AJAX request to generate AI image
        $.post(aigfp_image_generator.ajax_url, data, function (response) {
            console.log(response);
            if (response.success) {
                var base64_image = response.data.image_url; // Base64 image string

                // Show the generated image and button to set as featured image
                $('#aigfp-image-result').html('<img style="margin:20px 0" src="data:image/png;base64,' + base64_image + '" width="100%" /><br>' +
                    '<button type="button" class="button button-primary" id="set-aigfp-image">' +
                    'Set as Featured Image</button>');

                // Enable the "Set as Featured Image" button
                $('#set-aigfp-image').click(function () {
                    var data = {
                        action: 'set_aigfp_image_as_featured',
                        post_id: postId,
                        base64_image: base64_image,  // Send the Base64 image
                        nonce: aigfp_image_generator.nonce
                    };

                    // Make AJAX request to set the Base64 image as the featured image
                    $.post(aigfp_image_generator.ajax_url, data, function (response) {
                        if (response.success) {
                            alert('Featured image set successfully.');
                            $('#aigfp-image-result').html('');
                            wp.media.featuredImage.set(response.data.attachment_id);  // Set the featured image
                        } else {
                            alert('Error setting featured image: ' + response.data);
                        }
                    }).fail(function (jqXHR, textStatus, errorThrown) {
                        alert('Error: ' + textStatus + ' - ' + errorThrown);
                    });
                });
            } else {
                $('#aigfp-image-result').html('<p>Error generating image: ' + response.data + '</p>');
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            $('#aigfp-image-result').html('<p>Error: ' + textStatus + ' - ' + errorThrown + '</p>');
        }).always(function () {
            $('#generate-aigfp-image').prop('disabled', false).text('Regenerate AI Image');
        });
    });

    wp.media.featuredImage.frame().on('select', function () {
        var attachment = wp.media.featuredImage.frame().state().get('selection').first().toJSON();
        if (attachment.id) {
            // Send the selected image ID via AJAX
            var data = {
                action: 'set_native_featured_image',
                post_id: $('#post_ID').val(),
                image_id: attachment.id,
                nonce: aigfp_image_generator.nonce
            };
            $.post(aigfp_image_generator.ajax_url, data, function (response) {
                if (response.success) {
                    console.log('Image set as featured: ' + attachment.id);
                } else {
                    console.log('Error setting featured image.');
                }
            });
        }
    });
});


