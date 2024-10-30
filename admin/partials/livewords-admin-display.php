<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://lazzo.nl
 * @since      1.0.0
 *
 * @package    Livewords
 * @subpackage Livewords/admin/partials
 */

?>


<?php require_once plugin_dir_path( dirname( __FILE__ ) ) . '../includes/class-livewords-requestwrapper.php'; ?>

<?php settings_errors($this->plugin_name); ?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php
      $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'settings';
    ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=livewords&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
        <a href="?page=livewords&tab=content" class="nav-tab <?php echo $active_tab == 'content' ? 'nav-tab-active' : ''; ?>">Content</a>
        <a href="?page=livewords&tab=custom-fields" class="nav-tab <?php echo $active_tab == 'custom-fields' ? 'nav-tab-active' : ''; ?>">Custom fields</a>
    </h2>


    <form method="post" name="livewords_options" action="options.php">


        <?php if( $active_tab == 'settings' ) : ?>

        <?php settings_fields( 'liveWordsOptionsPage' ); ?>
        <?php do_settings_sections( 'liveWordsOptionsPage' ); ?>

        <?php
            $options = get_option( Livewords::WP_OPTIONS_LIVEWORDS_SETTINGS );

            $disabled = array();
            if ( strlen( $options['livewords_text_field_account_domain'] ) == 0 || strlen( $options['livewords_text_field_api_key'] ) == 0 ) {
                $disabled = array( 'disabled' => true );
            }
        ?>

        <?php submit_button( 'Test API connection', 'secondary', 'test_connection', true, $disabled ); ?>

        <div class="connection_test_response"></div>

        <?php submit_button('Save settings', 'primary','submit', true); ?>

        <hr>

        <h2>WPML Settings</h2>

        <p>These are the current WPML selected default and target languages. You can change this configuration <a href="/wp-admin/admin.php?page=sitepress-multilingual-cms/menu/languages.php">here</a>.</p>

        <?php
        // Get full info on target languages
        $languages_info = Livewords_RequestWrapper::getWPMLActiveLanguagesFullArray();
        // Get active target languages
        $active_target_languages = Livewords_RequestWrapper::getTargetLanguagesArray();
        ?>

        <p>
            <strong>Default language</strong><br>
            <img src="<?php echo $languages_info[Livewords_RequestWrapper::getDefaultLanguage()]['country_flag_url']; ?>">
            <?php echo Livewords_RequestWrapper::getDefaultLanguage(); ?>
        </p>

        <p>
            <strong>Active target languages</strong><br>
            <?php foreach ($active_target_languages as $active_target_language) : ?>
                <img src="<?php echo $languages_info[$active_target_language]['country_flag_url']; ?>">
                <?php echo $active_target_language; ?>
                <br>
            <?php endforeach; ?>
        </p>

        </form>

        <?php endif; ?>

        <?php if( $active_tab == 'content' ) : ?>

        <h2>Content</h2>

        <h4>Posts</h4>
        <p>Bulk translate all pending translatable posts, pages and custom post types</p>

        <?php
        // Get all translatable post types. Per custom post type, WPML lets you mark it as translatable on the edit post screen.
        $translatable_post_types = Livewords_RequestWrapper::getTranslatablePostTypes();
        ?>

        <table>

        <tr>
            <th>Post type</th>
            <th>Untranslated & modified</th>
            <th>All</th>
            <th></th>
        </tr>

            <?php foreach ( $translatable_post_types as $translatable_post_type ) : ?>

                <?php
                $readyForTranslationCount = count( Livewords_RequestWrapper::getPostIdsForSendingBulk( $translatable_post_type ) );
                $buttonOptions = array( 'post-type' => $translatable_post_type, 'forced' => false );
                $buttonText = sprintf("Bulk translate %s items", $readyForTranslationCount);
                if ($readyForTranslationCount == 0) {
                    $buttonOptions['disabled'] = 'disabled';
                    $buttonText = sprintf("No pending items");
                }
                ?>

                <tr
                    class="bulk_translate_posts"
                    id="bulk_translate_posts-<?php echo $translatable_post_type; ?>"
                    in-progress-text="In progress"
                    done-text="Done"
                    fail-text="Error">

                    <td>
                        <label><?php echo $translatable_post_type; ?></label>
                    </td>

                    <td class="input-fields">
                        <?php submit_button( $buttonText,
                            'secondary',
                            'bulk_translate_posts-' . $translatable_post_type,
                            false,
                            $buttonOptions ); ?>
                    </td>
                    <td>
                        <?php if (true || $readyForTranslationCount == 0) {
                            submit_button( 'Translate',
                            'secondary',
                            'bulk_translate_posts_anyway-' . $translatable_post_type,
                            false,
                                array( 'post-type' => $translatable_post_type, 'forced' => true ) );
                        } ?>
                    </td>

                    <td class="bulk_translate_posts_response" id="bulk_translate_posts_response-<?php echo $translatable_post_type; ?>">

                    </td>

                </tr>
            <?php endforeach; ?>
        </table>

        <h4>Taxonomies</h4>
        <p>Bulk translate all taxonomies like categories and tags</p>

        <div class="bulk_translate_taxonomies"
             in-progress-text="In progress"
             done-text="Done"
             fail-text="Error">

            <?php submit_button( 'Translate taxonomies', 'secondary', 'translate_taxonomies', true ); ?>

            <div class="bulk_translate_taxonomies_response"></div>

        </div>

        <?php endif; ?>

        <?php if( $active_tab == 'custom-fields' ) : ?>

        <h2>Custom fields</h2>

        <p>Every post has a title and a content body. Additionally, a post can have custom fields, either user generated or as part of a plugin or theme.
        In this section you can mark which custom fields are to be send for translation.</p>

        <form method="post" name="livewords_options_custom_fields" action="options.php">

            <?php settings_fields( 'liveWordsCustomFieldsOptions' ); ?>

            <?php $customFieldsOptions = get_option( Livewords::WP_OPTIONS_LIVEWORDS_CUSTOM_FIELDS_SETTINGS ); ?>
            <?php

//SELECT * FROM wp_postmeta PM
//INNER JOIN wp_posts P ON PM.post_id = P.ID
//WHERE PM.meta_key NOT LIKE '\_%'
//AND (PM.meta_value IS NOT NULL AND PM.meta_value != '')
//AND P.post_status != 'auto-draft'
//AND P.post_type != 'acf'
//GROUP BY PM.meta_key


            global $wpdb;
            $customFields = $wpdb->get_results(
            "
SELECT * FROM $wpdb->posts P
INNER JOIN $wpdb->postmeta PM ON PM.post_id = P.ID
WHERE PM.meta_key NOT LIKE '\_%' 
AND (PM.meta_value IS NOT NULL AND PM.meta_value != '')
AND P.post_status != 'auto-draft'
AND P.post_type != 'revision'
AND P.post_type != 'acf'
GROUP BY PM.meta_value
ORDER BY PM.meta_key
            "
            );
            ?>
                <table class="data-table">
                <thead>
                <tr>
                <th></th>
                <th>Name</th>
                <th>Value example</th>
                <th>Found in post</th>
                </tr>
                </thead>

            <?php
            $metaKey = null;
            $rowCountPerMetaKey = 0;
            foreach ( $customFields as $customField ) :

                // Try to deserialize. Omit serialized values
                if (is_serialized( $customField->meta_value)) {
                    continue;
                }
                if ($rowCountPerMetaKey > 3) {
                    if($metaKey != $customField->meta_key) {
                        $rowCountPerMetaKey = 0;
                    }
                    continue;
                }


            ?><tr>
                <td>

                <?php // Show input once ?>
                <?php if($metaKey != $customField->meta_key) : ?>
                            <input
                            <?php echo $customFieldsOptions[$customField->meta_id]? 'checked':''; ?>
                                id="customField<?php echo $customField->meta_id; ?>"
                                type="checkbox"
                                name="<?php echo Livewords::WP_OPTIONS_LIVEWORDS_CUSTOM_FIELDS_SETTINGS ?>[<?php echo  $customField->meta_id; ?>]"
                                value="<?php echo  $customField->meta_key; ?>">
                <?php endif; ?>


                        </td>
                        <td>

                            <?php // Show label only once ?>
                                <?php if($metaKey != $customField->meta_key) : ?>
                                    <label style="font-weight: bold" for="customField<?php echo $customField->meta_id; ?>"><?php echo  $customField->meta_key; ?></label>
                                    <?php $rowCountPerMetaKey = 0; ?>
                                    <?php // Set metaKey to new metaKey ?>
                                    <?php $metaKey = $customField->meta_key; ?>

                                <?php else: ?>
                                    <?php $rowCountPerMetaKey++; ?>
                                <?php endif; ?>

                        </td>
                        <td>
                        <?php
                        $customFieldValue = $customField->meta_value;
                        if (strlen($customFieldValue) > 100) {

                            // Remove markup
                            $customFieldValue = strip_tags($customFieldValue);

                            // truncate string
                            $stringCut = substr($customFieldValue, 0, 100);

                            // make sure it ends in a word so assassinate doesn't become ass...
                            $customFieldValue = substr($stringCut, 0, strrpos($stringCut, ' ')).'...';
                        }
                        echo $customFieldValue;
                         ?>

                        </td>
                        <td><a href="<?php echo get_edit_post_link($customField->post_id) ?>" target="_blank"><?php echo get_the_title($customField->post_id) ?></a></td>
                    </tr>
            <?php endforeach; ?>
                </table>
            <?php
        ?>

            <hr>
            <?php submit_button('Save custom fields', 'primary','submit', true); ?>

                </form>
        <?php endif; ?>