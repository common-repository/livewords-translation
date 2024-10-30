(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */


    $(function() {

        /**
         * On the settings page
         * The api connection testing button
         */
        $(document).on('click', "#test_connection", function (event) {
            event.preventDefault(); // cancel default behavior
            var data = {
                'action': 'test_api_connection'
            };

            $.post(ajaxurl, data, function(response) {
                $('.connection_test_response').html(response);
                // alert('Got this from the server: ' + response);
            });

        });

        /**
         * On the settings page
         * Button for bulk posting posts to LiveWords API
         */
        $(document).on('click', ".bulk_translate_posts input", function (event) {
            event.preventDefault(); // cancel default behavior

            var $bulkWrapper = $('.bulk_translate_posts');
            var $bulkButton = $(event.target);

            // Set button in progress after being clicked
            $bulkButton.attr('disabled', true).attr('value', $bulkWrapper.attr('in-progress-text'));

            // Send data to the backend.
            var data = {
                'action': 'bulk_translate_posts',
                'post-type': $bulkButton.attr('post-type'),
                'forced':  $bulkButton.attr('forced')
            };

            // Clear response text
            $('#bulk_translate_posts_response-' + $bulkButton.attr('post-type')).html('');

            // Post this to the backend.
            $.post(ajaxurl, data, function (response) {

            }).done(function (response) {

                // The server has never been reached.
                if (response.livewordsApi.errors) {
                    $bulkButton.attr('disabled', false).attr('value', $bulkWrapper.attr('fail-text'));
                    $('#bulk_translate_posts_response-' + $bulkButton.attr('post-type')).html(JSON.stringify(response.livewordsApi.errors));
                } else {
                    // Server was reached.
                    // Test if for LiveWords code 200 response
                    if (response.livewordsApi.response.code == 200) {
                        $bulkButton.attr('disabled', true).attr('value', $bulkWrapper.attr('done-text'));
                    } else {
                        $bulkButton.attr('disabled', false).attr('value', $bulkWrapper.attr('fail-text'));
                        $('#bulk_translate_posts_response-' + $bulkButton.attr('post-type')).html(response.livewordsApi.response.message);
                    }
                }


            }).fail(function () {
                // Ajax something in the plugin went wrong.
                $bulkButton.attr('disabled', false).attr('value', $bulkWrapper.attr('fail-text'));
            });

        });


        /**
         * On the settings page
         * Button for posting BULK TAXONOMIES to LiveWords API
         */
        $(document).on('click', "#translate_taxonomies", function (event) {
            event.preventDefault(); // cancel default behavior

            var $bulkWrapper = $('.bulk_translate_taxonomies');
            var $bulkButton = $(event.target);

            // Set button in progress after being clicked
            $bulkButton.attr('disabled', true).attr('value', $bulkWrapper.attr('in-progress-text'));

            var data = {
                'action': 'translate_taxonomies'
            };

            $.post(ajaxurl, data, function (response) {

            }).done(function (response) {

                // The server has never been reached.
                if (response.livewordsApi.errors) {
                    $bulkButton.attr('disabled', false).attr('value', $bulkWrapper.attr('fail-text'));
                    $('#bulk_translate_posts_response-' + $bulkButton.attr('post-type')).html(JSON.stringify(response.livewordsApi.errors));
                } else {
                    // Server was reached.
                    // Test if for LiveWords code 200 response
                    if (response.livewordsApi.response.code == 200) {
                        $bulkButton.attr('disabled', true).attr('value', $bulkWrapper.attr('done-text'));
                    } else {
                        $bulkButton.attr('disabled', false).attr('value', $bulkWrapper.attr('fail-text'));
                        $('#bulk_translate_posts_response-' + $bulkButton.attr('post-type')).html(response.livewordsApi.response.message);
                    }
                }


            }).fail(function () {
                // Ajax something in the plugin went wrong.
                $bulkButton.attr('disabled', false).attr('value', $bulkWrapper.attr('fail-text'));
            });

        });


        /**
         * On the settings page
         * Set the testing button to disabled if the entered settings are not from the database
         */
        $(
            'input[name=\'livewords_settings[livewords_text_field_api_url]\'], ' +
            'input[name=\'livewords_settings[livewords_text_field_account_domain]\'], ' +
            'input[name=\'livewords_settings[livewords_text_field_api_key]\']').change(function (event) {
            $('#test_connection').attr('disabled', true).attr('value', 'Save new settings before testing API connection');
        });

        /**
         * On admin edit post page.
         * This is the translate post button.
         */
        var $livewordsTranslateButton = $('#livewords_translate');
        $('#livewords_translate').click(function (event) {
            event.preventDefault(); // cancel default behavior

            // Disable the button. Don't send the request twice
            $livewordsTranslateButton.attr('disabled', true).attr('value', $livewordsTranslateButton.attr('sending-text'));

            var postId = $livewordsTranslateButton.attr('postid');
            var data = {
                'action': 'send_post_for_translation',
                'postId': postId
            };
            $.post(ajaxurl, data, function (response) {

            })
            .done(function (response) {
                // The server has never been reached.
                if (response.livewordsApi.errors) {
                    $livewordsTranslateButton.attr('value', $livewordsTranslateButton.attr('error-occurred-text'));
                    $livewordsTranslateButton.after(JSON.stringify(response.livewordsApi.errors));
                } else {
                    // Server was reached.
                    // Test if for LiveWords code 200 response
                    if (response.livewordsApi.response.code == 200) {
                        // Reflect status in the button
                        $livewordsTranslateButton.attr('value', $livewordsTranslateButton.attr('translation-in-progress-text'));
                        // Update status text
                        $('.livewords-translation-status-text').html(livewords_translation_status_array[2]);
                    } else {
                        $livewordsTranslateButton.attr('value', $livewordsTranslateButton.attr('error-occurred-text'));
                        $livewordsTranslateButton.after(JSON.stringify(response.livewordsApi.response));
                    }
                }
            })
            .fail(function () {
                // This is an 500 error in the plugin code
                $livewordsTranslateButton.attr('value', $livewordsTranslateButton.attr('error-occurred-text'));
            });
        });

        /**
         * On admin edit post page.
         * Handle onchange 'translate in' checkboxes.
         * If all are unchecked, disable the translate button
         *
         */
        var $livewordsTranslationOptionsListInputs = $('#livewords-translation-options-list input');
        $livewordsTranslationOptionsListInputs.change(function (event) {

            $livewordsTranslateButton.attr('disabled', true).attr('value', $livewordsTranslateButton.attr('save-first-text'));

            // if($('#livewords-translation-options-list input:checked').length == 0) {
            //     $livewordsTranslateButton.attr('disabled', true).attr('value', 'No target languages selected');
            // } else {
            //     $livewordsTranslateButton.attr('disabled', false).attr('value', 'Translate');
            // }
        });

        $('.livewords-translation-log-label').click(function(){
            $('.livewords-translation-log-label, .livewords-translation-log').toggleClass('open');
        });

    });


})( jQuery );
