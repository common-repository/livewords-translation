<?php
/**
 * WordPress Export Administration API
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Version number for the export format.
 *
 * Bump this when something changes that might affect compatibility.
 *
 * @since 2.5.0
 */
define('WXR_VERSION', '1.2');

/**
 * Generates the WXR export file for download.
 *
 * Default behavior is to export all content, however, note that post content will only
 * be exported for post types with the `can_export` argument enabled. Any posts with the
 * 'auto-draft' status will be skipped.
 *
 * @since 2.1.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @global WP_Post $post Global `$post`.
 *
 * @param array $args {
 *     Optional. Arguments for generating the WXR export file for download. Default empty array.
 *
 * @type string $content Type of content to export. If set, only the post content of this post type
 *                                  will be exported. Accepts 'all', 'post', 'page', 'attachment', or a defined
 *                                  custom post. If an invalid custom post type is supplied, every post type for
 *                                  which `can_export` is enabled will be exported instead. If a valid custom post
 *                                  type is supplied but `can_export` is disabled, then 'posts' will be exported
 *                                  instead. When 'all' is supplied, only post types with `can_export` enabled will
 *                                  be exported. Default 'all'.
 * @type string $author Author to export content for. Only used when `$content` is 'post', 'page', or
 *                                  'attachment'. Accepts false (all) or a specific author ID. Default false (all).
 * @type string $category Category (slug) to export content for. Used only when `$content` is 'post'. If
 *                                  set, only post content assigned to `$category will be exported. Accepts false
 *                                  or a specific category slug. Default is false (all categories).
 * @type string $start_date Start date to export content from. Expected date format is 'Y-m-d'. Used only
 *                                  when `$content` is 'post', 'page' or 'attachment'. Default false (since the
 *                                  beginning of time).
 * @type string $end_date End date to export content to. Expected date format is 'Y-m-d'. Used only when
 *                                  `$content` is 'post', 'page' or 'attachment'. Default false (latest publish date).
 * @type string $status Post status to export posts for. Used only when `$content` is 'post' or 'page'.
 *                                  Accepts false (all statuses except 'auto-draft'), or a specific status, i.e.
 *                                  'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', or
 *                                  'trash'. Default false (all statuses except 'auto-draft').
 * }
 *
 * @todo rewrite sql where conditions, most of them not needed for xml post to livewords, post type and post id should be enough
 */

// Require the class
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php';

function xml_livewords($args = array())
{
    global $wpdb, $post;

    $defaults = array(
        'postId' => 0,
        'postIds' => false,
        'get_taxonomies' => false,
        'content' => 'post',
        'author' => false,
        'category' => false,
        'start_date' => false,
        'end_date' => false,
        'status' => false,
    );
    $args = wp_parse_args($args, $defaults);

    /**
     * Fires at the beginning of an export, before any headers are sent.
     *
     * @since 2.3.0
     *
     * @param array $args An array of export arguments.
     */
    do_action('export_wp', $args);

    $sitename = sanitize_key(get_bloginfo('name'));
    if (!empty($sitename)) {
        $sitename .= '.';
    }

    // Specific post type
    if ('all' != $args['content'] && post_type_exists($args['content'])) {
        $ptype = get_post_type_object($args['content']);
        if (!$ptype->can_export)
            $args['content'] = 'post';

        $where = $wpdb->prepare("{$wpdb->posts}.post_type = %s", $args['content']);
    } else {
        // This is ALL post types.
        $post_types = get_post_types(array('can_export' => true));

        $esses = array_fill(0, count($post_types), '%s');

        $where = $wpdb->prepare("{$wpdb->posts}.post_type IN (" . implode(',', $esses) . ')', $post_types);
    }

    if ($args['status'] && ('post' == $args['content'] || 'page' == $args['content'])) {
        $where .= $wpdb->prepare( " AND {$wpdb->posts}.post_status = %s", $args['status'] );
    }
    else {
        // Todo: add meta untranslated
        $where .= " AND {$wpdb->posts}.post_status != 'auto-draft'";
        $where .= " AND {$wpdb->posts}.post_status != 'trash'";
    }

    $join = '';
    if ((true || $args['category'] && 'post' == $args['content']) && $args['get_taxonomies']) {
        if ($term = term_exists($args['category'], 'category')) {
            $join = "INNER JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)";
            $where .= $wpdb->prepare(" AND {$wpdb->term_relationships}.term_taxonomy_id = %d", $term['term_taxonomy_id']);
        }
    }

    if ('post' == $args['content'] || 'page' == $args['content'] || 'attachment' == $args['content']) {
        if ($args['author'])
            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_author = %d", $args['author']);

        if ($args['start_date'])
            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_date >= %s", date('Y-m-d', strtotime($args['start_date'])));

        if ($args['end_date'])
            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_date < %s", date('Y-m-d', strtotime('+1 month', strtotime($args['end_date']))));
    }

//	 If postId, filter on postId
    if ($args['postId']) {
        $where .= $wpdb->prepare(" AND {$wpdb->posts}.ID = " . $args['postId']);
    }

    // Grab a snapshot of post IDs, just in case it changes during the export.
    if ( $args['postIds'] ) {
        $post_ids = array_keys( $args['postIds'] );
    } else {
        $post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} $join WHERE $where" );
    }


    /*
     * Get the requested terms ready, empty unless posts filtered by category
     * or all content.
     */
    $cats = $tags = $terms = array();
    if (isset($term) && $term) {
        $cat = get_term($term['term_id'], 'category');
        $cats = array($cat->term_id => $cat);
        unset($term, $cat);
    } elseif (('all' == $args['content']) || $args['get_taxonomies']) {
        $categories = (array)get_categories(array('get' => 'all'));
        $tags = (array)get_tags(array('get' => 'all'));

        $custom_taxonomies = get_taxonomies(array('_builtin' => false));

        global $sitepress;
        // https://wpml.org/forums/topic/get-all-terms-of-all-languages-outside-loop/#post-1225494
        // Switch to default locale first to only get the terms for one (the default) locale!
        $sitepress->switch_lang(apply_filters('wpml_default_language', NULL ));
        $custom_terms = (array) get_terms( array(
            'hide_empty' => false
        ) );

        // Put categories in order with no child going before its parent.
        while ($cat = array_shift($categories)) {
            if ($cat->parent == 0 || isset($cats[$cat->parent]))
                $cats[$cat->term_id] = $cat;
            else
                $categories[] = $cat;
        }

        while ($t = array_shift($custom_terms)) {
            if ($t->parent == 0 || isset($terms[$t->parent])) {
                $terms[ $t->term_id ] = $t;
            }
            else
                $custom_terms[] = $t;
        }

        unset($categories, $custom_taxonomies, $custom_terms);
    }

    /**
     * Wrap given string in XML CDATA tag.
     *
     * @since 2.1.0
     *
     * @param string $str String to wrap in XML CDATA tag.
     * @return string
     */
    function wxr_cdata($str)
    {
        if (!seems_utf8($str)) {
            $str = utf8_encode($str);
        }
        // $str = ent2ncr(esc_html($str));
        $str = '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $str) . ']]>';

        return $str;
    }



    /**
     * Output a term_name XML tag from a given term object
     *
     * @since 2.9.0
     *
     * @param object $term Term Object
     */
    function wxr_term_name($term)
    {
        if (empty($term->name))
            return;

        echo '<wp:term_name>' . wxr_cdata($term->name) . "</wp:term_name>\n";
    }

    /**
     * Output a term_description XML tag from a given term object
     *
     * @since 2.9.0
     *
     * @param object $term Term Object
     */
    function wxr_term_description($term)
    {
        if (empty($term->description))
            return;

        echo "\t\t<wp:term_description>" . wxr_cdata($term->description) . "</wp:term_description>\n";
    }

    /**
     * Output term meta XML tags for a given term object.
     *
     * @since 4.6.0
     *
     * @param WP_Term $term Term object.
     */
    function wxr_term_meta($term)
    {
        global $wpdb;

        $termmeta = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->termmeta WHERE term_id = %d", $term->term_id));

        foreach ($termmeta as $meta) {
            /**
             * Filters whether to selectively skip term meta used for WXR exports.
             *
             * Returning a truthy value to the filter will skip the current meta
             * object from being exported.
             *
             * @since 4.6.0
             *
             * @param bool $skip Whether to skip the current piece of term meta. Default false.
             * @param string $meta_key Current meta key.
             * @param object $meta Current meta object.
             */
            if (!apply_filters('wxr_export_skip_termmeta', false, $meta->meta_key, $meta)) {
                printf("\t\t<wp:termmeta>\n\t\t\t<wp:meta_key>%s</wp:meta_key>\n\t\t\t<wp:meta_value>%s</wp:meta_value>\n\t\t</wp:termmeta>\n", wxr_cdata($meta->meta_key), wxr_cdata($meta->meta_value));
            }
        }
    }




    /**
     * Output list of taxonomy terms, in XML tag format, associated with a post
     *
     * @since 2.3.0
     */
    function wxr_post_taxonomy()
    {
        $post = get_post();

        $taxonomies = get_object_taxonomies($post->post_type);
        if (empty($taxonomies))
            return;
        $terms = wp_get_object_terms($post->ID, $taxonomies);

        foreach ((array)$terms as $term) {
            echo "\t\t<category domain=\"{$term->taxonomy}\" nicename=\"{$term->slug}\">" . wxr_cdata($term->name) . "</category>\n";
        }
    }

    /**
     *
     * @param bool $return_me
     * @param string $meta_key
     * @return bool
     */
    function wxr_filter_postmeta($return_me, $meta_key)
    {
        if ('_edit_lock' == $meta_key)
            $return_me = true;
        return $return_me;
    }

    ob_start();

    echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . "\" ?>\n"; ?>

    <rss
            xmlns:excerpt="http://wordpress.org/export/<?php echo WXR_VERSION; ?>/excerpt/"
            xmlns:content="http://purl.org/rss/1.0/modules/content/"
            xmlns:wfw="http://wellformedweb.org/CommentAPI/"
            xmlns:dc="http://purl.org/dc/elements/1.1/"
            xmlns:wp="http://wordpress.org/export/<?php echo WXR_VERSION; ?>/"
            xmlns:livewords="http://livewords.com/wordpress/metadata/">
        <channel>



            <?php // *******************    Only do this if get_taxonomies is requested     ******************* ?>
            <?php if ( $args['get_taxonomies'] ) : ?>

                <livewords:meta>

                    <livewords:action>translate_taxonomies</livewords:action>
                    <livewords:default-language><?php echo apply_filters('wpml_default_language', NULL ); ?></livewords:default-language>

                    <custom-attributes>
                        <custom-attribute attribute-id="livewords:target-lang">
                            <?php
                            // Require the class
                            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-livewords-requestwrapper.php'; ?>
                            <?php
                            // Get target languages
                            $selected_targets = Livewords_RequestWrapper::getTargetLanguagesArray();
                            // Loop through default targets
                            foreach ( $selected_targets as $target ) : ?>
                                <value><?php echo $target; ?></value>
                            <?php endforeach; ?>
                        </custom-attribute>
                    </custom-attributes>

                </livewords:meta>

                <?php foreach ( $terms as $t ) : ?>
                    <?php if ($t->taxonomy == 'nav_menu') {
                        // Leave nav_menu out.
                        continue;
                    }
                    if (!Livewords_RequestWrapper::isTaxonomyTranslatable( $t->term_id )) {
                        // Leave items out that are not translatable
                        continue;
                    }
                    ?>
                    <wp:term>
                        <wp:term_id><?php echo wxr_cdata( $t->term_id ); ?></wp:term_id>
                        <wp:term_taxonomy><?php echo wxr_cdata( $t->taxonomy ); ?></wp:term_taxonomy>
                        <wp:term_slug><?php echo wxr_cdata( $t->slug ); ?></wp:term_slug>
                        <wp:term_parent><?php echo wxr_cdata( $t->parent ? $terms[ $t->parent ]->slug : '' ); ?></wp:term_parent>
                        <?php
                        wxr_term_name( $t );
                        wxr_term_description( $t );
//                        wxr_term_meta( $t );
                        ?>
                    </wp:term>
                <?php endforeach; ?>
            <?php endif; ?>


            <?php
            /**
             * This part is for getting posts XML
             */
            ?>
            <?php // *******************    Only do this if posts is requested    *******************?>
            <?php if (!$args['get_taxonomies'] && $post_ids) {?>

                <?php
                /**
                 * @global WP_Query $wp_query
                 */
                global $wp_query;

                // Fake being in the loop.
                $wp_query->in_the_loop = true;

                // Fetch 20 posts at a time rather than loading the entire table into memory.
                while ($next_posts = array_splice($post_ids, 0, 20)) {
                    $where = 'WHERE ID IN (' . join(',', $next_posts) . ')';
                    $posts = $wpdb->get_results("SELECT * FROM {$wpdb->posts} $where");
                    ?>

                    <?php
                    // Begin Loop.
                    foreach ($posts as $post) {

                        setup_postdata($post); ?>
                        <item id="<?php echo intval($post->ID); ?>">
                            <livewords:meta>
                                <livewords:labels>
                                    <livewords:label><?php echo get_post_type(); ?></livewords:label>
                                </livewords:labels>
                                <livewords:id><?php echo intval( $post->ID ); ?></livewords:id>
                                <livewords:guid isPermaLink="false"><?php the_guid(); ?></livewords:guid>
                                <livewords:action>translate_posts</livewords:action>
                                <livewords:default-language><?php echo apply_filters( 'wpml_default_language', null ); ?></livewords:default-language>
                                <livewords:type><?php echo $ptype->name; ?></livewords:type>
                                <livewords:creation-date><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ); ?></livewords:creation-date>

                                <custom-attributes>
                                    <custom-attribute attribute-id="livewords:target-lang">
                                        <?php
                                        // Loop through selected or default targets
                                        $targets = $args['postIds'][ $post->ID ];
                                        foreach ( $targets as $target ) : ?>
                                            <value><?php echo $target; ?></value>
                                        <?php endforeach; ?>
                                    </custom-attribute>
                                </custom-attributes>

                            </livewords:meta>

                            <wp:post_id><?php echo intval($post->ID); ?></wp:post_id>
                            <wp:post_name><?php echo wxr_cdata($post->post_name); ?></wp:post_name>
                            <wp:post_type><?php echo wxr_cdata( $post->post_type ); ?></wp:post_type>


                            <?php // Only post allowed meta tags ?>
                            <?php
                                $allowedMetaTags = Livewords_RequestWrapper::allowedMetaTermsArray();
                                $allowedMetaTagsMetaQueryString = "(";
                                foreach ($allowedMetaTags as $metaTag) {
                                    $allowedMetaTagsMetaQueryString .= $wpdb->prepare("meta_key = %s OR ", $metaTag);
                                }
                                $allowedMetaTagsMetaQueryString .= "false)";
                            ?>

                            <?php $postmeta = $wpdb->get_results($wpdb->prepare("
                              SELECT * FROM $wpdb->postmeta 
                              WHERE post_id = %d AND meta_key NOT LIKE '\_%%' AND ".$allowedMetaTagsMetaQueryString,
                                $post->ID));

                            foreach ($postmeta as $meta) :
                                /**
                                 * Filters whether to selectively skip post meta used for WXR exports.
                                 *
                                 * Returning a truthy value to the filter will skip the current meta
                                 * object from being exported.
                                 *
                                 * @since 3.3.0
                                 *
                                 * @param bool $skip Whether to skip the current post meta. Default false.
                                 * @param string $meta_key Current meta key.
                                 * @param object $meta Current meta object.
                                 */
                                if (apply_filters('wxr_export_skip_postmeta', false, $meta->meta_key, $meta))
                                    continue;
                                ?>

                                <wp:postmeta key="<?php echo $meta->meta_key; ?>">
                                    <wp:meta_key><?php echo wxr_cdata($meta->meta_key); ?></wp:meta_key>
                                    <wp:meta_value><?php echo wxr_cdata($meta->meta_value); ?></wp:meta_value>
                                </wp:postmeta>
                            <?php endforeach; ?>


                            <title><?php
                                /** This filter is documented in wp-includes/feed.php */
                                echo apply_filters('the_title_rss', $post->post_title);
                                ?></title>

                            <content:encoded><?php
                                /**
                                 * Filters the post content used for WXR exports.
                                 *
                                 * @since 2.5.0
                                 *
                                 * @param string $post_content Content of the current post.
                                 */
                                echo wxr_cdata(apply_filters('the_content_export', $post->post_content));
                                ?></content:encoded>
                            <excerpt:encoded><?php
                                /**
                                 * Filters the post excerpt used for WXR exports.
                                 *
                                 * @since 2.6.0
                                 *
                                 * @param string $post_excerpt Excerpt for the current post.
                                 */
                                echo wxr_cdata(apply_filters('the_excerpt_export', $post->post_excerpt));
                                ?></excerpt:encoded>
                            <wp:post_name><?php echo wxr_cdata($post->post_name); ?></wp:post_name>
                            <taxonomies>
                                <?php wxr_post_taxonomy(); ?>
                            </taxonomies>

                        </item>
                        <?php
                    }
                }
            } ?>
        </channel>
    </rss>
    <?php

    $return = ob_get_contents();
    ob_end_clean();
    return $return;

}