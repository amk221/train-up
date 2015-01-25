<?php

/**
 * The admin screen for working with Group managers
 *
 * @package Train-Up!
 * @subpackage Group Managers
 */

namespace TU;

class Group_manager_user_admin extends User_admin {

  /**
   * $role
   *
   * The role slug that this admin screen is for managing.
   *
   * @var string
   *
   * @access protected
   */
  protected $role = 'tu_group_manager';

  /**
   * __construct
   *
   * - When creating the Group manager admin section, construct it as normal,
   *   then, if active force the title to be the plural of 'Group manager'.
   * - Listen out for any bulk action requests that may come in.
   * 
   * @access public
   */
  public function __construct() {
    parent::__construct();

    if ($this->is_active()) {
      $this->title = tu()->config['group_managers']['plural'];
      $this->process_bulk_actions();
    }
  }

  /**
   * _add_sub_menu_item
   * 
   * Force a sub menu item into Train-Up's main menu
   *
   * @access public
   */
  public function _add_sub_menu_item() {
    global $submenu;
    
    $submenu['tu_plugin'][] = array(
      tu()->config['group_managers']['plural'],
      'tu_group_managers',
      $this->front_page
    );
  }

  /**
   * _manage_users_columns
   *
   * - Fired on `manage_users_columns`
   * - When Group managers are being listed, remove the role column (because it
   *   should be obvious what role they have), and remove the number of posts 
   *   column, because its irrelevant.
   * - Add a 'groups' column which briefly lists which Groups the current
   *   Group manager can manage.
   * 
   * @param array $columns
   *
   * @access public
   *
   * @return array The altered columns
   */
  public function _manage_users_columns($columns) {
    $columns = parent::_manage_users_columns($columns);

    $columns['groups'] = tu()->config['groups']['plural'];

    return $columns;
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
    $group_manager  = Group_managers::factory($user);
    $_groups        = tu()->config['groups']['plural'];
    $_group_manager = tu()->config['group_managers']['single'];

    $description = sprintf(
      __('Select one or more %1$s which this %2$s can manage.', 'trainup'),
      strtolower($_groups),
      strtolower($_group_manager)
    );
    
    echo new View(tu()->get_path('/view/backend/users/groups_choice'),  array(
      'groups'      => Groups::find_all(),
      'user'        => $group_manager,
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
    tu()->add_crumb($this->front_page, tu()->config['group_managers']['plural']);

    parent::add_crumbs();
  }

}