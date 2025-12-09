jQuery(document).ready(function($) {
    var mediaUploader;

    $('#upload_image_button').click(function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media({
            title: projectsMapAdmin.choose_image,
            button: {
                text: projectsMapAdmin.choose_image
            },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#image').val(attachment.url);
            // Update or add the preview
            var img = $('#image').next('br').next('img');
            if (img.length) {
                img.attr('src', attachment.url);
            } else {
                $('#image').after('<br><img src="' + attachment.url + '" style="max-width:100px; margin-top:5px;">');
            }
        });
        mediaUploader.open();
    });
});