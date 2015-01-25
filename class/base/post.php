<?php

/**
 * Abstract class to wrap WP_Post objects
 *
 * @package Train-Up!
 */

namespace TU;

abstract class Post {

  /**
   * $post
   *
   * The actual WP_Post object that this class wraps.
   *
   * @var WP_Post
   *
   * @access protected
   */
  protected $post;

  /**
   * $template_file
   *
   * Name of the template file used to render this type of post
   *
   * @var string
   *
   * @access protected
   */
  protected $template_file = 'single';

  /**
   * $is_active
   *
   * This bool defines whether this post is active. Whether or not
   * a post is active, is set in plugin.php when the post accessor is set up.
   * The global $post object is considered the active post.
   *
   * @var boolean
   *
   * @access private
   */
  private $is_active = false;

  /**
   * __construct
   *
   * Accept either:
   * - An ID of a post
   * - The title of a post
   * - A hash of post data
   * - An actual WP_Post object,
   * Always set a post object, using this class as a wrapper to it.
   *
   * @param object $post
   * @param boolean $active Whether or not this is the active post
   *
   * @access public
   */
  public function __construct($post = null, $active = false) {
    $this->is_active = $active;

    if (is_object($post) && is_a($post, 'WP_Post')) {
      $this->post = $post;
    } else if (is_numeric($post)) {
      $this->post = get_post($post);
    } else if (is_string($post)) {
      $this->post = find_post_by_title($post);
    } else {
      $this->post = new \WP_Post((object)$post);
    }
  }

  /**
   * loaded
   *
   * A post is considered loaded if its title property has been populated
   *
   * @access public
   *
   * @return boolean
   */
  public function loaded() {
    return $this->ID && $this->post_title;
  }

  /**
   * __call
   *
   * Provide a shortcut to methods on the WordPress Post that this class wraps
   *
   * @param
   *
   * @access public
   *
   * @return mixed
   */
  public function __call($method, $args = array()) {
    return call_user_func_array(array($this->post, $method), $args);
  }

  /**
   * __get
   *
   * Provide shortcuts to getting this Post's internal properties
   * and its relations.
   *
   * @param string $property
   *
   * @access public
   *
   * @return mixed
   */
  public function __get($property) {
    $getter = "get_{$property}";
    if (method_exists($this, $getter)) {
      return $this->$getter();
    }

    if (is_object($this->post) && property_exists($this->post, $property)) {
      return $this->post->$property;
    }
  }

  /**
   * __set
   *
   * Provide shortcuts to setting this Post's internal properties
   *
   * @param string $property
   * @param mixed $value
   *
   * @access public
   */
  public function __set($property, $value) {
    $this->post->$property = $value;
  }

  /**
   * get_template_file
   *
   * @access public
   *
   * @return string The file name of the template used to render Post.
   */
  public function get_template_file() {
    return $this->template_file;
  }

  /**
   * is_active
   *
   * - Returns whether or not this post is active. i.e. it is the actual global
   *   post instance attached to the TU singleton, and it is being viewed
   *   on the frontend.
   * - When a post is active, it is able to add actions and filters specific
   *   to itself, useful.
   *
   * @see register_global_post
   * @access public
   *
   * @return boolean
   */
  public function is_active() {
    return !is_admin() && $this->is_active;
  }

  /**
   * set_data
   *
   * Update this instance with the properties provided. This is mostly for
   * trickling data down into the post object that this class wraps.
   *
   * @param array $data
   *
   * @access private
   */
  private function set_data($data) {
    foreach ((object)$data as $property => $value) {
      $this->$property = $value;
    }
  }

  /**
   * save
   *
   * - Insert or update the user accordingly
   * - Make sure the post name (and hence URL) is always based on the title
   *   (we don't want the user to be able to change it)
   *
   * @param array $data A hash of properties to update
   *
   * @access public
   */
  public function save($data = array()) {
    $this->set_data($data);

    $this->post_name = sanitize_title($this->post_title);

    if ($this->ID) {
      wp_update_post($this->post);
    } else {
      $this->ID = wp_insert_post($this->post);
    }
  }

  /**
   * delete
   *
   * Delete this post
   *
   * @access public
   *
   * @return mixed False on failure
   */
  public function delete() {
    wp_delete_post($this->ID, true);
  }

  /**
   * trash
   *
   * Trash this post
   *
   * @access public
   *
   * @return mixed False on failure
   */
  public function trash() {
    wp_trash_post($this->ID);
  }

  /**
   * untrash
   *
   * Untrash this post
   *
   * @access public
   *
   * @return mixed False on failure
   */
  public function untrash() {
    wp_untrash_post($this->ID);
  }

  /**
   * get_breadcrumb_trail
   *
   * Returns an array of crumbs used to find this post for use on the front end.
   *
   * @access public
   *
   * @return array
   */
  public function get_breadcrumb_trail() {
    $crumbs = array(
      array(
        'title' => tu()->config['general']['title'],
        'url'   => Pages::factory('My_account')->url
      )
    );

    foreach (array_reverse(get_post_ancestors($this->ID)) as $post_id) {
      $crumbs[] = array(
        'url'   => get_permalink($post_id),
        'title' => get_the_title($post_id)
      );
    }

    $crumbs[] = array(
      'url'   => $this->url,
      'title' => $this->post_title
    );

    $result = apply_filters('tu_post_crumbs', $crumbs);

    return $result;
  }

  /**
   * bail
   *
   * - Fired when access to this post has been denied
   * - Fire an action so developers can latch on to failures
   * - Set a flash message
   * - Override the template to be an error-specific page, useful for styling.
   * - Prevent content from being rendered
   * - Prevent any comments from being rendered
   * - Allow the developer to output their own error-page specific content with
   *   the use of tu_bail_content filter
   *
   * - Important note it is up to developers to make sure unauthorised attempts
   *   to access a page do not show any content they shouldn't. The best way
   *   to do this is to utilise tu_error.php as your template.
   *
   * @param mixed $error
   *
   * @access public
   *
   * @return mixed Value.
   */
  public function bail($error) {
    do_action('tu_bail');

    tu()->message->set_flash('error', $error);
    $this->template_file = 'tu_error';

    $false = function() {
      return apply_filters('tu_bail_content', false);
    };

    add_filter('the_content', $false);
    add_filter('tu_comments', '__return_false');
  }

  /**
   * go_to
   *
   * Redirect to this actual post.
   *
   * @access public
   */
  public function go_to() {
    go_to($this->url);
  }

  /**
   * get_url
   *
   * @access public
   *
   * @return string The permalink URL to this page
   */
  public function get_url() {
    // get_option('permalink_structure')
    return get_permalink($this->ID);
  }

  /**
   * get_depth
   *
   * @access public
   *
   * @return integer The amount of ancestors this post has
   */
  public function get_depth() {
    return count(get_post_ancestors($this->ID));
  }

  /**
   * get_featured_image
   *
   * Wrapper for finding out a posts featured image. Use WordPress's featured
   * images (i.e. the post 'thumbnail'), but if one isn't provided, try to
   * get the featured image from a parent post. This is so that nested posts
   * will inherit the image.
   *
   * @access public
   *
   * @return string The url of the image
   */
  public function get_featured_image() {
    $post_ids = get_post_ancestors($this->ID);
    array_unshift($post_ids, $this->ID);

    foreach ($post_ids as $post_id) {
      $thumbnail_id = get_post_thumbnail_id($post_id);

      if ($thumbnail_id) {
        list($src) = wp_get_attachment_image_src($thumbnail_id, 'post-thumbnail', false, '');
        return $src;
      }
    }
  }

  /**
   * get_parent
   *
   * @access public
   *
   * @return object|null The immediate parent post
   */
  public function get_parent() {
    if ($this->depth > 0) {
      return new $this($this->post->ancestors[0]);
    }
  }

  /**
   * get_next
   *
   * Overridden in child classes, because logic is dependant on the post type.
   * e.g. Finding the next Question is different to finding the next Level.
   *
   * @access public
   *
   * @return null The next post in line
   */
  public function get_next() {
    return null;
  }

  /**
   * get_prev
   *
   * Overridden in child classes, because logic is dependant on the post type.
   * e.g. Finding the previous Resource is different to finding the previous Level
   *
   * @access public
   *
   * @return null The previous post in line
   */
  public function get_prev() {
    return null;
  }

  /**
   * get_children
   *
   * @access public
   *
   * @return mixed Value.
   */
  public function get_children() {
    $children = get_children(array(
      'numberposts' => -1,
      'post_parent' => $this->ID,
      'post_status' => 'publish',
      'post_type'   => $this->post_type,
      'output'      => OBJECT
    ));

    if (count($children) > 0) {
      $class_name = get_class($this);
      $func       = create_function('$post', "return new {$class_name}(\$post);");
      $children   = array_map($func, $children);
    }

    return $children;
  }

}



