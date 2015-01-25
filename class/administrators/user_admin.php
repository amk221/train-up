<?php

/**
 * The admin screen for working with Administrators
 *
 * @package Train-Up!
 * @subpackage Administrators
 */

namespace TU;

class Administrator_user_admin extends User_admin {

  /**
   * $role
   *
   * The role slug that this admin screen is for managing.
   *
   * @var string
   *
   * @access protected
   */
  protected $role = 'administrator';

  /**
   * __construct
   *
   * - When creating the Administrator admin section, construct it as normal,
   *   then, if active force the title to be the plural of 'Group manager'.
   * - Listen out for any bulk action requests that may come in.
   * 
   * @access public
   */
  public function __construct() {
    parent::__construct();

    if ($this->is_active()) {
      add_action('show_user_profile', array($this, '_edit_own_profile'));
      add_action('personal_options_update', array($this, '_edit_own_profile_update'));

      $this->title = __('Administrators', 'trainup');
      $this->process_bulk_actions();
    }
  }
  
  /**
   * is_editing
   *
   * Returns whether an administrator appears to be editing their own profile
   * 
   * @access public
   *
   * @return boolean
   */
  public function is_editing() {
    global $pagenow;

    return (
      $pagenow === 'profile.php' &&
      current_user_can('administrator')
    );
  }

  /**
   * column_groups
   *
   * - Callback for the 'groups' column
   * - Load the current Group manager and print out a brief list of their
   *   Groups.
   * - Output links to the Group searches to show which Groups the current
   *   Group manager can manage.
   * 
   * @param integer $user_id
   *
   * @access protected
   *
   * @return string
   */
  protected function column_groups($user_id) {
    $group_manager  = Group_managers::factory($user_id);
    $_group_manager = simplify(tu()->config['group_managers']['single']);
    $group_ids      = $group_manager->get_group_ids();

    if (!$group_ids) {
      return __('None', 'trainup');
    } else if (count($group_ids) === 1) {
      $group = Groups::factory($group_ids[0]);
      $text  =  $group->post_title;
      $href  = "post.php?post={$group->ID}&action=edit";

      return "<a href='{$href}'>{$text}&nbsp;&raquo;</a>";
    } else {
      $list = $group_manager->get_group_list(20);
      $href = "edit.php?post_type=tu_group&amp;s={$_group_manager}: {$group_manager->ID}";
      $text = __('View', 'trainup');
      $list .= " <a href='{$href}'>{$text}&nbsp;&raquo;</a>";
      
      return $list;
    }
  }  

  /**
   * _edit_own_profile
   *
   * - Fired on `show_user_profile`
   * - Echo out a field that lets administrators choose which group they are in
   *   but only if an administrator. (We don't want normal users assigning
   *   themselves to groups)
   * 
   * @param object $user 
   *
   * @access private
   */
  public function _edit_own_profile($user) {
    $this->add_groups_field($user);
  }

  /**
   * _edit_own_profile_update
   *
   * - Fired on `personal_options_update`
   * - When an adminitrators's own profile is updated, handle the saving of any 
   *   Groups that may need to be associated with them.
   * 
   * @param object $user
   *
   * @access private
   */
  public function _edit_own_profile_update($user) {
    $this->handle_saving_of_groups($user);
  } 

  /**
   * add_groups_field
   *
   * - Fired when a Group manager's profile is being edited.
   * - Output a select box allowing Groups to be associated with a Group manager
   * 
   * @param object $user
   *
   * @access protected
   */
  protected function add_groups_field($user) {
    $administrator  = Administrators::factory($user);
    $_groups        = tu()->config['groups']['plural'];

    $description = sprintf(
      __('Select one or more %1$s which this administrator belongs to.', 'trainup'),
      strtolower($_groups)
    );
    
    echo new View(tu()->get_path('/view/backend/users/groups_choice'), array(
      'groups'      => Groups::find_all(),
      'user'        => $administrator,
      'description' => $description,
      '_groups'     => $_groups
    ));
  }

  /**
   * add_crumbs
   *
   * Add a crumb to show that the administrator is in the Group Managers section
   * 
   * @access protected
   */
  protected function add_crumbs() {
    tu()->add_crumb($this->front_page, __('Administraors', 'trainup'));

    parent::add_crumbs();
  }

}