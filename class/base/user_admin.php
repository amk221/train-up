<?php

namespace TU;

abstract class User_admin extends Admin {

  /**
   * $role
   *
   * The slug of the role that this user admin class is to manage.
   *
   * @var string
   *
   * @access protected
   */
  protected $role = '';

  /**
   * $unhighlight_menu_item
   *
   * The main menu item that this class should force to be un-highlighted
   * when it is active. 
   *
   * @var mixed
   *
   * @access protected
   */
  protected $unhighlight_menu_item = 'users.php';

  /**
   * __construct
   * 
   * - First run some checks
   * - Then, add the associated sub menu item for this user admin class
   * - If this user admin class is active, add loads of events.
   *
   * @access public
   */
  public function __construct() {
    $this->check();
    $this->front_page = "users.php?role={$this->role}";
    $this->highlight_sub_menu_item = $this->front_page;

    add_action('admin_menu', array($this, '_add_sub_menu_item'));
    add_action('delete_user', array($this, '_delete_user'));

    if ($this->is_active()) {
      parent::__construct();
      $this->add_crumbs();

      add_action('admin_enqueue_scripts', array($this, '_add_assets'));
      add_filter('views_users', '__return_false');
      add_filter('admin_body_class', array($this, '_stylable_user_types'));
      add_action('restrict_manage_users', array($this, '_force_role'));
      add_action('edit_user_profile', array($this, '_edit_user_profile')); 
      add_action('edit_user_profile_update', array($this, '_edit_user_profile_update'));
      add_action('manage_users_columns', array($this, '_manage_users_columns'));
      add_action('manage_users_custom_column', array($this, '_manage_users_custom_column'), 10, 3);

      if ($this->is_browsing()) {
        add_action('pre_user_query', array($this, '_allow_searching_for_users_in_group'));
      }
    }
  }

  /**
   * check
   *
   * - Check the current user has access to view the current user admin
   *   screen.
   * - Limit access for Group managers, so they can see Trainees only
   *   i.e. we don't want Group managers to be able to see administrators.
   * 
   * @access protected
   */
  protected function check() {
    global $pagenow;

    if (tu()->user->is_group_manager()) {
      if ( $pagenow === 'users.php' && !isset($_REQUEST['role']) || (
           isset($_REQUEST['role']) && $_REQUEST['role'] !== 'tu_trainee' )
      ) {
        wp_die(__('Access denied', 'trainup'));
      }
    }
  }

  /**
   * _add_assets
   *
   * - Fired on `admin_enqueue_scripts` when User administration is active
   * - Enqueue styles and scripts required for managing users.
   * 
   * @access private
   */
  public function _add_assets() {
    wp_enqueue_script('tu_users');
  }

  /**
   * _stylable_user_types
   *
   * We already have the current user's role as a body class, but we also want
   * the current type of users being viewed as a body class. This allows us
   * to style specific role, and also allows us to use JavaScript to add
   * bulk actions to specific views.
   * 
   * @param string $classes
   *
   * @access private
   *
   * @return string The altered classes
   */
  public function _stylable_user_types($classes) {
    $classes .= " on-{$this->role} ";

    return $classes;
  }

  /**
   * _force_role
   *
   * Fired upon `restrict_manage_users` A randomly chosen action
   * that appears to be the only one that lets us echo HTML out inside the
   * users form. This allows us to spoof further form submissions to be
   * a particular role... thereby remaining 'inside' the plugin.
   * 
   * @access private
   */
  public function _force_role() {
    echo "<input type='hidden' name='role' value='{$this->role}'>";
  }

  /**
   * _delete_user
   * 
   * - Fired when a user is to be deleted `delete_user`
   * - Load the user, and call delete on it (thereby removing their archive etc)
   * - Pass in 'false' to tell the delete function not to actually call 
   *   `wp_delete_user`, because WordPress is about to do this itself and we 
   *   don't want to create an infinite loop.
   *
   * @param integer $user_id 
   *
   * @access private
   */
  public function _delete_user($user_id) {
    Users::factory($user_id)->delete(false);
  }

  /**
   * _add_sub_menu_item
   * 
   * Force a sub menu item into Train-Up's main menu
   * Override me to add a custom sub menu item for this admin screen.
   *
   * @access private
   */
  public function _add_sub_menu_item() {
    
  }

  /**
   * _manage_users_columns
   *
   * - Fired on `manage_users_columns`
   * - When Users are being listed, remove the role column (because it
   *   should be obvious what role they have), and remove the number of posts 
   *   column, because its irrelevant.
   * 
   * @param array $columns
   *
   * @access private
   *
   * @return array The altered columns
   */
  public function _manage_users_columns($columns) {
    unset($columns['role']);
    unset($columns['posts']);

    return $columns;
  }

  /**
   * _manage_users_custom_column
   * 
   * - Fired on `manage_users_custom_column`
   * - Call a function automatically for the column that is being rendered
   * 
   * @param mixed $value
   * @param string $column_name 
   * @param integer $user_id
   *
   * @access private
   *
   * @return mixed
   */
  public function _manage_users_custom_column($value, $column_name, $user_id) {
    $func = "column_$column_name";

    if (method_exists($this, $func)) {
      return $this->$func($user_id);
    }
  }

  /**
   * _edit_user_profile
   *
   * - Fired on `edit_user_profile`
   * - Echo out a field that lets administrators choose the User's Groups
   * 
   * @param object $user 
   *
   * @access private
   */
  public function _edit_user_profile($user) {
    $this->add_groups_field($user);
  }

  /**
   * add_groups_field
   *
   * - Fired when a User's profile is being edited.
   * - Override me to add the fields that let administrators choose the Groups
   *   that a user can be in.
   * 
   * @param object $user
   *
   * @access protected
   */
  protected function add_groups_field($user) {

  }

  /**
   * _edit_user_profile_update
   *
   * - Fired on `edit_user_profile_update`
   * - When a User's profile is updated, handle the saving of any Groups that
   *   may need to be associated with them.
   * 
   * @param object $user
   *
   * @access private
   */
  public function _edit_user_profile_update($user) {
    $this->handle_saving_of_groups($user);
  } 

  /**
   * handle_saving_of_groups
   *
   * - Fired when a user's profile is being saved
   * - Read in any Group IDs and assign them to the user.
   * 
   * @param object $user
   *
   * @access protected
   */
  protected function handle_saving_of_groups($user) {
    $group_ids = isset($_POST['tu_groups']) ? $_POST['tu_groups'] : array();

    $user = Users::factory($user);
    $user->set_group_ids($group_ids);
  }

  /**
   * is_active
   *
   * Returns whether or not this user admin class is considered to be active
   * (i.e. it is Adding, Editing, or showing the list of Users that have the
   * role that this class is for).
   * 
   * @access public
   *
   * @return boolean
   */
  public function is_active() {
    return (
      $this->is_adding() || $this->is_editing() || $this->is_browsing()
    );
  }

  /**
   * is_adding
   *
   * Returns whether we appear to be adding a new user of the role that this
   * user admin class is for. This is a bit of a hack, because we don't want
   * to interfere with the existing WordPress Users section.
   * 
   * @access public
   *
   * @return boolean
   */
  public function is_adding() {
    global $pagenow;

    return (
      $pagenow === 'user-new.php' &&
      isset($_SERVER['HTTP_REFERER']) &&
      basename($_SERVER['HTTP_REFERER']) === $this->front_page
    );
  }

  /**
   * is_editing
   *
   * Returns whether we appear to be editing an existing user of the role that
   * this user admin class is for.
   * 
   * @access public
   *
   * @return boolean
   */
  public function is_editing() {
    global $pagenow;

    return (
      $pagenow === 'user-edit.php' &&
      user_can($_REQUEST['user_id'], $this->role)
    );
  }

  /**
   * is_browsing
   *
   * Returns whether we appear to be browsing users of the role that this
   * user admin class is for.
   * 
   * @access public
   *
   * @return boolean
   */
  public function is_browsing() {
    global $pagenow;

    return (
      $pagenow === 'users.php' && 
      isset($_REQUEST['role']) &&
      $_REQUEST['role'] === $this->role
    );
  }

  /**
   * get_user_id
   *
   * Get the user id that this user_admin class is for. It should be available
   * if on their edit page, but if on their profile page it won't (but doesn't
   * need to be).
   */
  private function get_user_id() {
    global $pagenow;

    if ($pagenow === 'profile.php') {
      return get_current_user_id();
    } elseif (isset($_REQUEST['user_id'])) {
      return $_REQUEST['user_id'];
    }
  }

  /**
   * add_crumbs
   *
   * Automatically add some crumbs based on whether we are Adding or Editing
   * a user.
   * 
   * @access protected
   */
  protected function add_crumbs() {    
    if ($this->is_adding()) {
      tu()->add_crumb('', __('Add new', 'trainup'));
    } else if ($this->is_editing()) {
      tu()->add_crumb('', Users::factory($this->get_user_id())->display_name);
    }
  }

  /**
   * process_bulk_actions
   *
   * - Fired whenever this user admin class is active (see child classes)
   * - Call a function if a suitable bulk action exists.
   * 
   * @access protected
   */
  protected function process_bulk_actions() {
    if (!isset($_REQUEST['action'])) return;

    if ($_REQUEST['action'] === 'tu_send_email' && isset($_REQUEST['users'])) {
      $this->set_up_bulk_emailer($_REQUEST['users']);
    }

    if (isset($_REQUEST['users']) && !empty($_REQUEST['tu_group'])) {
      if ($_REQUEST['action'] === 'tu_add_to_group') {
        $this->bulk_add_to_group($_REQUEST['users'], $_REQUEST['tu_group']);
      } else if ($_REQUEST['action'] === 'tu_remove_from_group') {
        $this->bulk_remove_from_group($_REQUEST['users'], $_REQUEST['tu_group']);
      }
    }
  }

  /**
   * bulk_add_to_group
   *
   * Fired when the administrator has requested a batch action to assign
   * selected users into a group.
   * 
   * @param integer $user_ids The user IDs to assign the group
   * @param integer $group_id The Group ID to put the users in
   *
   * @access protected
   */
  protected function bulk_add_to_group($user_ids, $group_id) {
    $_group = strtolower(tu()->config['groups']['single']);

    $group = Groups::factory($group_id);

    if ($group->loaded()) {
      $group->add_users($user_ids);
      $type = 'success';
      $msg  = sprintf(
        __('Added to %1$s "%2$s"', 'trainup'),
        $_group, $group->post_title
      );
    } else {
      $type = 'error';
      $msg  = sprintf(
        __('Failed to add to %1$s #%2$s', 'trainup'),
        $_group, $group_id
      );
    }

    tu()->message->set_flash($type, $msg);
  }

  /**
   * bulk_remove_from_group
   *
   * Fired when the administrator has requested a batch action to remove
   * selected users from a group.
   * 
   * @param integer $user_ids The user IDs to remove from the group
   * @param integer $group_id The Group ID to remove the users from
   *
   * @access protected
   */
  protected function bulk_remove_from_group($user_ids, $group_id) {
    $_group = strtolower(tu()->config['groups']['single']);

    $group = Groups::factory($group_id);

    if ($group->loaded()) {
      $group->remove_users($user_ids);
      $type = 'success';
      $msg  = sprintf(
        __('Removed from %1$s "%2$s"', 'trainup'),
        $_group, $group->post_title
      );
    } else {
      $type = 'error';
      $msg  = sprintf(
        __('Failed to remove from %1$s #%2$s', 'trainup'),
        $_group, $group_id
      );
    }

    tu()->message->set_flash($type, $msg);
  }

  /**
   * set_up_bulk_emailer
   *
   * - Fired when the user has requested a batch action to email a load of users.
   * - Send the user to the Emailer, along with details about the page that
   *   refereed them, so the we can spoof the breadcrumb trail
   * 
   * @param integer $user_ids
   *
   * @access protected
   */
  protected function set_up_bulk_emailer($user_ids) {
    global $wpdb;

    if (count($user_ids) < 1) return;

    $args = array(
      'page'        => 'tu_emailer',
      'user_ids'    => join(',', $user_ids),
      'tu_context'  => array(
        'title'     => $this->title,
        'link'      => $this->front_page,
        'menu_slug' => $this->front_page,
      )
    );

    $url = add_query_arg($args, 'admin.php');

    go_to($url);
  }

  /**
   * _allow_searching_for_users_in_group
   *
   * - Fired when a user search is ran
   * - Filter the query if the search term matches a special rule.
   * 
   * @param object $query
   *
   * @access private
   *
   * @return object The altered query
   */
  public function _allow_searching_for_users_in_group($query) {
    global $wpdb;

    preg_match(Groups::in_regex(), $query->query_vars['search'], $matches);

    $where                   = '';
    $role                    = $wpdb->esc_like($this->role);
    $find_users_in_no_groups = count($matches) === 2;
    $find_users_in_groups    = count($matches) === 3;

    if ($find_users_in_no_groups) {
      $this->title = sprintf(
        __('%1$s not in a %2$s', 'trainup'),
        tu()->config['trainees']['plural'],
        strtolower(tu()->config['groups']['single'])
      );

      $where = "
        AND {$wpdb->users}.ID NOT IN (
          SELECT user_id
          FROM   {$wpdb->usermeta} m2
          WHERE  m2.meta_key = 'tu_group'
          GROUP  BY user_id
        )
      ";
    }

    else if ($find_users_in_groups) {
      $where = "
        AND {$wpdb->users}.ID IN (
          SELECT user_id
          FROM   {$wpdb->usermeta} m2
          WHERE  m2.meta_key = 'tu_group'
          AND    m2.meta_value IN ({$matches[2]})
          GROUP  BY user_id
        )
      ";
    }

    if ($where) {
      $query->query_where = "WHERE 1=1 AND (
        {$wpdb->usermeta}.meta_key = '{$wpdb->prefix}capabilities' AND
        {$wpdb->usermeta}.meta_value LIKE '%{$role}%'
      ) {$where}";
    }

    return $query;
  }

}


 
