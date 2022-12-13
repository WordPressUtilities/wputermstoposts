<?php

/*
Plugin Name: WPU Terms to Posts
Description: Link terms to posts from the term edit page.
Version: 0.8.1
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUTermsToPosts {
    private $taxonomies;
    private $version = '0.8.1';
    private $order_list = array(
        'desc' => 'DESC',
        'asc' => 'ASC'
    );

    public function __construct() {
        add_action('plugins_loaded', array(&$this,
            'plugins_loaded'
        ), 99);
        add_action('admin_init', array(&$this,
            'admin_init'
        ), 99);
    }

    public function plugins_loaded() {
        if (!is_admin()) {
            return;
        }

        if (!load_plugin_textdomain('wputermstoposts', false, dirname(plugin_basename(__FILE__)) . '/lang/')) {
            load_muplugin_textdomain('wputermstoposts', dirname(plugin_basename(__FILE__)) . '/lang/');
        }
    }

    public function admin_init() {
        $this->taxonomies = apply_filters('wputtp_taxonomies', array());
        add_action('admin_enqueue_scripts', array(&$this, 'load_assets'));

        foreach ($this->taxonomies as $id => $taxonomy) {
            $taxonomy_item = get_taxonomy($id);
            if (!$taxonomy_item) {
                continue;
            }

            /* Default post types */
            if (!isset($taxonomy['post_types'])) {
                $this->taxonomies[$id]['post_types'] = $taxonomy_item->object_type;
            }
            /* Add actions */
            add_action($id . '_add_form_fields', array(&$this, 'add_fields'), 10);
            add_action($id . '_edit_form_fields', array(&$this, 'add_fields'), 10);
            add_action('create_' . $id, array(&$this, 'save_term_posts'));
            add_action('edited_' . $id, array(&$this, 'save_term_posts'));
        }

    }

    /* ----------------------------------------------------------
      Load assets
    ---------------------------------------------------------- */

    public function load_assets($hook) {
        if (!in_array($hook, array('term.php', 'edit-tags.php'))) {
            return;
        }

        wp_enqueue_script('wputermstoposts_scripts', plugins_url('assets/script.js', __FILE__), array('jquery'), $this->version);
        wp_enqueue_style('wputermstoposts_style', plugins_url('assets/style.css', __FILE__), false, $this->version);
    }

    /* ----------------------------------------------------------
      Edit form
    ---------------------------------------------------------- */

    public function add_fields() {
        $screen = get_current_screen();

        $sort_attributes = array(
            'data-post-date' => 'Post Date',
            'data-post-title' => 'Post Title',
            'data-post-id' => 'Post ID'
        );
        global $tag;
        $current_tag = false;
        if ($screen->base == 'edit-tags' && isset($_GET['taxonomy'])) {
            $current_tag = $_GET['taxonomy'];
        }
        if (is_object($tag)) {
            $current_tag = $tag->taxonomy;
        } else {
            $tag = false;
        }
        if (!$current_tag || !isset($this->taxonomies[$current_tag])) {
            return;
        }
        $current_tax = $this->taxonomies[$current_tag];
        foreach ($current_tax['post_types'] as $post_type) {
            $post_type_info = get_post_type_object($post_type);

            /* Currently selected posts */
            $selected_posts = $tag ? $this->get_posts_for_term($post_type, $tag) : array();

            /* All posts */
            $posts = get_posts(array(
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'any'
            ));

            $label = '<label>' . $post_type_info->label . '</label>';
            if ($screen->base == 'term') {
                echo '<tr><th>' . $label . '</th><td>';
            } else {
                echo '<div class="form-field">' . $label;
            }
            echo '<div class="wputermstoposts-wrapper"><ul class="wputermstoposts_list">';
            $field_base_id = 'wputermstoposts_' . $post_type;
            foreach ($posts as $post) {
                $checked = in_array($post->ID, $selected_posts) ? ' checked' : '';
                $field_id = $field_base_id . '_' . $post->ID;
                $attributes = array(
                    'data-post-date' => get_the_time('U', $post),
                    'data-post-title' => strtolower($post->post_title),
                    'data-post-id' => $post->ID
                );
                $attributes_html = '';
                foreach ($attributes as $attr_key => $attr_val) {
                    $attributes_html .= ' ' . $attr_key . '="' . esc_attr($attr_val) . '"';
                }
                $status = get_post_status_object($post->post_status);
                echo '<li ' . $attributes_html . '>';
                echo '<label title="' . esc_attr(sprintf('ID #%s - %s', $post->ID, $post->post_date)) . '">';
                echo '<input id="' . $field_id . '" type="checkbox" name="' . $field_base_id . '[]" value="' . $post->ID . '"' . $checked . ' />';
                echo ' ' . esc_html($post->post_title);
                if (function_exists('pll_get_post_language')) {
                    echo ' [' . pll_get_post_language($post->ID) . ']';
                }
                if ($status) {
                    echo ' <small>(' . $status->label . ')</small>';
                }
                echo '</label>';
                echo '</li>';
            }
            echo '</ul>';
            echo '<div class="wputermstoposts-filters">';
            echo '<label>' . __('Filter:', 'wputermstoposts') . ' ';
            echo '<input type="text" class="wputermstoposts_filter" id="' . $field_base_id . '_filter"/></label>';
            echo '<label>' . __('Order:', 'wputermstoposts') . ' ';
            echo '<select class="wputermstoposts_order" id="' . $field_base_id . '_order">';
            foreach ($sort_attributes as $attr_id => $attr_name) {
                foreach ($this->order_list as $order_id => $order_name) {
                    echo '<option data-order="' . $order_id . '" name="' . $attr_id . '">' . $attr_name . ' - ' . $order_name . '</option>';
                }
            }
            echo '</select>';
            echo '</label>';
            $total = count($posts);
            echo ' <span class="wputermstoposts-filters__count"><span class="count" data-total="' . $total . '">' . $total . '</span>/' . $total . '</span>';
            echo '</div>';
            echo '</div>';

            if ($screen->base == 'term') {
                echo '</td></tr>';
            } else {
                echo '</div>';
            }

        }
    }

    /* ----------------------------------------------------------
      Save posts
    ---------------------------------------------------------- */

    public function save_term_posts($term_id) {
        $term = get_term_by('term_taxonomy_id', $term_id);
        $current_tax = $this->taxonomies[$term->taxonomy];
        foreach ($current_tax['post_types'] as $post_type) {
            if (!isset($_POST['wputermstoposts_' . $post_type])) {
                return;
            }
            $selected_posts = $_POST['wputermstoposts_' . $post_type];
            if (!is_array($selected_posts)) {
                return;
            }

            /* Remove selected posts which are not checked */
            $currently_selected_posts = $this->get_posts_for_term($post_type, $term);
            foreach ($currently_selected_posts as $post_id) {
                if (!in_array($post_id, $selected_posts)) {
                    wp_remove_object_terms($post_id, $term->term_id, $term->taxonomy);
                }
            }

            /* Add posts which are checked */
            foreach ($selected_posts as $post_id) {
                if (!in_array($post_id, $currently_selected_posts) && get_post_type($post_id) == $post_type) {
                    wp_add_object_terms(intval($post_id, 10), $term->term_id, $term->taxonomy);
                }
            }
        }
    }

    /* ----------------------------------------------------------
      Helper
    ---------------------------------------------------------- */

    public function get_posts_for_term($post_type, $term) {
        return get_posts(array(
            'fields' => 'ids',
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'tax_query' => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field' => 'term_id',
                    'terms' => $term->term_id
                )
            )
        ));
    }

}

$WPUTermsToPosts = new WPUTermsToPosts();
