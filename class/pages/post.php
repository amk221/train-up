<?php

/**
 * A class to represent a single Page WP_Post
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class Page extends Post {
  
  /**
   * $template_file
   *
   * The template file used to render this type of post.
   *
   * @var string
   *
   * @access protected
  */
  protected $template_file = 'tu_page';

  /**
   * $view_data
   *
   * Hash of view data to send to the extra view that is
   * appended to this page
   *
   * @var array
   *
   * @access protected
   */
  protected $view_data = array();

  /**
   * $post_data
   *
   * The default post properties to be used when creating this
   * class for the first time.
   *
   * @var array
   *
   * @access protected
   */
  protected $post_data = array();

  /**
   * $auth_required
   *
   * Whether or not this Page should bail if there is no authenticated user.
   *
   * @var boolean
   *
   * @access protected
   */
  protected $auth_required = false;

  /**
   * $default_post_data
   *
   * WordPress Post property defaults for all Pages.
   *
   * @var array
   *
   * @access protected
   */
  protected $default_post_data = array(
    'post_type'      => 'tu_page',
    'comment_status' => 'closed',
    'post_status'    => 'publish'
  );

  /**
   * __construct
   *
   * - Attempt to instantiate the Page (post) as normal, but then
   *   also offer an alternative way to load it - by class name.
   * - If this page hasn't managed to be loaded, always create it (self healing)
   * - If this page is active, check it is allowed to be viewed.
   * 
   * @param mixed $page
   * @param boolean $active
   *
   * @access public
   */
  public function __construct($page = null, $active = false) {
    parent::__construct($page, $active);
    
    if (is_string($page)) {
      $this->load_by_class_name($page);
    }
    if (!$this->ID) {
      $this->create();
    }
    if ($this->is_active()) {
      list($ok, $error) = $this->pre_process();

      if ($ok) {
        
      }
    }
  }

  /**
   * pre_process
   *
   * - Fired when this page is active. 
   * - If this page is marked as protected, then prevent access unless logged in
   * 
   * @access private
   *
   * @return array
   */
  private function pre_process() {
    $error = '';

    if ($this->auth_required && !current_user_can('tu_frontend')) {
      Pages::factory('Login')->go_to();
    }

    return array(!$error, $error);
  }

  /**
   * get_initial_data
   * 
   * @access private
   *
   * @return array This class' data merged with the defaults for a TU page.
   */
  private function get_initial_data() {
    return array_merge($this->post_data, $this->default_post_data);
  }

  /**
   * create
   *
   * - Fired if this page has no ID. It is a Train-Up! page, and therefore
   *   has an associated PHP class, and should always exist in the system. So,
   *   this function ensures it always does.
   * - Only automatically make the post if the user is an administrator, we 
   *   don't want posts to be created with random lower level author IDs
   * 
   * @access private
   */
  private function create() {
    if (current_user_can('administrator')) {
      $this->save($this->get_initial_data());
      add_post_meta($this->ID, 'tu_class', addslashes(get_class($this)), true);
    }
  }

  /**
   * load_by_class_name
   *
   * - Provide a way of loading an instance of a page by its class name, so we
   *   can always get access to specific page functionality even if its post
   *   title changes.
   * - Note: We don't use get_posts because if other plugins are using
   *   pre_get_posts filters then the post being requested here might not be
   *   found, but it *always* needs to be found because Train-Up! relies on its
   *   specific pages.
   * 
   * @param string $class_name
   *
   * @access private
   */
  private function load_by_class_name($class_name) {
    global $wpdb;

    $class_name = __NAMESPACE__."\\{$class_name}_page";
    $this->post = wp_cache_get($class_name, '', false, $found);

    if ($found) return;

    $sql = "
      SELECT *
      FROM   {$wpdb->posts} p
      JOIN   {$wpdb->postmeta} pm
      ON     p.ID = pm.post_id
      WHERE  pm.meta_key   = 'tu_class'
      AND    pm.meta_value = %s
      LIMIT  1
    ";

    $pages = $wpdb->get_results($wpdb->prepare($sql, $class_name));

    if (count($pages) === 1) {
      $this->post = $pages[0];
      wp_cache_set($class_name, $this->post);
    }
  }

}

