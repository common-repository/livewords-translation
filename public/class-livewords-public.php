<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://lazzo.nl
 * @since      1.0.0
 *
 * @package    Livewords
 * @subpackage Livewords/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Livewords
 * @subpackage Livewords/public
 * @author     Daco de la Bretonière <daco@mac.com>
 */
class Livewords_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

//		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/livewords-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

//		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/livewords-public.js', array( 'jquery' ), $this->version, false );

	}

	public function switch_action() {
        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';

	    Livewords_RequestWrapper::logMessage("\n\n*** Callback request received ****");

	    // Check if simple xml lib can be used
	    if (!extension_loaded( 'simplexml' )) {
            Livewords_RequestWrapper::logMessageAndDie("The LiveWords plugin has a dependency on SimpleXML PHP extension for now. This could easily be extended to other libs in the future. Please check admin/parsers.php");
        }

        // See if the requested locale is available
	    $language = $_REQUEST['language'];
        if ( ! $this->isRequestedLocaleAvailable( $language ) ) {
            Livewords_RequestWrapper::logMessage( sprintf( "Requested locale '%s' is not available.", $language ) );
            return;
        }


        //authenticate or 401
        //validate input or 400
        //load post or 404 post not found
        //load wpml copy for translation or 404 locale not found
        //update wpml copy or 500
        //return 200 OK

        //get method
        //get post body
        //get action
        //get language

        $action   = $_REQUEST['action'];
        $method   = $_SERVER['REQUEST_METHOD'];

        $wrapper  = new Livewords_RequestWrapper( $method, $action, $language );

        $wrapper->isRequestAuthentic() or $wrapper::send401Unauthorized();

        $errors = $wrapper->validate();

        Livewords_RequestWrapper::logMessage("Trying to get the requested action:");

        // What action is to be performed
        $requestedAction = $wrapper->getRequestedAction();

        Livewords_RequestWrapper::logMessage(sprintf("Requested action finished, requested action is '%s'", $requestedAction));

        if ($requestedAction == 'translate_taxonomies') {
            $this->lw_translate_taxonomies();
        }
        if ($requestedAction == 'translate_posts') {
            $this->lw_translate_post();
        }

        if ( count( $errors ) != 0 ) {
            //get first error and exit
            http_response_code( $errors[0]['code'] );
            echo "<div class='notice notice-error'><p>";
            echo $errors[0]['message'];
            echo "</p></div>";

            die();

        } else {
            //we are in business
            http_response_code( 200 );


//            $wrapper->saveTranslation();

            echo "<div class='notice notice-success'><p>";
            echo "Translation Request received";
            echo "</p></div>";
        }
    }

    /**
     *
     * Check if requested locale is currently made available by WPML
     * @param $locale
     *
     * @return bool
     */
	private function isRequestedLocaleAvailable($locale) {

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';

        if ($locale == '') {
	        Livewords_RequestWrapper::logMessageAndDie("Please provide a locale");
        }

        // Check to see if the requested locale differs from the default locale
        if (apply_filters('wpml_default_language', NULL ) == $locale) {
            Livewords_RequestWrapper::logMessageAndDie( sprintf( "The requested locale '%s' is the same as the default locale", $locale ) );
        }

        $currentWPMLLanguagesArray = apply_filters( 'wpml_active_languages', NULL);

        if(!key_exists($locale, $currentWPMLLanguagesArray)) {
            Livewords_RequestWrapper::logMessageAndDie( sprintf( "The requested locale %s is currently not available in WPML. Available locales are %s", $locale, print_r( $currentWPMLLanguagesArray, true ) ) );
        } else {
            return true;
        }
    }

    /**
     * Called by Switch Action
     */
	private function lw_translate_post() {

        $language = $_REQUEST['language'];

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';

		//authenticate or 401
		//validate input or 400
		//load post or 404 post not found
		//load wpml copy for translation or 404 locale not found
		//update wpml copy or 500
		//return 200 OK

		//get method
		//get post body
		//get action
		//get language

		$action   = $_REQUEST['action'];
		$method   = $_SERVER['REQUEST_METHOD'];
		$wrapper  = new Livewords_RequestWrapper( $method, $action, $language );

		$wrapper->isRequestAuthentic() or $wrapper::send401Unauthorized();

        $errors = $wrapper->validate();

		if ( count( $errors ) != 0 ) {
			//get first error and exit
			http_response_code( $errors[0]['code'] );
			echo "<div class='notice notice-error'><p>";
			echo $errors[0]['message'];
			echo "</p></div>";

			die();

		} else {
			//we are in business
			http_response_code( 200 );
            Livewords_RequestWrapper::logMessage("Wrapper created. Ready to save translation.");
			$wrapper->saveTranslation();

			echo "<div class='notice notice-success'><p>";
			echo "Translation Request received";
			echo "</p></div>";
		}

	}

    /**
     * Called by Switch Action
     */
    private function lw_translate_taxonomies() {

        $language = $_REQUEST['language'];

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';

        $wrapper = new Livewords_RequestWrapper( 'POST', 'lw_translate_taxonomies', $language);

        $wrapper->saveTranslatedTaxonomies();

    }

}