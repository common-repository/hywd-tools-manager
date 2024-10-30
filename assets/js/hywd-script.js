jQuery(document).ready(function($) {

    $('.hywd-plugin-info-popup[data-title]').hover(function() {
        $(this).removeClass('tooltip-hidden');
    });

    $('.hywd-plugin-info-popup[data-title]').click(function() {
        $(this).addClass('tooltip-hidden');
    });

    $('.license-edit-btn').click(function (){
        $(this).parent('form.hywd-license-verification-form').find('#hywd-license-key-input').attr('disabled', false)
        $(this).parent('form.hywd-license-verification-form').find('#hywd-license-key-input').removeClass('hywd-license-input-disabled')
        $(this).parent('form.hywd-license-verification-form').find('#hywd-license-key-input').focus()
        $(this).parent('form.hywd-license-verification-form').find('.check-icon').hide()
        $(this).parent('form.hywd-license-verification-form').find('.right-arrow').show()

        var encoded_license = $(this).parent('form.hywd-license-verification-form').find('#hywd-license-key-input').data('license')
        $(this).parent('form.hywd-license-verification-form').find('#hywd-license-key-input').val(atob(encoded_license))

    })


    $('.hywd-license-verification-form').on('submit', function(e) {
        e.preventDefault();
        var api_action = 'activate'
        var validating_msg = 'Validating License...'

        var license_key = $(this).find('#hywd-license-key-input').val();
        var plugin_unique_id = $(this).find('#hywd-plugin-unique-id').val();

        if(license_key.trim() == ''){
            var encoded_license = $(this).find('#hywd-license-key-input').data('license')
            if(encoded_license != ''){
                license_key = atob(encoded_license)
                api_action = 'deactivate'
                validating_msg = "Deactivating License..."
            }

        }

        var success_msg_block = $('#hywd-response-success-'+plugin_unique_id)
        var error_msg_block = $('#hywd-response-error-'+plugin_unique_id)

        error_msg_block.hide()
        success_msg_block.show()
        success_msg_block.text(validating_msg)



        // AJAX request
        $.ajax({
            url: php_js_var.admin_ajax, // WordPress AJAX handler
            type: 'POST',
            data: {
                // action: '\\HywdPluginManager\\Admin\\hywd_verify_lk',
                action: 'hywd_verify_lk',
                license_key: license_key,
                plugin_unique_id: plugin_unique_id,
                api_action: api_action,
                nonce: php_js_var.ajax_nonce,
            },
            success: function(response) {
                var response = JSON.parse(response);

                success_msg_block.text('')
                error_msg_block.text('')
                // Handle the API response
                if(response.status === 'success' || response.status === 'deactivated'){
                    error_msg_block.hide()
                    success_msg_block.show()
                    success_msg_block.text(response.message);
                    location.reload()
                }else if(response.status === 'error'){
                    success_msg_block.hide()
                    error_msg_block.show()
                    error_msg_block.text(response.message);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                console.error(xhr, textStatus, errorThrown);
                success_msg_block.hide()
                error_msg_block.show()
                error_msg_block.text(errorThrown);
            }
        });
    });

});

