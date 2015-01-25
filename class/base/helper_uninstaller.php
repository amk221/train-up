<?php

/**
 * Helper class for uninstalling the plugin
 *
 * @package Train-Up!
 */

namespace TU;

class Uninstaller {

  /**
   * __construct
   *
   * - Load the config, can't do anything without that!
   * - Clear any settings
   * - Remove plugin-specific roles and capabilities
   * - Delete all posts created by Train-Up!
   * - Delete the table that houses the archived Test-result information
   * - Make WordPress forget about the custom post types.
   * - Remove some miscellaneous options that might be left
   * 
   * @access public
   */
  public function __construct() {
    tu()->config = Settings::get_config();
  
    Settings::clear();

    $this->remove_roles_and_capabilities();
    $this->delete_posts();
    $this->delete_archive();
    $this->deregister_post_types();

    delete_option('tu_activated');
    delete_option('tu_installed');
    delete_option('tu_fixtures');
    delete_option('tu_version');
  }

  /**
   * delete_posts
   *
   * - Delete all the Training Levels, their relationships will be deleted
   *   automatically.
   * - Delete the Groups
   * - Delete the Pages
   * 
   * @access public
   */
  public function delete_posts() {
    $args = array('post_status' => get_post_stati());
    
    foreach (Levels::find_all($args) as $level) {
      $level->delete();
    }

    foreach (Groups::find_all($args) as $group) {
      $group->delete();
    }

    foreach (Pages::find_all($args) as $page) {
      $page->delete();
    }
  }

  /**
   * delete_archive
   *
   * Deletes the table that houses the archived Test-result information
   * 
   * @access private
   */
  private function delete_archive() {
    global $wpdb;

    $wpdb->query("
      DROP TABLE IF EXISTS {$wpdb->prefix}tu_archive
    ");
  }

  /**
   * deregister_post_types
   *
   * Tell WordPress to forget about the Custom Post Types
   * 
   * @access private
   */
  private function deregister_post_types() {
    $levels = new Level_post_type;
    $levels->forget();

    $tests = new Test_post_type;
    $tests->forget();

    $groups = new Group_post_type;
    $groups->forget();

    $pages = new Page_post_type;
    $pages->forget();

    do_action('tu_uninstall_cpt');

    Post_type::flush();
  }

  /**
   * remove_roles_and_capabilities
   *
   * - Remove capabilities assigned to the plugin's roles.
   * - Then remove the actual roles, unless they already existed pre-our plugin.
   * 
   * @access private
   */
  private function remove_roles_and_capabilities() {
    foreach (Roles_helper::get_roles() as $role_name => $config) {
      $role = get_role($role_name);

      foreach ($config['capabilities'] as $capability => $yes) {
        $role->remove_cap($capability);
      }

      if (!isset($config['pre_existing'])) {
        remove_role($role_name);
      }
    }
  }

}


 
