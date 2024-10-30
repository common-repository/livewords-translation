<?php
/**
 * File: class-livewords-requestwrapper.php
 *
 * @category
 * @package
 * @author    Wieger Uffink <wieger@lazzo.nl>
 * @date      20/02/17
 * @copyright 2017 Lazzo | www.lazzo.nl
 * @license   Proprietary license | http://www.lazzo.nl
 * @version   subversion: $Id:$
 */


/**
 * class Livewords_RequestWrapper
 *
 * @category
 * @package
 * @author   Wieger Uffink <wieger@lazzo.nl>
 * @license  Proprietary license | http://www.lazzo.nl
 * @version  subversion: $Id:$
 *
 */
class Livewords_RequestWrapper {

    const HTTP_HEADER_X_TIMESTAMP = 'X-Timestamp';
    const HTTP_HEADER_X_TOKEN = 'X-Token';
    const HTTP_HEADER_X_SIGNATURE = 'X-Signature';

    const TRANSLATION_STATUS_NOT_TRANSLATED = 1;
    const TRANSLATION_STATUS_IN_PROGRESS = 2;
    const TRANSLATION_STATUS_INCOMPLETE = 3;
    const TRANSLATION_STATUS_TRANSLATED = 4;
    const TRANSLATION_STATUS_MODIFIED = 5;

    // Meta is the post database field
    // Field is the name that's been posted.
    const WP_POST_META_FIELD_SELECTED_TARGET_LANGUAGES = '_livewords_post_selected_target_languages';
    const WP_POST_FIELD_SELECTED_TARGET_LANGUAGES = 'livewords_post_selected_target_languages';

    const WP_POST_META_FIELD_TRANSLATION_STATUS = '_livewords_translation_status';
    const WP_POST_META_FIELD_REQUESTED_TARGET_LANGUAGES = '_livewords_requested_target_languages';

    const WP_POST_META_FIELD_TRANSLATION_LOG = '_livewords_translation_log';

    /**
     * @var array list of available callable actions
     */
    private $availableActions = array(
        'translate_posts',
        'translate_taxonomies',
    );

    /**
     * @var string HTTP REQUEST METHOD name
     */
    private $method;

    /**
     * @var string action called by livewords api
     * @todo define discrete values as constants or enumeration for validation
     */
    private $action;

    /**
     * @var string XML body
     */
    private $body;

    /**
     * @var SimpleXMLElement
     */
    private $document;

    /**
     * @var array XML body as array
     */
    private $parsedBody;

    /**
     * @var string language code of the translation
     */
    private $language;

    /**
     * @var array any errors found during validation
     */
    private $errors = array();

    /**
     * @var array relevant headers sent by Livewords to authenticate and sign translation request
     */
    private $headers = [
        self::HTTP_HEADER_X_TIMESTAMP => '',
        self::HTTP_HEADER_X_TOKEN     => '',
        self::HTTP_HEADER_X_SIGNATURE => ''
    ];

    /**
     * Livewords_RequestWrapper constructor. Reads php://input to get post body,
     * and parses the contents into a SimpleXMLElement
     *
     * @param $method
     * @param $action
     * @param $language
     */
    public function __construct( $method, $action, $language ) {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/parsers.php';

        $this->method = $method;
        $this->action = $action;
        $this->body   = file_get_contents( 'php://input' );

        Livewords_RequestWrapper::logMessage(sprintf("Received raw body \n%s", $this->body));

        // Init parser
        $parser = new LiveWords_XML_Parser();
        // Parse body into an array
        try {
            ///todo handle error properly
            $this->parsedBody = $parser->parse( $this->body );

        } catch ( Exception $e ) {
            Livewords_RequestWrapper::logMessage("Failed to parse request body!");
            Livewords_RequestWrapper::logMessage($e);
        };

        $this->parseHeaders( $_SERVER );

        $this->language = $language;

        /*      $this->id = $params['id'];
                $this->post = $params['post'];
                $this->action = $params['action'];
                $this->locale = $params['locale'];*/
    }

    /**
     * Log something to system.log
     * @param $message mixed
     */
    public static function logMessage( $message ) {

        $file = plugin_dir_path( dirname( __FILE__ ) ) . 'system.log';
        $maxFilesize = 4000000; // 4meg

        if ( filesize( $file ) > $maxFilesize ) {
            $handle = fopen($file, 'r+');
            echo ftruncate( $handle, $maxFilesize );
            fclose($handle);
        }

        if ( is_string( $message ) ) {
            error_log( sprintf( "%s > %s\n", date( 'c' ), $message ), 3, $file );
        } else {
            error_log( sprintf( "%s > %s\n", date( 'c' ), print_r( $message, true ) ),3, $file );
        }


    }

    /**
     * Log the error and send response with 200 OK to stop LiveWords requests.
     * @param $message
     *
     */
    public static function logMessageAndDie( $message ) {

        Livewords_RequestWrapper::logMessage($message);
        wp_die(sprintf("%s > %s\n", date( 'c' ), print_r($message, true)), 'Error', 200);

    }

    /**
     *
     * @return array
     */
    public static function allowedMetaTermsArray( ) {
        return get_option( Livewords::WP_OPTIONS_LIVEWORDS_CUSTOM_FIELDS_SETTINGS );
    }

    /**
     * @param $postId
     *
     * @return bool
     */
    public static function isPostInDefaultLanguage( $postId ) {
        return Livewords_RequestWrapper::getDefaultLanguage() === Livewords_RequestWrapper::getWpmlLanguageCode( $postId );
    }

    /**
     * @return Array of post types.
     */
    public static function getTranslatablePostTypes() {
        $post_types = apply_filters( 'wpml_translatable_documents', null );
        foreach ($post_types as $k => $post_type) {
            $post_types[$k] = $k;
        }
        return $post_types;
    }

    /**
     * @return string language string.
     */
    public static function getDefaultLanguage() {
        return apply_filters('wpml_default_language', NULL );
    }

    /**
     * Get the currently user selected languages.
     * @param $post_id
     *
     * @return array ( [0] => nl [1] => fr )
     */
    public static function getPostSelectedTargetLanguages( $post_id ) {

        $invertedSelected = get_post_meta( $post_id, Livewords_RequestWrapper::WP_POST_META_FIELD_SELECTED_TARGET_LANGUAGES, true );
        if (!is_array($invertedSelected)) {
            $invertedSelected = array();
        }
        return array_diff(Livewords_RequestWrapper::getTargetLanguagesArray(), $invertedSelected);
    }

    /**
     * This returns the full WPML array
     * * @return array.
     */
    public static function getWPMLActiveLanguagesFullArray() {
        return apply_filters( 'wpml_active_languages', NULL);
    }

    /**
     * This returns the full WPML array with the source language omitted
     * * @return array.
     */
    public static function getTargetLanguagesFullArray() {
        $defaultLocale = Livewords_RequestWrapper::getDefaultLanguage();
        $currentWPMLLanguagesArray = Livewords_RequestWrapper::getWPMLActiveLanguagesFullArray();
        unset($currentWPMLLanguagesArray[$defaultLocale]);
        return $currentWPMLLanguagesArray;
    }

    /**
     * @return array [nl,fr,etc].
     */
    public static function getTargetLanguagesArray() {
        $currentWPMLLanguagesArray = Livewords_RequestWrapper::getTargetLanguagesFullArray();
        return array_keys($currentWPMLLanguagesArray);
    }

    /**
     * @return string Imploded languages string.
     */
    public static function getTargetLanguages() {
        $currentWPMLLanguagesArray = Livewords_RequestWrapper::getTargetLanguagesArray();
        return implode(',', $currentWPMLLanguagesArray);
    }

    /**
     * @param int
     * @return array
     */
    public static function getWpmlLanguageDetails( $postId ) {
        $elementType        = get_post_type( $postId );
        $get_language_args  = array( 'element_id' => $postId, 'element_type' => $elementType );
        return apply_filters( 'wpml_element_language_details', null, $get_language_args );
    }

    /**
     * @param int
     * @return string
     */
    public static function getWpmlLanguageCode( $postId ) {
      $details = Livewords_RequestWrapper::getWpmlLanguageDetails( $postId );
      return $details->language_code;
    }


    /**
     * retrieves values for keys defined in @see{$this->headers}, either in global $_SERVER array or in parameter passed
     *
     * @param array $parameters
     */
    protected function parseHeaders( array $parameters = null ) {

        if ( is_null( $parameters ) ) {
            $parameters = $_SERVER;
        }
        foreach ( $this->headers as $key => $value ) {
            $phpizedHeaderKey = 'HTTP_' . strtoupper( str_replace( '-', '_', $key ) );

            if ( array_key_exists( $phpizedHeaderKey, $parameters ) ) {
                $this->headers[ $key ] = $parameters[ $phpizedHeaderKey ];
            }

        }

    }

    public function getRequestedAction(){

        $action = $this->parsedBody['action'];

        if (!$action || '' == $action) {
            Livewords_RequestWrapper::logMessageAndDie("No action could be found.");
        }

        if (!in_array($action, $this->availableActions)) {
            Livewords_RequestWrapper::logMessageAndDie(sprintf("The requested action '%s' is not available", $action));
        }

        return $action;

    }

    /**
     * Sends 401 Response and terminates execution
     */
    public static function send401Unauthorized() {
        http_response_code( 401 );
        header( 'WWW-Authenticate', 'LiveWords Custom Authentication Scheme' );
        exit();
    }


    /**
     *  Set status after request for translation is successfully send to LiveWords.
     *  Record requested target languages
     */
    public function setPostTranslationRequestedStatus( $postId ) {

        // Which languages are requested for translation?
        $selectedTargetLanguages = Livewords_RequestWrapper::getPostSelectedTargetLanguages( $postId );

        // Record requested target languages.
        update_post_meta( $postId, Livewords_RequestWrapper::WP_POST_META_FIELD_REQUESTED_TARGET_LANGUAGES, $selectedTargetLanguages);

        // Update the status to in progress.
        update_post_meta( $postId, Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_STATUS, Livewords_RequestWrapper::TRANSLATION_STATUS_IN_PROGRESS);

        // Log this action
        $statusText = get_post_meta( $postId, Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_LOG, true );

        // Cap string
        $statusText = substr( $statusText, 0, 1000 );
        $statusText = sprintf( "%s ID %s to lw in '%s'.\n", date( 'c' ), $postId, implode( ',', Livewords_RequestWrapper::getPostSelectedTargetLanguages( $postId ) ) ) . $statusText;
        update_post_meta( $postId, Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_LOG, $statusText );

    }



    /**
     * Set the new translation status and log.
     */
    public function setPostTranslationReceivedStatus() {
        $loadedPostId = $this->getLoadedPostId();

        // What languages where requested
        $requestedTargetLanguages = get_post_meta( $loadedPostId, Livewords_RequestWrapper::WP_POST_META_FIELD_REQUESTED_TARGET_LANGUAGES, true);
        Livewords_RequestWrapper::logMessage(sprintf("ID %s has still pending for translation: %s", $loadedPostId, print_r($requestedTargetLanguages, true)));

        // Remove current language and store again.
        if (is_array($requestedTargetLanguages)) {
            if ( ( $key = array_search( $this->language, $requestedTargetLanguages ) ) !== false ) {
                unset( $requestedTargetLanguages[ $key ] );
            }
            update_post_meta( $loadedPostId, Livewords_RequestWrapper::WP_POST_META_FIELD_REQUESTED_TARGET_LANGUAGES, $requestedTargetLanguages );
        }

        // Set status text
        // If still pending targets, set incomplete
        if ($requestedTargetLanguages) {
            update_post_meta( $loadedPostId, Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_STATUS, Livewords_RequestWrapper::TRANSLATION_STATUS_INCOMPLETE);
            Livewords_RequestWrapper::logMessage(sprintf("New status of post %s id %s.", $loadedPostId, Livewords_RequestWrapper::TRANSLATION_STATUS_INCOMPLETE));
        } else {
            // Otherwise, we're complete
            update_post_meta( $loadedPostId, Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_STATUS, Livewords_RequestWrapper::TRANSLATION_STATUS_TRANSLATED);
            Livewords_RequestWrapper::logMessage(sprintf("New status of post %s id %s.", $loadedPostId, Livewords_RequestWrapper::TRANSLATION_STATUS_TRANSLATED));
        }

        // Log action
        $statusText = get_post_meta($loadedPostId, $this::WP_POST_META_FIELD_TRANSLATION_LOG, true);
        // Cap string
        $statusText = substr($statusText, 0,1000);
        $statusText = sprintf("%s Post id %s translated in '%s'. Still pending for translation '%s'\n", date('c'), $loadedPostId, $this->language, implode(',', $requestedTargetLanguages)) . $statusText;
        update_post_meta( $loadedPostId, $this::WP_POST_META_FIELD_TRANSLATION_LOG, $statusText);
        Livewords_RequestWrapper::logMessage(sprintf("Post id %s translated in '%s'. Still pending for translation '%s'.", $loadedPostId, $this->language, implode(',', $requestedTargetLanguages)));
    }

    /**
     * @param $postId
     *
     * @return bool
     */
    public function isPostValidPostType( $postId ) {

        $postType = get_post_type( $postId );
        $invalidPostTypes = array(
            'attachment',
            'revision',
            'nav_menu_item',
            'custom_css',
            'customize_changeset',
            'action',
            'author',
            'order',
            'theme'
        );

        return !in_array($postType, $invalidPostTypes);
    }

    /**
     * @param $postId
     *
     * @return bool
     */
    public function isPostTranslatable( $postId ) {
        $translatable_post_types = apply_filters( 'wpml_translatable_documents', null );
        $postType = get_post_type( $postId );

        return key_exists($postType, $translatable_post_types);
    }

    /**
     * @param $taxId integer
     *
     * @return bool
     */
    public function isTaxonomyTranslatable( $taxId ) {
        // This will always return true. It seems that WPML has no comfortable way to tell if a taxonomy is translatable.
        // This will cause no problem, because the plugin handles the received translation properly: it'll just continue if the received translation is not translatable.
        return true;
        //        global $sitepress;
        //        $translatable_taxonomies = apply_filters( 'get_translatable_taxonomies', null );
        //        $term = get_term($taxId);
        //
        //        $wpml_element_type = apply_filters( 'wpml_element_type', $term->taxonomy );
        //        $trid = apply_filters( 'wpml_element_trid', null, $taxId, $wpml_element_type);
        //
        //        Livewords_RequestWrapper::logMessage( sprintf( "** TRANSLATABLE TERM: %s", print_r($term, true)) );
        //        Livewords_RequestWrapper::logMessage( sprintf( "** TRID: %s", print_r($trid, true)) );
        //
        //        return key_exists($term->taxonomy, $translatable_taxonomies) || array_search($term->taxonomy, $sitepress->get_translatable_taxonomies( true )) !== false;
    }


    /**
     *  Update localized post
     *
     */
    public function saveTranslation() {

        //parse meta data in document from livewords namespace elements
        //recreate xml document acceptable by wordpress
        //locate localized post through wpml
        //update post

        $loadedPostId = $this->getLoadedPostId();

        if ( ! $this->isPostValidPostType( $loadedPostId ) ) {
            Livewords_RequestWrapper::logMessageAndDie( sprintf( "ERROR post %s cannot be translated: has invalid post type.", $loadedPostId ) );
        }

        if ( ! $this->isPostTranslatable( $loadedPostId ) ) {
            Livewords_RequestWrapper::logMessageAndDie( sprintf( "ERROR post %s cannot be translated: is not WPML translatable.", $loadedPostId ) );
        }

        Livewords_RequestWrapper::logMessage(sprintf("Loaded post id is %s and requested locale is %s", $loadedPostId, $this->language));

        $duplicatePostForLocale = $this->getWPMLTranslatedPostIdForLocale( $loadedPostId );

        // LiveWords never sends more than one post back
        $post = $this->parsedBody['posts'][0];

        // Todo: this is a bit tricky, to set ID to the value of post_id
        $post['ID'] = $duplicatePostForLocale;
        $post       = wp_slash( $post );

        Livewords_RequestWrapper::logMessage(sprintf("** About to update target post %s", $duplicatePostForLocale));
        wp_update_post( $post );
        Livewords_RequestWrapper::logMessage("** target post updated");

        // Update the post meta
        $meta = $post['postmeta'];
        foreach ( $meta as $meta_value ) {
            update_post_meta( $duplicatePostForLocale, $meta_value['key'], $meta_value['value'] );
        }
        Livewords_RequestWrapper::logMessage("** Post meta updated");

        Livewords_RequestWrapper::logMessage("About to update new translation status.");
        // Handle the new translation status.
        $this->setPostTranslationReceivedStatus();
        Livewords_RequestWrapper::logMessage("Updated translation status.");
    }

    /**
     * Loop over all taxonomies
     */
    public function saveTranslatedTaxonomies() {

        Livewords_RequestWrapper::logMessage(sprintf("Starting taxonomies for lang %s", $this->language));

        // Get the parsed terms.
        $terms = $this->parsedBody['terms'];

        // Loop over all terms and see if it's a WPML source term.
        foreach ( $terms as $term ) {

            $originalTermId       = $term['term_id'];
            $originalTermTaxonomy = $term['term_taxonomy'];

            $originalTermObject = get_term( $originalTermId, $originalTermTaxonomy );

            Livewords_RequestWrapper::logMessage(sprintf( "\n\nThe original term for id %s with taxonomy '%s' is\n\n%s\n", $originalTermId, $originalTermTaxonomy, print_r( $originalTermObject, true ) ));

            // Something went wrong!
            if ( is_wp_error( $originalTermObject ) ) {
                Livewords_RequestWrapper::logMessage($originalTermObject->get_error_message());
                continue;
            }

            // Cannot find the original term by id.
            if ( ! $originalTermObject ) {
                Livewords_RequestWrapper::logMessage(sprintf('Cannot find original term with id %s', $originalTermId));
                continue;
            }

            // Get element’s ID in the in the specified language.
            $translatedTermId = apply_filters( 'wpml_object_id', $originalTermId, $originalTermTaxonomy, false, $this->language );
            Livewords_RequestWrapper::logMessage(sprintf( "The translated term id in language '%s' for id '%s' is id '%s'\n\n", $this->language, $originalTermId, $translatedTermId ));

            if ( $translatedTermId === $term['term_id'] ) {
                Livewords_RequestWrapper::logMessage("For some reason this request is trying to update the source term. Maybe this term is not translatable?");
                continue;
            }

            // there's no ID yet, so duplicate this term and use that ID as the translation.
            if ( ! $translatedTermId ) {
                Livewords_RequestWrapper::logMessage(sprintf( "Translated term id does not exist. Creating one for '%s' which is a '%s'\n\n", $originalTermId, $originalTermTaxonomy));
                $translatedTermId = $this->duplicateTerm( $originalTermId, $originalTermTaxonomy );
            }
            Livewords_RequestWrapper::logMessage(sprintf("Translation of %s is %s", $originalTermId, $translatedTermId));

            $translatedTerm = array( 'name' => $term['term_name'] );
            if ( $term['term_description'] ) {
                $translatedTerm['description'] = $term['term_description'];
            }
            // Update the translated term with the translated string.
            // Todo: what about the slug!
            Livewords_RequestWrapper::logMessage(sprintf("Updating %s with name '%s'...", $translatedTermId, $term['term_name']));
            wp_update_term( $translatedTermId, $originalTermTaxonomy, $translatedTerm );
            Livewords_RequestWrapper::logMessage(sprintf("Done updating term %s", $translatedTermId));

        }

    }

    /**
     *
     * Method for extracting postId from parsed XML body
     * @return integer postId
     */
    public function getLoadedPostId() {
        $post = $this->parsedBody['posts'][0];

        return $post['post_id'];
    }

    /**
     * @param $postType string
     * @param $ignoreTranslationStatus bool
     *
     * @return array All post ids and targets like array(id=>array(targets))
     */
    public function getPostIdsForSendingBulk( $postType, $ignoreTranslationStatus = false ) {

        // switch to source language
        global $sitepress;
        $sitepress->switch_lang(apply_filters('wpml_default_language', NULL ));

        // Set the return array up.
        $returnIds = array();

        if ( $ignoreTranslationStatus ) {
            // We only want the posts with correct post type
            $args = array(
                'nopaging'  => true,
                'post_type' => $postType
            );
        } else {
            // Check to see if this status is either not set, untranslated or modified.
            $metaQuery = array(
                'relation' => 'OR',
                array(
                    'key'     => Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_STATUS,
                    'value'   => Livewords_RequestWrapper::TRANSLATION_STATUS_MODIFIED,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_STATUS,
                    'value'   => Livewords_RequestWrapper::TRANSLATION_STATUS_NOT_TRANSLATED,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => Livewords_RequestWrapper::WP_POST_META_FIELD_TRANSLATION_STATUS,
                    'compare' => 'NOT EXISTS',
                ),
            );
            // We want the posts with correct post type and meta query
            $args = array(
                'nopaging'  => true,
                'post_type'  => $postType,
                'meta_query' => $metaQuery
            );

        }

        $query = new WP_Query( $args );
        $posts = $query->get_posts();

        foreach ( $posts as $post ) {

            // Get user selected target languages for the post
            $selectedTargets = Livewords_RequestWrapper::getPostSelectedTargetLanguages( $post->ID );

            // Post must be in default language AND there must be at least one selected target
            if ( Livewords_RequestWrapper::isPostInDefaultLanguage( $post->ID ) && $selectedTargets ) {
                $returnIds[ $post->ID ] = $selectedTargets;
            };
        }

        return $returnIds;
    }

    /**
     *
     * Try to get duplicate postID. If not exist, create duplicate and set in draft.
     *
     * @param integer $postId of the source post or false if target post does not exist
     *
     * @return integer|boolean
     *
     */
    public function getWPMLTranslatedPostIdForLocale( $postId ) {

        $translatedPostId = null;

        // Check if the source post exists
        if ( $this->postExists( $postId ) ) {

            // Check if target post exists
            $wpml_element_type  = apply_filters( 'wpml_element_type', get_post_type( $postId ));
            $translatedPostId = apply_filters( 'wpml_object_id', $postId, get_post_type( $postId ), false, $this->language );
            Livewords_RequestWrapper::logMessage(sprintf("Translated postId is [%s]", $translatedPostId));
            // If not, create it by duplicating
            if ( ! $translatedPostId ) {
                Livewords_RequestWrapper::logMessage(sprintf("Target post did not exist. Creating..."));
                $translatedPostId = $this->duplicatePost( $postId );
            }
            Livewords_RequestWrapper::logMessage(sprintf("getWPMLTranslatedPostIdForLocale for %s post type '%s' is %s", $postId, get_post_type( $postId ), $translatedPostId));
            return $translatedPostId;
        } else {
            Livewords_RequestWrapper::logMessageAndDie(sprintf("ERROR Post with id %s does not exist", $postId));
            return false;
        }

    }

    /**
     * Determines if a post, identified by the specified ID, exist
     * within the WordPress database.
     *
     * @param    int $postId The ID of the post to check
     *
     * @return   bool               True if the post exists; otherwise, false.
     * @since    1.0.0
     */
    public static function postExists( $postId ) {
        return is_string( get_post_status( $postId ) );
    }

    /**
     * Duplicate post by it's Id
     *
     * @param integer
     *
     * @return integer
     */
    public function duplicatePost( $postId ) {
        global $wpdb;
        $post = get_post( $postId );
        $args = array(
            'comment_status' => $post->comment_status,
            'ping_status'    => $post->ping_status,
            'post_author'    => 1, //TODO fix author - find author and store in post
            'post_content'   => $post->post_content,
            'post_excerpt'   => $post->post_excerpt,
            'post_name'      => $post->post_name,
            'post_parent'    => $post->post_parent,
            'post_password'  => $post->post_password,
            'post_status'    => 'draft',
            'post_title'     => $post->post_title,
            'post_type'      => $post->post_type,
            'to_ping'        => $post->to_ping,
            'menu_order'     => $post->menu_order
        );

        //		 Insert new post and obtain the ID
        $new_post_id = wp_insert_post( $args );

        /*
         * get all current post terms ad set them to the new post draft
         */
        $taxonomies = get_object_taxonomies( $post->post_type ); // returns array of taxonomy names for post type, ex array("category", "post_tag");
        foreach ( $taxonomies as $taxonomy ) {
            $post_terms = wp_get_object_terms( $postId, $taxonomy, array( 'fields' => 'slugs' ) );
            wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
        }

        /*
         * duplicate all post meta just in two SQL queries
         */
        //TODO escape postId
        $post_meta_infos = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$postId" );
        if ( count( $post_meta_infos ) != 0 ) {
            $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
            foreach ( $post_meta_infos as $meta_info ) {
                $meta_key        = $meta_info->meta_key;
                $meta_value      = addslashes( $meta_info->meta_value );
                $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
            }
            $sql_query .= implode( " UNION ALL ", $sql_query_sel );
            $wpdb->query( $sql_query );
        }

        //		Now set relation between source post and newly created post
        $elementType        = get_post_type( $postId );
        $wpml_element_type  = apply_filters( 'wpml_element_type', $elementType );
        $get_language_args  = array( 'element_id' => $postId, 'element_type' => $elementType );

        $original_post_language_info = apply_filters( 'wpml_element_language_details', null, $get_language_args );
        $set_language_args = array(
            'element_id'           => $new_post_id,
            'element_type'         => $wpml_element_type,
            'trid'                 => $original_post_language_info->trid,
            'language_code'        => $this->language,
            'source_language_code' => $original_post_language_info->language_code
        );

        do_action( 'wpml_set_element_language_details', $set_language_args );

        Livewords_RequestWrapper::logMessage(sprintf("Post '%s' is duplicated. Posttype is %s. New post id is '%s'. Trid is '%s'", $postId, $wpml_element_type, $new_post_id, $original_post_language_info->trid));

        return $new_post_id;
    }

    /**
     * @param $term_id integer
     * @param $term_tax string
     *
     * @return integer ID
     */
    function duplicateTerm( $term_id, $term_tax ) {

        $existing_taxonomy_term = get_term( $term_id, $term_tax );
        Livewords_RequestWrapper::logMessage(sprintf("Existing term %s", print_r($existing_taxonomy_term, true)));
        Livewords_RequestWrapper::logMessage(sprintf("Starting term duplication %s %s", $term_id, $term_tax));

        // The {$existing_taxonomy_term->name} ({$this->language}) is temporary and will be overwritten.
        // The slug is permanent
        $new_term = wp_insert_term( "{$existing_taxonomy_term->name} ({$this->language})", $term_tax, array(
            'description' => $existing_taxonomy_term->description,
            'slug'        => "{$existing_taxonomy_term->slug}-{$this->language}",
            'parent'      => $existing_taxonomy_term->parent
        ) );

        // Attach all existing posts
        if ( ! is_wp_error( $new_term ) ) {
            // add all existing posts to new term
            $posts = get_objects_in_term( $term_id, $term_tax );

            if ( ! is_wp_error( $posts ) ) {
                foreach ( $posts as $post_id ) {
                    $result = wp_set_post_terms( $post_id, $new_term['term_id'], $term_tax, true );
                }
            } else {
                Livewords_RequestWrapper::logMessageAndDie($posts);
            }
        } else {
            Livewords_RequestWrapper::logMessageAndDie($new_term);
        }

        // Now make a connection between the original term and the new term
        $wpml_element_type = apply_filters( 'wpml_element_type', $term_tax );
        $language_args     = array( 'element_id' => $existing_taxonomy_term->term_taxonomy_id, 'element_type' => $term_tax );
        Livewords_RequestWrapper::logMessage(sprintf("Getting wpml element language details with these args:\n%s", print_r($language_args, true)));
        $original_term_language_info = apply_filters( 'wpml_element_language_details', null, $language_args );
        Livewords_RequestWrapper::logMessage(sprintf("Original term language info:\n%s", print_r($original_term_language_info, true)));

        $set_language_args           = array(
            'element_id'           => $new_term['term_taxonomy_id'],
            'element_type'         => $wpml_element_type,
            'trid'                 => $original_term_language_info->trid,
            'language_code'        => $this->language,
            'source_language_code' => $original_term_language_info->language_code
        );
        // Set the new terms info and thereby making the connection between old and new.
        do_action( 'wpml_set_element_language_details', $set_language_args );

        return $new_term['term_id'];

    }


    /**
     *
     * @return array containing any errors found while checking the request
     */
    public function validate() {


        if ( 'POST' !== $this->method ) {
            $this->errors[] = [ 'code' => 405, 'message' => sprintf( 'Method %s is not supported', $this->method ) ];
        }

        if ( empty( $this->action ) ) {
            $this->errors[] = [ 'code'    => 406,
                                'message' => sprintf( 'parameter action not set or value evaluates to false' )
            ];
        }

        if ( empty( $this->language ) ) {
            $this->errors[] = [ 'code' => 406, 'message' => sprintf( 'Required parameter language not set' ) ];
        }

        if ( empty( $this->body ) ) {
            $this->errors[] = [ 'code' => 406, 'message' => sprintf( 'Post body is empty' ) ];
        }

        if ( ! is_array( $this->parsedBody ) ) {
            $this->errors[] = [ 'code' => 406, 'message' => sprintf( 'Post body could not be parsed' ) ];
        }

        return $this->errors;

    }

    /**
     *
     * Validate signature sent in header using API_KEY from wordpress options
     * @return bool
     */
    public function isRequestAuthentic() {

        $options = get_option( Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS );
        $apiKey  = $options[ Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS_API_KEY ];

        $timestamp          = $this->headers[ self::HTTP_HEADER_X_TIMESTAMP ];
        $token              = $this->headers[ self::HTTP_HEADER_X_TOKEN ];
        $signatureOnRequest = $this->headers[ self::HTTP_HEADER_X_SIGNATURE ];

        $signature = $this->createHMACSSignature(
            $timestamp,
            $token,
            $apiKey
        );

        $authenticated = ! empty( $signature ) && $signature === $signatureOnRequest;

        return $authenticated;
    }

    /**
     * @see http://static.livewords.com/docs/pigwhale/livewords-api.html#_security_considerations
     *
     * @param $timestamp
     * @param $token
     * @param $key
     *
     * @return false|string
     */
    private function createHMACSSignature( $timestamp, $token, $key ) {

        return hash_hmac( 'sha256', $timestamp . $token, $key );

    }

}
