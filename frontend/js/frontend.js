jQuery(document).ready(function($) {
    // Clear any inline error messages when focusing fields
    $('.amin-frontend-form-container input, .amin-frontend-form-container select, .amin-frontend-form-container textarea').on('focus', function() {
        $(this).next('.amin-error-message').remove();
    });

    // reCAPTCHA validation
    $('.amin-frontend-form-container').on('submit', function(e) {
        $('.g-recaptcha').next('.recaptcha-error').remove();
        if (typeof grecaptcha !== 'undefined' && grecaptcha.getResponse() === "") {
            e.preventDefault();
            $('.g-recaptcha').after('<div class="recaptcha-error" style="color: red; margin-top: 5px;">Please complete the reCAPTCHA verification.</div>');
        }
    });

    // Show selected file names
    $('.custom-file-upload').on('change', function () {
        const fileNames = Array.from(this.files).map(f => f.name).join(', ');
        $(this).closest('.file-upload-field').find('.selected-files').text(fileNames);
    });
});
