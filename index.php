<?php
/*
 * Created by: Halak.pl
 * Author: Marcin Halak (@versoo90)
 * Autror: marcin@halak.pl
 */

// DEFAULT SETTINGS
define('DEFAULT_VIDEO_CATEGORY', (int) get_option('default_video_category'));
define('DEFAULT_VIDEO_PAGE', get_option('default_video_page'));
define('DEFAULT_VIDEO_TAXONOMY', 'video_category');
define('DEFAULT_VIDEO_SLUG', 'videos');

function mh_register_video() {
    $labels = array(
        'name' => _x('Video', 'Post Type General Name', 'mh_university'),
        'singular_name' => _x('Video', 'Post Type Singular Name', 'mh_university'),
        'menu_name' => __('Video', 'mh_university'),
        'parent_item_colon' => __('Video parent:', 'mh_university'),
        'all_items' => __('All videos', 'mh_university'),
        'view_item' => __('Show video', 'mh_university'),
        'add_new_item' => __('Add video', 'mh_university'),
        'add_new' => __('New video', 'mh_university'),
        'edit_item' => __('Edit video', 'mh_university'),
        'update_item' => __('Update video', 'mh_university'),
        'search_items' => __('Search video', 'mh_university'),
        'not_found' => __('Not found video', 'mh_university'),
        'not_found_in_trash' => __('Not found video in trash', 'mh_university'),
    );
    $slug = DEFAULT_VIDEO_SLUG;
    $args = array(
        'label' => __('video', 'mh_university'),
        'description' => __('New video', 'mh_university'),
        'labels' => $labels,
        'supports' => array('title', 'editor', 'thumbnail', 'comments'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-video-alt3',
        'can_export' => true,
        'has_archive' => true,
        'exclude_from_search' => false,
        'publicly_queryable' => true,
        'capability_type' => 'post',
        'rewrite' => array('slug' => "$slug/%video_category%", 'with_front' => false),
        'taxonomies' => array(DEFAULT_VIDEO_TAXONOMY, 'video_tag'),
    );
    register_post_type('video', $args);
}

// Hook into the 'init' action
add_action('init', 'mh_register_video');

function create_video_taxonomies() {
    $labels = array(
        'name' => _x('Category', 'taxonomy general name'),
        'singular_name' => _x('Category', 'taxonomy singular name'),
        'search_items' => __('Search category'),
        'all_items' => __('All categories'),
        'parent_item' => __('Category parent'),
        'parent_item_colon' => __('Category parent:'),
        'edit_item' => __('Edit category'),
        'update_item' => __('Update category'),
        'add_new_item' => __('Add new category'),
        'new_item_name' => __('New category name'),
        'menu_name' => __('Categories'),
    );

    $args = array(
        'hierarchical' => true,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'sort' => true,
        'query_var' => true,
        'args' => array('orderby' => 'term_order'),
        'rewrite' => array('slug' => DEFAULT_VIDEO_SLUG, 'with_front' => false),
    );
    register_taxonomy(DEFAULT_VIDEO_TAXONOMY, array('video'), $args);
}

add_action('init', 'create_video_taxonomies', 0);

function create_video_tag_taxonomie() {
    $labels = array(
        'name' => _x('Tag', 'taxonomy general name'),
        'singular_name' => _x('Tag', 'taxonomy singular name'),
        'search_items' => __('Search tag'),
        'all_items' => __('All tags'),
        'parent_item' => __('Tag parent'),
        'parent_item_colon' => __('Tag parent:'),
        'edit_item' => __('Edit tag'),
        'update_item' => __('Update tag'),
        'add_new_item' => __('Add new tag'),
        'new_item_name' => __('New tag name'),
        'menu_name' => __('Tags'),
    );

    $args = array(
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'sort' => true,
        'query_var' => true,
        'args' => array('orderby' => 'term_order'),
        'rewrite' => array('slug' => DEFAULT_VIDEO_SLUG . '/tag', 'with_front' => false),
    );
    register_taxonomy('video_tag', array('video'), $args);
}

add_action('init', 'create_video_tag_taxonomie', 0);

// Create Default Taxonomy
function create_defualt_taxonomy() {
    if (!DEFAULT_VIDEO_CATEGORY) {
        $defaultTaxonomy = wp_insert_term(
            'All videos', // the term
            DEFAULT_VIDEO_TAXONOMY, // the taxonomy
            array(
                'description' => 'All videos',
                'slug' => 'all',
            )
        );
        update_option('default_video_category', $defaultTaxonomy["term_id"]);
    }
}

add_action('init', 'create_defualt_taxonomy', 1);

// Defalut Taxonomy
function mh_set_default_object_terms($post_id, $post) {
    if (DEFAULT_VIDEO_CATEGORY) {
        $term = get_term(DEFAULT_VIDEO_CATEGORY, DEFAULT_VIDEO_TAXONOMY);
        if ('publish' === $post->post_status) {
            $defaults = array(
                DEFAULT_VIDEO_TAXONOMY => array($term->slug),
            );
            $taxonomies = get_object_taxonomies($post->post_type);
            foreach ((array) $taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($post_id, $taxonomy);
                if (empty($terms) && array_key_exists($taxonomy, $defaults)) {
                    wp_set_object_terms($post_id, $defaults[$taxonomy], $taxonomy);
                }
            }
        }
    }
}

add_action('save_post', 'mh_set_default_object_terms', 100, 2);

// Change permalink if video dosen't have category
add_filter('post_link', 'video_category_permalink', 10, 3);
add_filter('post_type_link', 'video_category_permalink', 10, 3);

function video_category_permalink($permalink, $post_id, $leavename) {
    if (strpos($permalink, '%video_category%') === FALSE)
        return $permalink;

    // Get post
    $post = get_post($post_id);
    if (!$post)
        return $permalink;

    // Get taxonomy terms
    $terms = wp_get_object_terms($post->ID, DEFAULT_VIDEO_TAXONOMY);
    if (!is_wp_error($terms) && !empty($terms) && is_object($terms[0]))
        $taxonomy_slug = $terms[0]->slug;
    else
        $taxonomy_slug = 'all';

    return str_replace('%video_category%', $taxonomy_slug, $permalink);
}

// Add to admin_init function
add_filter("manage_edit-video_category_columns", 'video_category_columns');

function video_category_columns($theme_columns) {
    $new_columns = array(
        'cb' => '<input type="checkbox" />',
        'title' => __('Name'),
        'header_icon' => '',
        'description' => __('Description'),
        'slug' => __('Slug'),
        'posts' => __('Posts')
    );
    return $new_columns;
}

// Add to admin_init function
add_filter("manage_video_category_custom_column", 'manage_video_category_columns', 10, 3);

function manage_video_category_columns($out, $column_name, $theme_id) {
    $theme = get_term($theme_id, DEFAULT_VIDEO_TAXONOMY);
    $url = admin_url('edit-tags.php', 'http');
    if ($column_name === 'cb') {
        if ($theme_id !== DEFAULT_VIDEO_CATEGORY) :
            ?>

            <?php
        endif;
    }
    elseif ($column_name === 'title') {
        ?>
        <?php $complete_url = wp_nonce_url("edit-tags.php?action=delete&amp;taxonomy=" . DEFAULT_VIDEO_TAXONOMY . "&amp;tag_ID=$theme_id", 'delete-tag_' . $theme_id);
        ?>
        <strong><a class="row-title" href="<?php echo $url; ?>?action=edit&amp;taxonomy=<?php echo DEFAULT_VIDEO_TAXONOMY; ?>&amp;tag_ID=<?php echo $theme_id; ?>&amp;post_type=video" title="Edit “<?php echo $theme->name; ?>”"><?php echo ($theme->parent > 0) ? '— ' : ''; ?><?php echo $theme->name; ?></a></strong>
        <br>
        <div class="row-actions">
            <span class="edit"><a href="<?php echo $url; ?>?action=edit&amp;taxonomy=<?php echo DEFAULT_VIDEO_TAXONOMY; ?>&amp;tag_ID=<?php echo $theme_id; ?>&amp;post_type=video">Edit</a> | </span>
            <span class="inline hide-if-no-js"><a href="#" class="editinline">Quick&nbsp;Edit</a> | </span>
            <?php if ($theme_id !== DEFAULT_VIDEO_CATEGORY) : ?>
                <span class="delete"><a class="delete-tag" href="<?php echo $complete_url; ?>">Delete</a> | </span>
            <?php endif; ?>
            <span class="view"><a href="<?php echo get_term_link($theme); ?>">View</a></span>
        </div>
        <div class="hidden" id="inline_<?php echo $theme_id; ?>"><div class="name"><?php echo $theme->name; ?></div><div class="slug"><?php echo $theme->slug; ?></div><div class="parent"><?php echo $theme->parent; ?></div></div>
        <?php
    }
}

// Rewrite rule
add_action('init', 'video_rewrite_rule');

function video_rewrite_rule() {
    add_rewrite_rule('^' . DEFAULT_VIDEO_SLUG . '$', 'index.php?redirect_video=1', 'top');
}

add_filter('query_vars', 'video_rewrite_query_vars');

function video_rewrite_query_vars($query_vars) {
    $query_vars[] = 'redirect_video';
    return $query_vars;
}

add_action('parse_request', 'video_rewrite_parse_request');

function video_rewrite_parse_request(&$wp) {
    $status = 301;
    if (array_key_exists('redirect_video', $wp->query_vars)) {
        $location = get_term_link(DEFAULT_VIDEO_CATEGORY, DEFAULT_VIDEO_TAXONOMY);
        wp_redirect($location, $status);
    }
    return;
}




