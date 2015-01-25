<?php

/**
 * Abstract class to help with creating custom post types
 *
 * @package Train-Up!
 */

namespace TU;

abstract class Post_type {  

  /**
   * $slug
   *
   * The base name for this post type, usually the same as the $name, unless
   * the post type is dynamic.
   *
   * @var string
   *
   * @access public
   */
  public $slug = '';

  /**
   * $name
   *
   * The name of the post type
   * e.g. tu_level if not dynamic (same as slug)
   * and tu_resource_[ID] if dynamic.
   *
   * @var string
   *
   * @access public
   */
  public $name = '';

  /**
   * $options
   *
   * The hash of options used to configure the post type for WordPress
   * (basically the params to send to WordPress' `register_post_type` func).
   *
   * @var array
   *
   * @access public
   */
  public $options = array();

  /**
   * $shortcodes
   *
   * A hash of shortcodes that this post type has.
   * (Each shortcode automatically has a corresponding function)
   *
   * @var array
   *
   * @access public
   */
  public $shortcodes = array();

  /**
   * $is_dynamic
   *
   * A simple marker as to whether or not this post type is one that exists only
   * because a user has created a post, and in turn a dynamic post type has
   * been created, based upon that post.
   *
   * @var boolean
   *
   * @access public
   */
  public $is_dynamic = false;

  /**
   * get_key
   * 
   * @access protected
   *
   * @return string A unique key to represent this post type when it is cached.
   */
  protected function get_key() {
    return "tu_post_type_{$this->name}";
  }

  /**
   * register
   *
   * - Register this post type with WordPress.
   * - Note, this function must remain light weight because potentially *lots*
   *   of post types will be registered on each request.
   * - i.e. Don't do something daft like put flush_rewrite_rules in here.
   * 
   * @access public
   */
  public function register() {
    $this->register_post_type();
    $this->register_shortcodes();
  }

  /**
   * set_options
   *
   * Set the options for this post type, so that when it is serialised the
   * options are stored (i.e. they're not stuck in a function)
   * 
   * @access protected
   */
  protected function set_options() {
    $this->options = array();
  }

  /**
   * set_shortcodes
   *
   * Set the shortcodes for this post type, so that when it is serialized the
   * shortcodes are stored (i.e. they're not stuck in a function)
   * 
   * @access protected
   */
  protected function set_shortcodes() {
    $this->shortcodes = array();
  }

  /**
   * refresh
   *
   * Update the $options and the $shortcodes for this post type instance,
   * so that it is primed and ready to be cached.
   * 
   * @access public
   */
  public function refresh() {
    $this->set_options();
    $this->set_shortcodes();
  }

  /**
   * cache
   *
   * - First refresh this post type so it is primed and ready to be cached then,
   *   schedule `flush_rewrite_rules()` to be called on next request.
   *   (i.e. so as to not repeatedly call it).
   * - The plugin will then register the cached post types and flush the rewrite
   *   rules so they spring into life.
   * - Cache this post type (serialize it in the db).
   * 
   * @access public
   */
  public function cache() {
    $this->refresh();
    $this->schedule_rewrite_flush();
    update_option($this->get_key(), $this);
  }

  /**
   * forget
   *
   * Delete this post type from the cache, and schedule `flush_rewrite_rules()`
   * to be called on next request so that WordPress no longer knows about this
   * post type and its associated routes.
   * 
   * @access public
   */
  public function forget() {
    delete_option($this->get_key());
    $this->schedule_rewrite_flush();
  }

  /**
   * register_post_type
   *
   * - Tell WordPress about this post type!
   * - Make the post type filterable for other developers.
   * 
   * @access private
   */
  private function register_post_type() {
    $this->options = apply_filters("{$this->slug}_options", $this->options);
    
    register_post_type($this->name, $this->options);
  }

  /**
   * register_shortcodes
   *
   * - Go through each of this post type's shortcodes
   *   and tell WordPress about it.
   * - We add two actual shortcodes, one which is the default shortcode
   *   and another which is an alternative, necessary if the user has
   *   made customisations to the name of models in system.
   *   e.g. [has_test] might also actually be [has_exam]
   * 
   * @access private
   */
  private function register_shortcodes() {
    foreach ($this->shortcodes as $name => $details) {
      add_shortcode($name, array($this, '_add_shortcode'));
      add_shortcode($details['shortcode'], array($this, '_add_shortcode'));
    }
  }

  /**
   * get_original_shortcode
   *
   * - Accept an alternative shortcode.
   * - Find it amongst this post types' default shortcodes.
   * 
   * @param string $shortcode
   *
   * @access private
   *
   * @return string
   */
  private function get_original_shortcode($shortcode) {
    $tag = $shortcode;
    foreach ($this->shortcodes as $name => $details) {
      if ($details['shortcode'] == $shortcode) {
        $tag = $name;
        break;
      }
    }
    return $tag;
  }

  /**
   * _add_shortcode
   *
   * - Fired when WordPress is calling a shortcode.
   * - Find the appropriate callback for this shortcode and fire it.
   * 
   * @param array $attributes
   * @param string $content
   * @param string $tag
   *
   * @access private
   *
   * @return mixed
   */
  public function _add_shortcode($attributes, $content, $tag) {
    $tag        = $this->get_original_shortcode($tag);
    $shortcodes = $this->shortcodes;
    $defaults   = $shortcodes[$tag]['attributes'];
    $func       = str_replace('!', 'not_', $tag);
    $func       = "shortcode_{$func}";
    $attributes = shortcode_atts($defaults, $attributes);

    return $this->$func($attributes, $content, $tag);
  }

  /**
   * find_cached
   *
   * - Find all the cached post types in the db and unserialize them.
   * - Note: tu_pages must come last in the list, because when the rules
   *   are registered, tu_pages (/training) should be that last routes to check.
   * 
   * @access public
   * @static
   *
   * @return array
   */
  public static function find_cached() {
    global $wpdb;

    $sql = "
      SELECT option_name  AS name,
             option_value AS post_type
      FROM   {$wpdb->options}
      WHERE  option_name LIKE('tu_post_type%')
    ";

    $post_types = array();

    foreach ($wpdb->get_results($sql) as $row) {
      $post_types[$row->name] = unserialize($row->post_type);
    }

    usort($post_types, array(__CLASS__, '_sort_post_types'));

    return $post_types;
  }

  /**
   * _sort_post_types
   *
   * Used to sort an array of post types with the ones who have the longest
   * slug first. This is so that their rewrite rules are matched first.
   * 
   * @param mixed $a
   * @param mixed $b
   *
   * @access public
   * @static
   *
   * @return bool
   */
  public static function _sort_post_types($a, $b) {
    return (
      substr_count($a->options['rewrite']['slug'], '/') <
      substr_count($b->options['rewrite']['slug'], '/')
    );
  }

  /**
   * refresh_cache
   *
   * - Go through each post type and re-cache it.
   * - This function is necessary because when the user changes the
   *   singular/plural names of the post types, the rewrite rules need to
   *   be updated.
   * - This function is quite expensive because each dynamic post types'
   *   post will be loaded.
   * 
   * @access public
   * @static
   */
  public static function refresh_cache() {
    $post_types = self::find_cached();

    foreach ($post_types as $name => $post_type) {
      $post_type->cache();
    }
  }

  /**
   * schedule_rewrite_flush
   *
   * Rather than calling `flush_rewrite_rules()` each time a post type is cached
   * just do it once because its expensive.
   * 
   * @access private
   */
  private function schedule_rewrite_flush() {
    add_option('tu_flush_rewrite_rules', true);
  }

  /**
   * flush_rewrite_rules
   *
   * Flush the rewrite rules, then de-schedule further attempts.
   * 
   * @access private
   */
  public static function flush() {
    flush_rewrite_rules(false);
    delete_option('tu_flush_rewrite_rules');
  }

}
