Livewords Flow
Contributors: livewords1
Tags: wordpress-multilanguage,multilanguage,translation,language,translation-management,translation-automation,translate
Requires at least: 4.7.5
Tested up to: 4.8
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

The Livewords Flow plugin connects a Wordpress installation to Livewords Flow using the WPML plugin.

#### Prerequisites
- This plugin is dependent on WPML.
    - Minimum supported WPML version is 3.6.3
    - When WPML is not installed or if it is deactivated, the Livewords Flow plugin settings page shows a message saying that you should install WPML.
- API settings
    - You must provide an API URL (including protocol), Account domain and Livewords Flow API ID. These can be obtained from Livewords. After these settings are saved, you can use the ‘Test API connection’ button to see if you can connect successfully.
- WPML Settings
    - On the Livewords Flow plugin settings page there is a section called ‘WPML settings’.
    This is for information purposes only; WPML should be configured properly and be in working order before continuing with the Livewords Flow plugin.
    The settings display the default language and the target languages. You can edit these on the WPML settings page.

The Livewords Flow plugin fully follows the WPML workflow. It automates certain key actions of this flow, but every action can still be performed solely with the WPML plugin. Moreover, if the Livewords Flow plugin were to be uninstalled, the theme continues to function as a multi lingual website with all finished translations unaffected and functional.

More information on the WPML plugin can be found at [wpml.org/documentation](https://wpml.org/documentation)

#### Installation guide
Before installing and activating this plugin, you should have an installed and working WPML configuration. Please refer to the [documentation](https://wpml.org/documentation).
Once everything is working properly, you can install and activate this plugin. After filling in the required API URL, Account domain and Livewords API ID, you can continue with the custom field section.

#### Custom fields
Post types can contain custom fields.
These could be little pieces of information that do not belong to the body text of the post, or they can control the behaviour of your installed theme.
There is a plethora of plugins that utilise these fields, either under the hood or as fields to be completed by the admin user.
Many theme builders also use custom fields.

In this section, you can select which custom fields are to be sent to Livewords Flow for translation. All found custom fields are listed with a maximum of 5 value examples and a link to the post they belong to. This way, you can get a good idea of which fields contain translatable content and which fields do not.
Once you have made your selection and saved it, you are good to go.

Next, it is time to test one or more posts, probably working together with the Livewords team to see if translations are received correctly. After everything has been set up, you can translate all existing content with the bulk translate functionality.

#### Edit content manually
You can use the WPML plugin and completely ignore the Livewords Flow plugin.
Bear in mind however that manually performed translations will be overwritten by the Livewords Flow plugin whenever a translation is received.

## Making a post type translatable
WordPress uses so-called post types to distinguish different kinds of posts, so technically, a ‘page’ is also a post.
WordPress comes with a ‘post’ and a ‘page’ post type, but many WordPress implementations have more post types than these two.
You can configure which post types should be translatable in the WPML settings page, or you can toggle this setting on the Edit page of a particular post type.

Once set for translation, the relevant post type is listed in the ‘Content’ section of the Livewords Flow plugin settings page with a ‘bulk translate’ button.
A ‘Translate’ button and settings are also displayed on every Edit page of that particular post type.

## Single post settings
Every translatable post will be requested in all available target languages by default.
You can override this behaviour in the ‘Translate in the languages’ section on the Edit Post page.
You can toggle individual target languages on and off, so a particular language will not be scheduled for translation either in a bulk translation request or in a single post translation request.
Every change in the settings must be saved by hitting ‘Update’ in order for it to take effect.

## Single post translation
On the Edit Post page you will see the Livewords Flow Translation box and the (WPML) Language box.
This plugin follows the same paradigms as WPML. So every translation is also a (target) post, with a connection to the source post.
On every Edit page of a *target* post you will see a message telling you that in order to use the Livewords Flow plugin, you have to navigate to the *source* post.
We have added a link to the source post for your convenience.

You can check if a post already has translations (either created by Livewords Flow or manually) by looking at the ‘Translate this Document’ section.
A plus sign at the target language indicates that there is no translation yet, a pencil sign indicates that there is a translation for this post in that language.
The translation may be out of date, of course.

#### Post status
In WordPress, every post has a status such as ‘Draft’ or ‘Published’.
All words newly translated using Livewords Flow (posts that have never been translated before) have a ‘Draft’ status and must be set to ‘Published’ manually.
This can optionally be achieved by performing a ‘Bulk action’ in the WordPress post listing.

All existing translations will *keep* their post status after a translation update. The same goes for bulk translations.

##### New post
When creating a new post, the Livewords Flow Translation box will indicate that you first have to fill in all mandatory fields and save the post before you will be able to request translations.

After saving the post for the first time, the Translation status of the post will be *Untranslated*.
This status only changes after a translate request and will never reoccur for that particular post.

After the translate request, the Translation status will read *In progress*.
When a translation is received, a WPML-connected post will be created and updated with the translated information.
The status reads *Incomplete* until all requested translations have been received, after which the status will read *Translated*.

##### Existing post
Translating an existing post is basically the same as translating a new post. This post can also have the status *Untranslated*, which means that the post already existed when the Livewords Flow plugin was installed. But all other statuses are possible as well.

##### Translation log
Every post has a translation log. This is a collapsible log, which shows the requests and responses received for each post. It is mainly used for debugging.

## Bulk translation
Bulk translation can be performed for each post type. The bulk action will request a translation for every post that has the *Untranslated* or *Modified* status.
The single post setting regarding target languages is applied in the request. The status of the translation request can be viewed in the same way as a single post translation request.

If there are no pending items left, you can execute a forced request for all posts of that post type by clicking ‘Translate anyway’.
When performing this action, all translation statuses will be ignored, but the single post target languages setting is not.

## Taxonomies
Only categories and tags are marked as translatable by WPML by default.
If your theme utilises more taxonomies, you can enable them in the WPML settings page under Translation options > Custom taxonomies.
The Livewords Flow plugin setting page has a ‘Translate taxonomies’ button. The plugin will request a translation for every translatable taxonomy.
Taxonomies do not have status or language settings.

== Installation ==

### Overview

This plugin leverages the functionality of the WPML plugin. WPML is therefore dependent on it.
When the user sends a request for a translation, a request with an xml body is send to Livewords Flow.

 ```xml
<item id="1">
    <livewords:meta>
    <livewords:labels>
        <livewords:label>post</livewords:label>
    </livewords:labels>
    <livewords:id>1</livewords:id>
    <livewords:guid isPermaLink="false">http://livewords-plugin.local?p=1</livewords:guid>
    <livewords:action>translate_posts||translate_taxonomies</livewords:action>
    <livewords:default-language>en</livewords:default-language>
    <livewords:type>post</livewords:type>
    <livewords:creation-date>Mon, 03 Apr 2017 13:09:31 +0000</livewords:creation-date>

        <custom-attributes>
            <custom-attribute attribute-id="livewords:target-lang">
                <value>nl</value>
                <value>de</value>
        </custom-attribute>
        </custom-attributes>

    </livewords:meta>

    <wp:post_id>1</wp:post_id>
    <wp:post_name><![CDATA[Translatable post name]]></wp:post_name>
    <wp:post_type><![CDATA[post]]></wp:post_type>

    <wp:postmeta key="key1">
        <wp:meta_key><![CDATA[key1]]></wp:meta_key>
        <wp:meta_value><![CDATA[Translatable value]]></wp:meta_value>
    </wp:postmeta>

    <wp:postmeta key="key2">
    <wp:meta_key><![CDATA[key2]]></wp:meta_key>
    <wp:meta_value><![CDATA[Translatable value]]></wp:meta_value>
    </wp:postmeta>

    <title>Post title</title>

    <content:encoded><![CDATA[Translatable body content]]></content:encoded>
    <excerpt:encoded><![CDATA[Translatable excerpt]]></excerpt:encoded>
    <wp:post_name><![CDATA[Translatable post name]]></wp:post_name>
    <taxonomies>
        <![CDATA[List of taxonomies]]>
    </taxonomies>

</item>
```

This request comprises:

* livewords:meta
  * livewords:labels
    * livewords:label: The specific post type.
  * livewords:id: The ID of the post
  * livewords:action: Either 'translate_posts' or 'translate_taxonomies'. The desired action. When the translated item is pushed back, the plugin determines the desired action based on this value.
  * livewords:default-language: The WPML selected source language.
  * custom-attributes
    * custom-attribute attribute-id="livewords:target-lang".
      * value: A list of target languages. These are the WPML target language by default, but can also be selected for each post by the admin user.
* content:encoded: Translatable content body.
* excerpt:encoded: Translatable excerpt.
* wp:post_name: Translatable name of the post
* wp:postmeta key="key": Key value pairs with translatable values. Which keys are sent is configured in the Custom Fields section of the plugin. Since keys are not unique in any way, this is not 100% guaranteed to work: selected key A may mean something else in another post.

After receiving the push back from Livewords Flow, the desired action is derived from the xml. Each post has translated posts as applied by WPML. If there is a translation post for the received target language, that post is updated with the translated content.
If the target post does not exist, the source post is duplicated with the correct language and linked to the source post, after which it is updated with the received translation.

Bulk translations work the same way as single requests, but with more items packed into a single request.

Taxonomies pack a request like
```xml
<wp:term>
    <wp:term_id><![CDATA[1]]></wp:term_id>
    <wp:term_taxonomy><![CDATA[post_tag]]></wp:term_taxonomy>
    <wp:term_slug><![CDATA[term-slug]]></wp:term_slug>
    <wp:term_parent><![CDATA[]]></wp:term_parent>
    <wp:term_name><![CDATA[translatable content]]></wp:term_name>
</wp:term>
```

Taxonomies can only be sent in bulk.

This plugin does not introduce any new database tables.

### Installation
The plugin is installed by placing the Livewords Flow directory in the /wp-content/plugins/ directory. Activate the plugin in the plugin section when logged into the backend.

### Custom fields
This query creates a list of custom fields
```sql
SELECT * FROM $wpdb->posts P
INNER JOIN $wpdb->postmeta PM ON PM.post_id = P.ID
WHERE PM.meta_key NOT LIKE '\_%'
AND (PM.meta_value IS NOT NULL AND PM.meta_value != '')
AND P.post_status != 'auto-draft'
AND P.post_type != 'revision'
AND P.post_type != 'acf'
GROUP BY PM.meta_value
ORDER BY PM.meta_key
```
So, every post is considered and inner joined based on post meta. This way, we can filter out meta keys starting with an underscore, null values, posts that are a revision or auto-draft, and posts of the 'acf' type, introduced by the popular Advanced Custom Fields plugin.

### Error handling
There are scenarios in which the plugin cannot handle the received translation. In that case, the error is written to system.log (there is no specific error.log), and put in the response body.
Then the system exits, giving a 200 response code, mainly to keep Livewords Flow from trying the same push again.

### Debugging

#### Post log
Every post has a small log with info on sent and received requests info and a timestamp. The plugin uses update_post_meta for this, so the log is saved with the post as meta data and does not introduce a new database table.

#### Log file
Have a look in livewords/system.log (in the installation directory) after sending a request for a complete xml structure of both the request and the received translation.
A system.log file is keeping track of all requests sent and received, as well as any errors that have occurred. The file will truncate to 4MB. Here is a list of the various log entries:
 * Failed to parse request body: thrown when the plugin is unable to parse the xml.
 * No action could be found: thrown when one of the two actions is not found.
 * New status of post %s id %s: shows the new post status.
 * Starting taxonomies for lang %s: starting translation of taxonomies in language l.
 * Target post did not exist. Creating...: printed when the plugin tries to create a new linked post.
 * Callback request received: general notice that a push from Livewords Flow has been received.
 * Please provide a locale: thrown when push from Livewords Flow did not contain a locale.
 * The requested locale '%s' is the same as the default locale: thrown when the target locale is the same as the source locale.

### Limitations
For now, the plugin only supports content that is a post or custom post type, and taxonomies. All other types of content, such as widgets and strings, are currently not supported.