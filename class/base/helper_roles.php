<?php

/**
 * Helper class for setting up the roles requried by the plugin
 *
 * @package Train-Up!
 */

namespace TU;

class Roles_helper {

  /**
   * __construct
   * 
   * - Fired on `after_setup_theme` (after the global $wp_roles is created)
   * - Add/remote/update the roles that Train-Up!
   *
   * @access public
   */
  public function __construct() {
    add_action('after_setup_theme', array($this, '_set_up_roles'));
  }

  /**
   * _set_up_roles
   *
   * Go through the role configurations, and tell WordPress what permissions
   * each type of user should have. Create the role if it doesn't already 
   * exist and rename the role if it does it exist to the current title.
   *
   * @access private
   */
  public function _set_up_roles() {
    global $wp_roles;

    foreach (self::get_roles() as $role_name => $config) {
      $role = get_role($role_name);

      if ($role) {
        $wp_roles->roles[$role_name]['name'] = $config['title'];
      } else {
        $role = add_role($role_name, $config['title']);
      }

      foreach ($config['capabilities'] as $capability => $yes) {
        if ($yes) {
          $role->add_cap($capability);
        } else {
          $role->remove_cap($capability);
        }
      }
    }
  }

  /**
   * get_administrator_role
   * 
   * @access public
   * @static
   *
   * @return array Administrator role configuration
   */
  public static function get_administrator_role() {
    $pre_existing = true;
    $class_name   = 'Administrator';
    $title        = __('Administrator', 'trainup');
    $description  = __('Full ability to administer the plugin', 'trainup');
    $capabilities = array_merge(self::full_post_type_permissions(), array(
      'tu_backend'        => true,
      'tu_debugger'       => true,
      'tu_emailer'        => true,
      'tu_settings'       => true,
      'tu_trainees'       => true,
      'tu_group_managers' => true,
      'tu_frontend'       => true
    ));

    $config = compact('pre_existing', 'class_name', 'title', 'description', 'capabilities');

    return $config;
  }

  /**
   * get_group_manager_role
   *
   * Group managers capabilities can be customised via the Settings page.
   * Below, we loop through the permissions specified by the config and if
   * they relate to a post type, then we add full editing/deleting capability.
   * 
   * @access public
   * @static
   *
   * @return Group Manager role config
   */
  public static function get_group_manager_role() {
    $class_name     = 'Group_manager';
    $title          = tu()->config['group_managers']['single'];
    $description    = sprintf(
      __('Have the ability to view results of %1$s within their %2$s', 'trainup'),
      strtolower(tu()->config['tests']['single']),
      strtolower(tu()->config['groups']['plural'])
    );
    $capabilities   = array(
      'tu_frontend' => true,
      'tu_backend'  => true,
      'tu_emailer'  => true,
      'tu_trainees' => true,
      'read'        => true,
      'edit_posts'  => true,
      'list_users'  => true,
      // Results
      'read_private_tu_results'     => true,
      'publish_tu_results'          => true,
      'edit_tu_results'             => true,
      'edit_others_tu_results'      => true,
      'edit_private_tu_results'     => true,
      'edit_published_tu_results'   => true,
      'delete_tu_results'           => false,
      'delete_private_tu_results'   => false,
      'delete_published_tu_results' => false,
      'delete_others_tu_results'    => false,
      // Groups
      'read_private_tu_groups'     => true,
      'publish_tu_groups'          => false,
      'edit_tu_groups'             => true,
      'edit_others_tu_groups'      => false,
      'edit_private_tu_groups'     => true,
      'edit_published_tu_groups'   => true,
      'delete_tu_groups'           => false,
      'delete_private_tu_groups'   => false,
      'delete_published_tu_groups' => false,
      'delete_others_tu_groups'    => false
    );

    $config = compact('class_name', 'title', 'description', 'capabilities');

    return apply_filters('tu_group_manager_role', $config);
  }

  /**
   * get_trainee_role
   * 
   * @access public
   * @static
   *
   * @return array
   */
  public static function get_trainee_role() {
    $class_name  = 'Trainee';
    $title       = tu()->config['trainees']['single'];
    $description = sprintf(
      __('Have the ability to view %1$s, %2$s, and take %3$s', 'trainup'),
      strtolower(tu()->config['levels']['plural']),
      strtolower(tu()->config['resources']['plural']),
      strtolower(tu()->config['tests']['plural'])
    );
    $capabilities = array(
      'tu_frontend' => true
    );

    $config = compact('class_name', 'title', 'description', 'capabilities');

    return apply_filters('tu_trainee_role', $config);
  }

  /**
   * get_roles
   *
   * Define the 3 main roles the plugin requires to work and specify the
   * capabilities for each one.
   * 
   * @access public
   *
   * @return array
   */
  public static function get_roles() {
    $administrator    = self::get_administrator_role();
    $tu_group_manager = self::get_group_manager_role();
    $tu_trainee       = self::get_trainee_role();

    return compact('administrator', 'tu_group_manager', 'tu_trainee');
  }

  /**
   * full_post_type_permissions
   *
   * * Rather than typing out the permissions by hand just return a hash with all
   *   the post type capabilities set to true. 
   * * Optionally accept a specific post type to get full permissions for.
   *
   * @access private
   *
   * @param $post_type
   * @param $yes Whether or not the permission is allowed
   *
   * @return array
   */
  private static function full_post_type_permissions($post_type = null, $yes = true) {
    $permissions = array();
    $post_types  = $post_type ? array($post_type) : get_known_post_type_names();

    foreach ($post_types as $type) {
      $type = strtolower("tu_{$type}");

      $permissions = array_merge($permissions, array(
        "read_private_{$type}"     => $yes,
        "publish_{$type}"          => $yes,
        "edit_{$type}"             => $yes,
        "edit_others_{$type}"      => $yes,
        "edit_private_{$type}"     => $yes,
        "edit_published_{$type}"   => $yes,
        "delete_{$type}"           => $yes,
        "delete_private_{$type}"   => $yes,
        "delete_published_{$type}" => $yes,
        "delete_others_{$type}"    => $yes
      ));
    }

    return $permissions;
  }

}


 
