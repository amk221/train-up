<?php

/**
 * A class to represent a single Level WP_Post
 *
 * @package Train-Up!
 * @subpackage Levels
 */

namespace TU;

class Level extends Post {

  /**
   * $template_file
   *
   * The template file that is used to render Levels.
   *
   * @var string
   *
   * @access protected
   */
  protected $template_file = 'tu_level';

  /**
   * __construct
   *
   * When a Level is instantiated construct the post as normal, then if it is
   * actually active, check the permissions.
   *
   * @param mixed $post
   * @param boolean $active
   *
   * @access public
   */
  public function __construct($post, $active = false) {
    parent::__construct($post, $active);

    if ($this->is_active()) {
      list($ok, $error) = tu()->user->can_access_level($this);

      if ($ok) {

      } else {
        $this->bail($error);
      }
    }
  }

  /**
   * get_resources
   *
   * @param array $args
   *
   * @access public
   *
   * @return array The resource posts associated with this level
   */
  public function get_resources($args = array()) {
    $args = array_merge(array(
      'numberposts' => -1,
      'post_type'   => "tu_resource_{$this->ID}",
      'post_status' => 'publish',
      'meta_key'    => 'tu_level_id',
      'meta_value'  => $this->ID,
      'orderby'     => 'ID menu_order post_title',
      'order'       => 'ASC'
    ), $args);

    return get_posts_as('Resources', $args);
  }

  /**
   * get_test
   *
   * @param array $args
   *
   * @access public
   *
   * @return object|null The Test post associated with this Level
   */
  public function get_test($args = array()) {
    $args = array_merge(array(
      'post_type'   => 'tu_test',
      'post_status' => 'publish',
      'numberposts' => 1,
      'meta_key'    => 'tu_level_id',
      'meta_value'  => $this->ID
    ), $args);

    $tests = get_posts_as('Tests', $args);

    return (count($tests) > 0) ? $tests[0] : null;
  }

  /**
   * get_groups
   *
   * @access public
   *
   * @return array The Group posts associated with this Level
   */
  public function get_groups() {
    return Groups::find_all(array(
      'include' => $this->group_ids
    ));
  }

  /**
   * set_group_ids
   *
   * Save the IDs of Group posts that you wish to be associated with this Level
   *
   * @param array $group_ids
   *
   * @access public
   */
  public function set_group_ids($group_ids = array()) {
    delete_post_meta($this->ID, 'tu_group');

    foreach ($group_ids as $group_id) {
      $this->add_to_group($group_id);
    }
  }

  /**
   * get_group_ids
   *
   * @access public
   *
   * @return array IDs of group posts associated with this Level
   */
  public function get_group_ids() {
    return get_post_meta($this->ID, 'tu_group');
  }

  /**
   * has_group
   *
   * Accept the ID of a Group, and return whether or not this Level is
   * associated with it.
   *
   * @param integer $group_id
   *
   * @access public
   *
   * @return boolean
   */
  public function has_group($group_id) {
    return in_array($group_id, (array)$this->group_ids);
  }

  /**
   * add_to_group
   *
   * Associate this Level with a Group
   *
   * @param array $group_id
   *
   * @access public
   */
  public function add_to_group($group_id) {
    if (!$this->has_group($group_id)) {
      add_post_meta($this->ID, 'tu_group', $group_id);
    }
  }

  /**
   * set_eligibility_config
   *
   * Accept an array that is the configuration that defines whether or not
   * a Trainee is eligible for accessing the Level and its relations.
   * Currently, this consists of just some test IDs, although in future we
   * may want to add other eligibility options.
   *
   * @param array $data
   *
   * @access public
   */
  public function set_eligibility_config($config = array()) {
    update_post_meta($this->ID, 'tu_eligibility_config', $config);
  }

  /**
   * get_eligibility_config
   *
   * @access public
   *
   * @return array The config that defines how a Level is determined to be
   * accessible by a Trainee
   */
  public function get_eligibility_config() {
    $defaults = array('test_ids' => array());
    $config   = (array)get_post_meta($this->ID, 'tu_eligibility_config', true);
    $config   = array_replace_recursive($defaults, $config);

    return $config;
  }

  /**
   * save
   *
   * - When this Level is saved, first load its Test if it has one, and make
   *   sure the Test's title and menu order are the same as the Level.
   * - Create a new Resource post type specifically for this Level, so it
   *   can have its own batch of resource posts.
   *
   * @param array $data
   *
   * @access public
   */
  public function save($data = array()) {
    parent::save($data);

    if ($this->test) {
      $this->test->save(array(
        'post_title' => $this->post_title,
        'menu_order' => $this->menu_order
      ));
    }

    if ($this->post_status !== 'auto-draft') {
      $post_type = new Resource_post_type($this->ID);
      $post_type->cache();
    }
  }

  /**
   * delete
   *
   * - When a Level is deleted, make WordPress forget about the dynamic resource
   *   post type that is associated with this Level
   * - Delete all resource posts for this level
   * - Delete the associated test (if there is one)
   *
   * @param boolean $hard Delete the actual post
   *
   * @access public
   */
  public function delete($hard = true) {
    $this->delete_resources();
    $this->delete_test();

    $post_type = new Resource_post_type($this->ID);
    $post_type->forget();

    if ($hard) {
      parent::delete();
    }
  }

  /**
   * trash
   *
   * When a Level is trashed, trash its associated Resources and Test too.
   *
   * @param boolean $hard Trash the actual post
   *
   * @access public
   */
  public function trash($hard = true) {
    $this->trash_resources();
    $this->trash_test();

    if ($hard) {
      parent::trash();
    }
  }

  /**
   * untrash
   *
   * When a Level is untrashed, untrash its associated Resources and Test too.
   *
   * @param boolean $hard Untrash the actual post
   *
   * @access public
   */
  public function untrash($hard = true) {
    $this->untrash_resources();
    $this->untrash_test();

    if ($hard) {
      parent::untrash();
    }
  }

  /**
   * delete_test
   *
   * Load this Level's associated Test, if there is one then delete it.
   *
   * @access private
   */
  private function delete_test() {
    $test = $this->get_test(array('post_status' => get_post_stati()));

    if ($test) {
      $test->delete();
    }
  }

  /**
   * trash_test
   *
   * Load this Level's associated Test, if there is one then trash it
   *
   * @access private
   */
  private function trash_test() {
    $test = $this->get_test(array('post_status' => get_post_stati()));

    if ($test) {
      $test->trash();
    }
  }

  /**
   * untrash_test
   *
   * Load this Level's associated Test, if there is one then untrash it
   *
   * @access private
   */
  private function untrash_test() {
    $test = $this->get_test(array('post_status' => get_post_stati()));

    if ($test) {
      $test->untrash();
    }
  }

  /**
   * delete_resources
   *
   * Load this Level's associated Resources. Go through each one and delete
   * them.
   *
   * @access private
   */
  private function delete_resources() {
    $resources = $this->get_resources(array('post_status' => get_post_stati()));

    foreach ($resources as $resource) {
      $resource->delete();
    }
  }

  /**
   * trash_resources
   *
   * Load this Level's associated Resources. Go through each one and trash it.
   *
   * @access private
   */
  private function trash_resources() {
    $resources = $this->get_resources(array('post_status' => get_post_stati()));

    foreach ($resources as $resource) {
      $resource->trash();
    }
  }

  /**
   * untrash_resources
   *
   * Load this Level's associated Resources. Go through each one and untrash it.
   *
   * @access private
   */
  private function untrash_resources() {
    $resources = $this->get_resources(array('post_status' => get_post_stati()));

    foreach ($resources as $resource) {
      $resource->untrash();
    }
  }

}



