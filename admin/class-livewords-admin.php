<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://lazzo.nl
 * @since      1.0.0
 *
 * @package    Livewords
 * @subpackage Livewords/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 *
 * @package    Livewords
 * @subpackage Livewords/admin
 * @author     Daco de la Bretonière <daco@mac.com>
 */
class Livewords_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Livewords_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Livewords_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/livewords-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Livewords_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Livewords_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/livewords-admin.js', array( 'jquery' ), $this->version, false );

	}


    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */

    public function add_plugin_admin_menu() {

        /*
         * Add a settings page for this plugin to the Settings menu.
         *
         * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
         *
         *        Administration Menus: http://codex.wordpress.org/Administration_Menus
         *
         */
        add_options_page( 'LiveWords Translation', 'LiveWords', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page')
        );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */

    public function add_action_links( $links ) {
        /*
        *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
        */
        $settings_link = array(
            '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __('Settings', $this->plugin_name) . '</a>',
        );
        return array_merge(  $settings_link, $links );

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */

    public function display_plugin_setup_page() {

        if ( function_exists('icl_object_id') ) {
            include_once( 'partials/livewords-admin-display.php' );
        } else {
            include_once( 'partials/livewords-admin-display-no-wpml.php' );
        }

    }


    public function validate($input) {

        $valid = array();

        $valid['account_domain'] = (true||isset($input['account_domain']) && !empty($input['account_domain'])) ? 'hello' : 0;
        $valid['api_key'] = (true||isset($input['api_key']) && !empty($input['api_key'])) ? 1: 0;
        $valid['api_version'] = (true||isset($input['api_version']) && !empty($input['api_version'])) ? 1 : 0;

        $valid['account_domain'] = esc_url($input['account_domain']);
//        $valid['api_key'] = sanitize_text_field($input['api_key']);
//        $valid['api_version'] = $input['api_version'];

        return $valid;
    }

    /**
     * Register the API settings section
     */
    public function options_update() {

        register_setting( 'liveWordsOptionsPage', 'livewords_settings' );
        register_setting( 'liveWordsCustomFieldsOptions', Livewords::WP_OPTIONS_LIVEWORDS_CUSTOM_FIELDS_SETTINGS );

        add_settings_section(
            'livewords_liveWordsOptionsPage_section',
            __( 'LiveWords API settings', 'wordpress' ),
            array($this, 'livewords_settings_section_callback'),
            'liveWordsOptionsPage'
        );
//
//        add_settings_section(
//            'livewords_liveWordsCustomFieldsOptions_section',
//            __( 'LiveWords Custom Fields', 'wordpress' ),
//            array($this, 'livewords_liveWordsCustomFieldsOptions_section_callback'),
//            'liveWordsCustomFieldsOptions'
//        );

        add_settings_field(
            'api_url',
            __( 'Api url', 'wordpress' ),
            array($this, 'livewords_text_field_api_url_render'),
            'liveWordsOptionsPage',
            'livewords_liveWordsOptionsPage_section'
        );

        add_settings_field(
            'account_domain',
            __( 'Account domain', 'wordpress' ),
            array($this, 'livewords_text_field_account_domain_render'),
            'liveWordsOptionsPage',
            'livewords_liveWordsOptionsPage_section'
        );
        add_settings_field(
            'api_key',
            __( 'LiveWords API ID', 'wordpress' ),
            array($this, 'livewords_text_field_api_key_render'),
            'liveWordsOptionsPage',
            'livewords_liveWordsOptionsPage_section'
        );

    }

    function livewords_settings_section_callback(  ) {

        echo __( 'Configure the LiveWords API connection', 'wordpress' );

    }
    function livewords_liveWordsCustomFieldsOptions_section_callback(  ) {

        echo __( 'Configure the LiveWords Custom Fields', 'wordpress' );

    }

    /**
     * Render api url setting
     */
    function livewords_text_field_api_url_render() {
        $options = get_option( 'livewords_settings' );
        ?>
        <input
                type="text"
                class="regular-text"
                name="livewords_settings[livewords_text_field_api_url]"
                value="<?php echo $options["livewords_text_field_api_url"]; ?>">
        <?php
    }

    /**
     * Render account domain setting
     */
    function livewords_text_field_account_domain_render(  ) {

        $options = get_option( 'livewords_settings' );
        ?>
        <input 
                type="text" 
                class="regular-text" 
                name="livewords_settings[livewords_text_field_account_domain]" 
                value="<?php echo $options["livewords_text_field_account_domain"]; ?>">
        <?php
    }

    /**
     * Render api key setting
     */
    function livewords_text_field_api_key_render() {

        $options = get_option( 'livewords_settings' );
        ?>
        <input
                type="password"
                class="regular-text"
                name="livewords_settings[livewords_text_field_api_key]"
                value="<?php echo $options["livewords_text_field_api_key"]; ?>">
        <?php
    }



    /**
     * Register the metabox on post/page edit views.
     * Check for WPML existance
     *
     * @since 1.0.0
     */
    public function metabox_register() {

        if ( function_exists( 'icl_object_id' ) ) {

            $post_types = get_post_types( array( 'public' => true ) );


            // Cycle through all post types.
            foreach ( $post_types AS $post_type ) {
                // Ignore attachment post type.
                if ( 'attachment' === $post_type ) {
                    continue;
                }

                // Register metabox.
                add_meta_box(
                    'livewords-translation-box',
                    __( 'LiveWords Translation', 'livewords' ),
                    array( $this, 'metabox_content' ),
                    $post_type,
                    'side',
                    'high'
                );
            }
        }
    }

    /**
     * Function for rendering the checkboxes on a edit post page.
     * With the checkboxes you can determine which target languages are requested with 'translate'
     */
    public function render_post_target_language_options( $postNew = false ) { ?>

        <div class="livewords-translation-options"<?php echo ( $postNew ) ? ' style="display: none;"' : '' ?>>
            <label><strong>Translate in the languages</strong></label>

            <?php
            // Get all the current language targets
            $targets = Livewords_RequestWrapper::getTargetLanguagesArray(); ?>

            <?php
            // Current selected languages if any.
            if ($postNew) {
                $current_post_options = Livewords_RequestWrapper::getTargetLanguagesArray();
            } else {
                $current_post_options = Livewords_RequestWrapper::getPostSelectedTargetLanguages( get_the_ID() );
            }
            ?>
            <?php
            // Get an array with all the info. Needed for the flag to display
            $languages_info = Livewords_RequestWrapper::getTargetLanguagesFullArray() ?>
            <?php
            /**
             * Show all target languages as an option
             */
            ?>
            <ul id="livewords-translation-options-list" class="form-no-clear">

                <?php
                // Loop through all the target languages
                foreach ( $targets as $target ) : ?>
                    <li>
                        <label class="selectit">
                            <?php
                            // If has options, use that. If not, use default = unchecked
                            ?>
                            <input <?php $current_post_options ? checked( in_array( $target, $current_post_options ), true ) : checked( false, true ) ?>
                                    value="<?php echo $target; ?>"
                                    type="checkbox"
                                    name="<?php echo Livewords_RequestWrapper::WP_POST_FIELD_SELECTED_TARGET_LANGUAGES ?>[]"
                                    id="livewords-post-target-languages-<?php echo $target; ?>">
                            <img src="<?php echo $languages_info[ $target ]['country_flag_url']; ?>">
                            <?php echo $target; ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>

        </div>

    <?php }

    /**
     * Render a metabox on the edit post page
     *
     *
     *
     */
    public function metabox_content() {

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';

        // Is this the target language
        if (Livewords_RequestWrapper::getDefaultLanguage() == Livewords_RequestWrapper::getWpmlLanguageCode(get_the_ID())) {

            // Show metabox if posttype is translatable
            $translatable_post_types = apply_filters( 'wpml_translatable_documents', null );

            // Is this set to translatable?
            if ( $translatable_post_types[ get_post_type() ] ) {

                /*
                 * The translate button. Only active is there's at least one target language selected.
                 */
                $current_post_options = Livewords_RequestWrapper::getPostSelectedTargetLanguages( get_the_ID() );
                if ($current_post_options) {
                    // The submit button with extra attributes for js interface
                    submit_button( 'Translate', 'secondary', 'livewords_translate', true,
                        array(
                            'postId'                       => get_the_ID(),
                            'sending-text'                 => 'Sending...',
                            'translation-in-progress-text' => 'Translation in progress',
                            'error-occurred-text'          => 'An error occurred!',
                            'save-first-text'              => 'Save the new settings first'
                        ) );
                } else {
                    submit_button( 'No target language selected', 'secondary', 'livewords_translate', true,
                        array(
                            'disabled'        => true,
                            'save-first-text' => 'Save the new settings first'
                        ) );
                }

                /**
                 * Show translation status.
                 */
                $translation_status = get_post_meta( get_the_ID(), Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_STATUS, true );

                // Todo fix this so that the text comes from the php class DRY.
                $translation_status_text_array = array(
                    '',
                    'Untranslated',
                    'In progress',
                    'Incomplete',
                    'Translated',
                    'Modified'
                );
                ?>
                <script type="application/javascript">
                    livewords_translation_status_array = <?php echo json_encode($translation_status_text_array); ?>
                </script>
                <div class="livewords-translation-status">
                    <p class="livewords-translation-status-label"><label><strong>Translation status</strong></label></p>
                    <p class="livewords-translation-status-text">
                        <?php
                        if ( $translation_status ) {
                            echo $translation_status_text_array[ $translation_status ];
                        } else {
                            echo $translation_status_text_array[1];
                        }
                        ?>
                    </p>
                </div>
                <?php
                // User selectable language targets
                $this->render_post_target_language_options();
                ?>
                <div class="livewords-translation-log-label"><label><strong>Translation log</strong></label><span class="toggle-indicator" aria-hidden="true"></span></div>
                <div class="livewords-translation-log">
                <?php
                // Translation log
                echo get_post_meta( get_the_ID(), Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_LOG, true );
                ?></div><?php
            } else {
                printf( "Post type %s is not yet translatable.", get_post_type() );
            };

        } else {
            // Can we get a wpml object id, then this is a target language
            $transID = apply_filters( 'wpml_object_id', get_the_ID(), get_post_type(), false, Livewords_RequestWrapper::getDefaultLanguage());
            global $pagenow;
            if ('post-new.php' == $pagenow) {
                printf( "This is an unsaved post." );
                $this->render_post_target_language_options(true);
            } elseif (Livewords_RequestWrapper::getDefaultLanguage() != apply_filters( 'wpml_current_language', NULL )) {
                printf( "This is a target language. See <a href=\"%s\">source</a> language for more options.", get_edit_post_link( $transID ) );
            } else {
                // Something else is going on. Usually this post is not saved yet properly with a title or something like that
                printf( "Post is not ready yet for LiveWords translation.");
            }
        }
    }

    /**
     * @param $channel string post type or taxonomy string
     * @param $bulk boolean Set to true if this is a bulk request
     *
     * @return string
     */
    public function get_livewords_api_url( $channel, $bulk = false ) {
        // Get this API URL
        $options = get_option( Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS );
        // These channels always exist
        $standardChannels = array( 'post', 'page', 'taxonomy' );

        // If not matching, post to default channel.
        if ( ! in_array( $channel, $standardChannels ) ) {
            $channel = 'default';
        }
        // If this is bulk request, append bulk string to the endpoint
        $bulkString = '';
        if ( $bulk ) {
            $bulkString = '/_bulk';
        }
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';
        return $options[ Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS_API_URL ] . '/' . $channel . '/items' . $bulkString;
    }

    /**
     * @param $channel string
     * @param $body string
     * @param $bulk boolean set to true if this is a bulk request
     * @return array|WP_Error
     */
    public function post_to_livewords( $channel, $body = '', $bulk = false ) {
        $options = get_option( Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS );
        $args    = array(
            'body' => $body,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $options[ Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS_ACCOUNT_DOMAIN ] . ':' . $options[ Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS_API_KEY ] ),
                'Content-Type' => 'text/xml'
            ),
            'timeout' => 20
        );
        return wp_remote_post( $this->get_livewords_api_url($channel, $bulk), $args );
    }

    /**
     * @param $post_id
     * Handles
     * - Posts translation status
     * - Optional target languages
     */
    public function handle_save_post( $post_id, $post, $update ) {
        // If this is just a revision, don't send the email.
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';

        $postedTargetLanguages = $_POST[Livewords_RequestWrapper::WP_POST_FIELD_SELECTED_TARGET_LANGUAGES];

        $invertedSelectedTargetLanguages = array_diff( Livewords_RequestWrapper::getTargetLanguagesArray(), is_array( $postedTargetLanguages ) ? $postedTargetLanguages : array() );
        // Save
        update_post_meta(
            $post_id,
            Livewords_RequestWrapper::WP_POST_META_FIELD_SELECTED_TARGET_LANGUAGES,
            $invertedSelectedTargetLanguages
        );

        // Update post meta to not translated or modified.
        $currentStatus = get_post_meta($post_id, Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_STATUS, true);
        if (!$currentStatus || $currentStatus == Livewords_RequestWrapper::TRANSLATION_STATUS_NOT_TRANSLATED) {
            update_post_meta( $post_id, Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_STATUS, Livewords_RequestWrapper::TRANSLATION_STATUS_NOT_TRANSLATED);
        } else {
            update_post_meta( $post_id, Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_STATUS, Livewords_RequestWrapper::TRANSLATION_STATUS_MODIFIED);
        }

    }



// ***************** Ajax actions from WP_AJAX action

    /**
     * Kicks in from WP_AJAX action
     * Test API connection by trying to get a 200 response from LiveWords API
     */
    public function test_api_connection() {
        $options = get_option( Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS );

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $options[ Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS_ACCOUNT_DOMAIN ] . ':' . $options[ Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS_API_KEY ] ),
                'Content-Type' => 'text/xml'
            )
        );
        $response = wp_remote_request( $options[Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS_API_URL], $args );

        $responseBody = wp_remote_retrieve_body( $response );
        if (wp_remote_retrieve_response_code( $response ) != 200 || ( empty($responseBody) )) {
            echo "<div class='notice notice-error'><p>";
            echo "<p>Cannot connect to the API:</p>";
            echo json_encode($response);
            echo "</p></div>";
        } else {
            echo "<div class='notice notice-success'><p>";
            echo "Connection successful!";
            echo "</p></div>";
        }

        wp_die();
    }

    /**
     * Kicks in from WP_AJAX action
     * Get post xml by postId and post this to the LiveWords API
     */
    public function send_post_for_translation() {

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';

        $postId = intval( $_POST['postId'] );

        $postType = get_post_type($postId);

        // Get the selected target languages
        $selectedTargets = Livewords_RequestWrapper::getPostSelectedTargetLanguages( $postId );
        // Compose array(id => targets) format.
        $postIds = array($postId => $selectedTargets);
        // Create xml
        include('xml.php');
        $xml = xml_livewords(array(
                'postIds' => $postIds
        ));

        // Compose response for the browser.
        $pluginResponse = array(
            'plugin' => array(
                'requestUrl'  => $this->get_livewords_api_url( $postType, false ),
                'requestBody' => $xml
            )
        );

        // Send to LiveWords
        $response = $this->post_to_livewords($postType, $xml);

        $pluginResponse['livewordsApi'] = $response;

        // There's no response. Something went wrong (server down, not valid certificate etc etc)
        if (is_wp_error( $response )) {

        } else {
            // There's a response from LiveWords

            if (wp_remote_retrieve_response_code( $response ) != 200) {

            } else {
                // Log new status in the post
                Livewords_RequestWrapper::setPostTranslationRequestedStatus( $postId );
            }
        }
        Livewords_RequestWrapper::logMessage($pluginResponse);
        wp_send_json($pluginResponse);
    }

    /**
     * Kicks in from WP_AJAX action
     * Bulk translate all the posts from specific type
     */
    public function bulk_translate_posts() {

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';

        $postType = $_POST['post-type'];
        $forced = !!$_POST['forced'];

        include('xml.php');

        $postIds = Livewords_RequestWrapper::getPostIdsForSendingBulk( $postType, $forced);

        $xml = xml_livewords(array(
            'postIds' => $postIds
        ));

        // Compose response for the browser.
        $pluginResponse = array(
            'plugin' => array(
                'requestUrl'  => $this->get_livewords_api_url( $postType, true ),
                'requestBody' => $xml,
                'bulkCount' => count($postIds)
            )
        );

        // Send to LiveWords
        $response = $this->post_to_livewords($postType, $xml, true);

        $pluginResponse['livewordsApi'] = $response;

        if (wp_remote_retrieve_response_code( $response ) != 200) {

        } else {
            // If successful, set status = in progress
            foreach ($postIds as $postId => $_) {
                Livewords_RequestWrapper::setPostTranslationRequestedStatus( $postId );
            }
        }
        Livewords_RequestWrapper::logMessage( sprintf( "New bulk translate request send to LiveWords API \n\n%s\n\n", print_r( $response, true ) ) );
        wp_send_json($pluginResponse);

    }
    /**
     * Kicks in from WP_AJAX action
     * Posts all taxonomies to the LiveWords API on the livewords-taxonomies channel
     */
    public function translate_taxonomies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';
        Livewords_RequestWrapper::logMessage("Sending taxonomies to LiveWords.");
        include('xml.php');

        $xml = xml_livewords(array(
            'get_taxonomies' => true
        ));

        // Compose response for the browser.
        $pluginResponse = array(
            'plugin' => array(
                'requestUrl'  => $this->get_livewords_api_url( 'taxonomy', false ),
                'requestBody' => $xml
            )
        );

        $response = $this->post_to_livewords('taxonomy', $xml);

        $pluginResponse['livewordsApi'] = $response;

        // There's no response. Something went wrong (server down, not valid certificate etc etc)
        if (is_wp_error( $response )) {

        } else {
            // There's a response from LiveWords

            if (wp_remote_retrieve_response_code( $response ) != 200) {

            } else {

                // Log new status in the post
                Livewords_RequestWrapper::logMessage("Done sending taxonomies to the server and status is okay.");
            }
        }
        Livewords_RequestWrapper::logMessage($pluginResponse);
        wp_send_json($pluginResponse);

    }


}