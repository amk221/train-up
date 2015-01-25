<?php

/**
 * The admin screen for working with Groups
 *
 * @package Train-Up!
 * @subpackage Groups
 */

namespace TU;

class Group_admin extends Post_admin {

  /**
   * __construct
   *
   * - Load the Group post type and refresh it
   * - Construct the admin screen as normal using this Group post type.
   * - Then, if active add loads of events.
   * - If viewing the list of Groups, apply the filters
   * 
   * @access public
   */
  public function __construct() {
    $post_type = new Group_post_type;
    $post_type->refresh();

    parent::__construct($post_type);

    if ($this->is_active()) {
      $this->process_bulk_actions();

      add_action('admin_enqueue_scripts', array($this, '_add_assets'));
      add_action('post_submitbox_misc_actions', array($this, '_publish_meta'));

      if ($this->is_browsing()) {
        add_action('pre_get_posts', array($this, '_allow_searching_for_groups_by_user'));
        add_action('pre_get_posts', array(__NAMESPACE__.'\\Groups', '_filter'));
      }
    }
  }

  /**
   * _add_assets
   *
   * - Fired on `admin_enqueue_scripts` when Group administration is active
   * - Enqueue styles and scripts required for managing Group posts.
   * 
   * @access private
   */
  public function _add_assets() {
    wp_enqueue_script('tu_groups');
    wp_enqueue_style('tu_groups');
  }

  /**
   * get_columns
   *
   * @access protected
   *
   * @return array Extra columns that should show when managing Groups.
   */
  protected function get_columns() {
    return array(
      'description' => __('Description', 'trainup'),
      'colour'      => __('Colour', 'trainup'),
      'trainees'    => tu()->config['trainees']['plural']
    );
  }

  /**
   * get_meta_boxes
   * 
   * @access protected
   *
   * @return array A hash of meta boxes that should show when managing Groups.
   */
  protected function get_meta_boxes() {
    return array(
      'description' => array(
        'title'    => __('Description', 'trainup'),
        'context'  => 'advanced',
        'priority' => 'default'
      ),
      'trainees' => array(
        'title'    => tu()->config['trainees']['plural'],
        'context'  => 'advanced',
        'priority' => 'default'
      ),
      'relationships' => array(
        'title'    => __('Relationships', 'trainup'),
        'context'  => 'side',
        'priority' => 'default'
      )
    );
  }

  /**
   * column_description
   *
   * - Callback for the 'description' column
   * - Output the description of the current Group
   * 
   * @access protected
   */
  protected function column_description() {
    global $post;
    echo Groups::factory($post)->get_description(100) ?: '&ndash;';
  }

  /**
   * column_colour
   * 
   * @access protected
   */
  protected function column_colour() {
    global $post;
    $colour = Groups::factory($post)->get_colour();
    echo "
      <span class='tu-group-colour-box' style='background: {$colour}'>
        {$colour}
      </span>
    ";
  }

  /**
   * column_trainees
   *
   * - Callback for the 'trainees' column
   * - Output a link to a search of Trainees within the current Group.
   * 
   * @access protected
   */
  protected function column_trainees() {
    global $post;

    $_groups   = simplify(tu()->config['groups']['single']);
    $_trainees = strtolower(tu()->config['trainees']['plural']);
    $href      = "users.php?role=tu_trainee&s={$_groups}: {$post->ID}";
    $text      = sprintf(__('View %1$s', 'trainup'), $_trainees);

    echo "<a href='{$href}'>{$text}&nbsp;&raquo;</a>";
  }

  /**
   * meta_box_description
   *
   * - Callback for the 'description' meta box
   * - Output a textarea to allow saving of the Group's description.
   * 
   * @access protected
   */
  protected function meta_box_description() {
    echo new View(tu()->get_path('/view/backend/groups/description_meta'), array(
      'description' => tu()->group->post_content
    ));
  }

  /**
   * meta_box_relationships
   *
   * - Callback for the 'relationships' meta box
   * - Output a list of links to view trainees and group managers associated
   *   with the current group
   * 
   * @access protected
   */
  protected function meta_box_relationships() {
    echo new View(tu()->get_path('/view/backend/groups/relation_meta'), array(
      'group'           => tu()->group,
      '_group'          => strtolower(tu()->config['groups']['single']),
      '_trainees'       => strtolower(tu()->config['trainees']['plural']),
      '_group_managers' => strtolower(tu()->config['group_managers']['plural'])
    ));
  }

  /**
   * meta_box_trainees
   * 
   * - Callback for the 'trainees' meta box
   * - Output a textarea and autocompleter search box to allow Trainees to be
   *   searched for an then associated with the current Group.
   *
   * @access protected
   */
  protected function meta_box_trainees() {
    global $post;
    
    echo new View(tu()->get_path('/view/backend/groups/trainees_meta'), array(
      'trainees_in_group' => Groups::factory($post)->get_trainees()
    ));
  }

  /**
   * on_save
   *
   * - Fired when a Group is being saved
   * - Read in any Trainee IDs, and associate them with the current Group
   * - Also set the Group's post content to be that of the description, taken
   *   from the 'description' meta box.
   * 
   * @param integer $post_id 
   * @param object $post 
   *
   * @access protected
   */
  protected function on_save($post_id, $post) {
    $description = isset($_POST['group_description']) ? $_POST['group_description'] : '';
    $trainee_ids = isset($_POST['trainee_ids']) ? $_POST['trainee_ids'] : array();
    $colour      = isset($_POST['tu_colour']) ? $_POST['tu_colour'] : '#f1f1f1';

    $group = Groups::factory($post);
    $group->set_trainees($trainee_ids);
    $group->set_colour($colour);
    $group->save(array(
      'post_content' => $description
    ));
  }

  /**
   * on_delete
   *
   * - Fired when a Group is about to be deleted
   * - Load the Group and fire a soft delete on it (It is about to be deleted
   *   by WordPress anyway).
   * - This is to remove any associations that users may have with this Group,
   *   basically just cleaning up after ourselves.
   * 
   * @param integer $post_id 
   *
   * @access protected
   */
  protected function on_delete($post_id) {
    Groups::factory($post_id)->delete(false);
  }

  /**
   * _set_order
   *
   * - Fired on `pre_get_posts` when Groups are being viewed
   * - Override the default _set_order function (by menu_order) and
   *   set it to go off the title instead, unless otherwise specified.
   *
   * @param object $query
   * 
   * @access private
   *
   * @return object The altered query
   */
  public function _set_order($query) {
    if (!isset($_REQUEST['orderby'])) {
      $query->set('orderby', 'title');
      $query->set('order', 'ASC');
    }

    return $query;
  }

  /**
   * _publish_meta
   *
   * - Fired on `post_submitbox_misc_actions` when a group is active
   *   (inside the Publish meta box)
   * - Render the option to choose a colour for this Group.
   * - We are using this place temporarily, because it doesn't yet warrant
   *   having its own meta box.
   * 
   * @access private
   */
  public function _publish_meta() {
    echo new View(tu()->get_path('/view/backend/groups/publish_meta'), array(
      'colour' => tu()->group->colour
    ));
  }

  /**
   * _allow_searching_for_groups_by_user
   *
   * - Fired on `pre_get_posts`
   * - Inspect the search query parameters and if it looks like a special string
   *   that allows us to search for Groups associated with a User, then
   *   filter the results.
   * - Unset the actual search query parameter, because its redundant now.
   *
   * @param object $query
   * 
   * @access private
   *
   * @return object The altered query
   */
  public function _allow_searching_for_groups_by_user($query) {
    if (!isset($query->query_vars['s'])) return;

    preg_match(Groups::for_regex(), $query->query_vars['s'], $matches);

    if (count($matches) !== 3) return;

    $user = Users::factory($matches[2]);

    if (!$user->loaded()) return;

    $query->query_vars['post__in'] = $user->get_group_ids();
    
    unset($query->query_vars['s']);
    unset($query->query['s']);

    return $query;
  }

  /**
   * add_crumbs
   *
   * - Add the crumbs as usual
   * - Then, if a Group search has been run for a User, add another crumb
   *   e.g. Train-Up! > Groups > Joe Bloggs
   * 
   * @access protected
   */
  protected function add_crumbs() {
    parent::add_crumbs();

    if (!isset($_GET['s'])) return;

    preg_match(Groups::for_regex(), $_GET['s'], $matches);

    if (count($matches) === 3) {
      $user_id = $matches[2];
      $user    = Users::factory($user_id);

      tu()->add_crumb('', $user->display_name);
    }
  }

  /**
   * process_bulk_actions
   * 
   * Inspect the request parameters and fire a function for any bulk actions.
   *
   * @access private
   */
  private function process_bulk_actions() {
    if (!isset($_REQUEST['action'])) return;

    if ($_REQUEST['action'] === 'tu_send_email') {
      $this->set_up_bulk_emailer($_REQUEST['post']);
    }
  }

  /**
   * set_up_bulk_emailer
   *
   * - Fired when an administrator has requested to bulk email some Groups
   * - Find the IDs of all the *Trainees* in the specified Groups, and then
   *   navigate to the emailer screen passing along the IDs, this basically
   *   pre-populates the recipients list.
   * - Set the emailer's context to be this admin screen.
   * 
   * @param array $group_ids
   *
   * @access private
   */
  private function set_up_bulk_emailer($group_ids) {
    global $wpdb;

    if (count($group_ids) < 1) return;

    $trainee_ids = Groups::get_trainee_ids($group_ids);

    $args = array(
      'page'        => 'tu_emailer',
      'user_ids'    => join(',', $trainee_ids),
      'tu_context'  => array(
        'title'     => tu()->config['groups']['plural'],
        'link'      => 'edit.php?post_type=tu_group',
        'menu_slug' => 'edit.php?post_type=tu_group'
      )
    );

    $url = add_query_arg($args, 'admin.php');

    go_to($url);
  }

}


 
