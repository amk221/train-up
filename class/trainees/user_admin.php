<?php

/**
 * The admin screen for working with Trainees
 *
 * @package Train-Up!
 * @subpackage Trainees
 */

namespace TU;

class Trainee_user_admin extends User_admin {

  /**
   * $role
   *
   * The role slug that this admin screen is for managing.
   *
   * @var string
   *
   * @access protected
   */
  protected $role = 'tu_trainee';

  /**
   * __construct
   *
   * - When creating the Trainee admin section, construct it as normal,
   *   then, if active force the title to be the plural of 'Trainee'.
   * - Listen out for any bulk action requests that may come in.
   * - Filter trainees for Group managers so they can only see ones they're
   *   allowed to see.
   * 
   * @access public
   */
  public function __construct() {
    parent::__construct();

    if ($this->is_active()) {
      $this->title = tu()->config['trainees']['plural'];
      $this->process_bulk_actions();

      if ($this->is_browsing()) {
        add_action('pre_user_query', array($this, '_allow_searching_for_trainees_taking_test'));
        add_action('pre_user_query', array(__NAMESPACE__.'\\Trainees', '_filter'));
      }
    }
  }

  /**
   * _add_sub_menu_item
   * 
   * Force a sub menu item into the plugin's main menu
   *
   * @access private
   */
  public function _add_sub_menu_item() {
    global $submenu;

    $submenu['tu_plugin'][] = array(
      tu()->config['trainees']['plural'],
      'tu_trainees',
      $this->front_page
    );
  }

  /**
   * _manage_users_columns
   *
   * - Fired on `manage_users_columns`
   * - When Trainees are being listed, remove the role column (because it
   *   should be obvious what role they have), and remove the number of posts 
   *   column, because its irrelevant.
   * - Add a 'groups' column which briefly lists which Groups the current
   *   Trainee is in
   * - Add an 'archive' column, which links to the Trainee's past results.
   * 
   * @param array $columns
   *
   * @access private
   *
   * @return array The altered columns
   */
  public function _manage_users_columns($columns) {
    $columns = parent::_manage_users_columns($columns);

    $columns['groups']  = tu()->config['groups']['plural'];
    $columns['archive'] = __('Archive', 'trainup');

    return $columns;
  }

  /**
   * column_groups
   *
   * @param integer $user_id
   *
   * @access protected
   *
   * @return string Links to the Group searches to show which Groups the current
   * Trainee is in.
   */
  protected function column_groups($user_id) {
    $trainee   = Trainees::factory($user_id);
    $group_ids = $trainee->get_group_ids();
    $_trainee  = simplify(tu()->config['trainees']['single']);
    $_group    = simplify(tu()->config['groups']['single']);

    if (!$group_ids) {
      $href = "users.php?role=tu_trainee&amp;s={$_group}:";
      $text = __('None', 'trainup');

      return "<a href='{$href}'>{$text}</a>";
    } else if (count($group_ids) === 1) {
      $text = Groups::factory($group_ids[0])->post_title;
      $href = "post.php?post={$group_ids[0]}&amp;action=edit";

      return "<a href='{$href}'>{$text}&nbsp;&raquo;</a>";
    } else {
      $list = $trainee->get_group_list(20);
      $type = simplify(tu()->config['trainees']['single']);
      $href = "edit.php?post_type=tu_group&amp;s={$_trainee}: {$trainee->ID}";
      $text = __('View', 'trainup');
      $list .= " <a href='$href'>{$text}&nbsp;&raquo;</a>";

      return $list;
    }
  }

  /**
   * column_archive
   * 
   * @param integer $user_id
   *
   * @access protected
   *
   * @return string A hyperlink to the active Trainee's archived test results.
   */
  protected function column_archive($user_id) {
    $href = "admin.php?page=tu_results&amp;tu_user_id={$user_id}";
    $text = __('View archive', 'trainup');

    return "<a href='{$href}'>{$text}&nbsp;&raquo;</a>";
  }

  /**
   * add_groups_field
   *
   * - Fired when a Trainee's profile is being edited.
   * - Output a select box allowing Groups to be associated with a Trainee
   * 
   * @param object $user
   *
   * @access protected
   */
  protected function add_groups_field($user) {
    $trainee  = Trainees::factory($user);
    $_groups  = tu()->config['groups']['plural'];
    $_trainee = tu()->config['trainees']['single'];

    $description = sprintf(
      __('Select one or more %1$s that this %2$s belongs to.', 'trainup'),
      strtolower($_groups),
      strtolower($_trainee)
    );

    echo new View(tu()->get_path('/view/backend/users/groups_choice'), array(
      'groups'      => Groups::find_all(),
      'user'        => $trainee,
      'description' => $description,
      '_groups'     => $_groups
    ));
  }

  /**
   * add_crumbs
   *
   * Add a crumb to show that the administrator is in the Trainees section
   * 
   * @access protected
   */
  protected function add_crumbs() {
    tu()->add_crumb($this->front_page, tu()->config['trainees']['plural']);

    parent::add_crumbs();
  }
  
  /**
   * _allow_searching_for_trainees_taking_test
   *
   * - Fired on `pre_get_posts`
   * - Inspect the search query parameters and if it looks like a special string
   *   that allows us to search for Trainees who are currently taking the test
   *   then alter the query to show only those trainees.
   *
   * @param object $query
   * 
   * @access private
   *
   * @return object The altered query
   */
  public function _allow_searching_for_trainees_taking_test($query) {
    global $wpdb;

    preg_match(Trainees::taking_test_regex(), $query->query_vars['search'], $matches);

    if (count($matches) !== 3) return $query;

    $test = Tests::factory($matches[2]);

    $this->title = sprintf(
      __('%1$s currently taking %2$s', 'trainup'),
      tu()->config['trainees']['plural'],
      $test->post_title
    );

    $trainee_ids = join(',', $test->active_trainee_ids) ?: 0;

    $query->query_where = "AND {$wpdb->users}.ID IN ({$trainee_ids})";

    return $query;
  }

}

 
