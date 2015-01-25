<?php

/**
 * The admin screen for working with Resources
 *
 * @package Train-Up!
 * @subpackage Resources
 */

namespace TU;

class Resource_admin extends Post_admin {

  /**
   * $editable_permalinks
   *
   * Turn off the ability for users to edit the permalink of a Level
   *
   * @var mixed
   *
   * @access protected
   */
  protected $editable_permalinks = false;

  /**
   * __construct
   *
   * - Accept a Resource post type to base this admin screen on, set up the 
   *   class as normal
   * - Then, if active spoof the sub menu item to be 'Resources'
   *   (because this is a dynamic admin screen it doesn't have a related
   *   sub menu item)
   * - Add the scripts and styles
   * 
   * @param object $post_type
   *
   * @access public
   */
  public function __construct($post_type) {
    parent::__construct($post_type);

    if ($this->is_active()) {
      $this->highlight_sub_menu_item = 'tu_resources';
      
      add_action('admin_enqueue_scripts', array(__NAMESPACE__.'\\Resources_admin', '_add_assets'));
    }
  }

  /**
   * get_columns
   *
   * Returns a hash of extra columns to include when displaying resources in the
   * backend. Each key gets automatically mapped to a function.
   * 
   * @access protected
   *
   * @return array
   */
  protected function get_columns() {
    return parent::get_columns() + array(
      'scheduled' => __('Scheduled?', 'trainup'),
    );
  }

  /**
   * get_sortable_columns
   *
   * Returns an array of columns that WordPress should make sortable for the
   * Resource post type admin class.
   * 
   * @access protected
   *
   * @return array
   */
  protected function get_sortable_columns() {
    return array(
      'scheduled' => __('Scheduled', 'trainup')
    );
  }

  /**
   * get_meta_boxes
   * 
   * @access public
   *
   * @return array A hash of meta boxes to show when dealing with Resources.
   */
  public function get_meta_boxes() {
    return array(
      'relationships' => array(
        'title'    => __('Relationships', 'trainup'),
        'context'  => 'side',
        'priority' => 'default'
      ),
      'schedule' => array(
        'title'    => __('Schedule access', 'trainup'),
        'context'  => 'advanced',
        'priority' => 'default'
      )
    );
  }

  /**
   * meta_box_relationships
   *
   * - Fired when the 'relationships' meta box is to be rendered.
   *   (It displays links to posts that are associated with the active Resource)
   * - Echo out the view
   * 
   * @access protected
   */
  protected function meta_box_relationships() {
    echo new View(tu()->get_path('/view/backend/resources/relation_meta'), array(
      'level'     => tu()->resource->level,
      '_level'    => strtolower(tu()->config['levels']['single']),
      '_resource' => strtolower(tu()->config['resources']['single'])
    ));
  }

  /**
   * meta_box_schedule
   *
   * - Fired when the 'schedule' meta box is to be rendered.
   *   (It displays an input box allowing administrators to choose when the
   *   resources become available)
   * - Echo out the view
   * 
   * @access protected
   */
  protected function meta_box_schedule() {
    echo new View(tu()->get_path('/view/backend/resources/schedule_meta'), array(
      'groups'    => Groups::find_all(),
      'schedules' => tu()->resource->schedule_config,
      '_groups'   => strtolower(tu()->config['groups']['plural'])
    ));
  }

  /**
   * on_save
   *
   * - Fired then a Resource is saved
   * - Set its schedule (when it becomes available)
   * - Always set the ID of the Level that this Resource belongs to.
   * 
   * @param integer $post_id
   * @param object $post
   *
   * @access protected
   */
  protected function on_save($post_id, $post) {
    $schedules = isset($_POST['tu_schedule']) ? $_POST['tu_schedule'] : array();

    $resource = Resources::factory($post);
    $resource->set_level_id($this->post_type->level_id);
    $resource->set_schedules($schedules);
    $resource->save();
  }

  /**
   * add_crumbs
   *
   * Add a root 'Resources' crumb, then continue to add the automatic crumbs
   * 
   * @access protected
   */
  protected function add_crumbs() {
    tu()->add_crumb('admin.php?page=tu_resources', tu()->config['resources']['plural']);
    parent::add_crumbs();
  }

  /**
   * column_scheduled
   *
   * - Callback for a cell in the 'scheduled?' column.
   * - Ouput whether or not the resource is scheduled to become available
   * 
   * @access protected
   */
  protected function column_scheduled() {
    global $post;

    $str = '';
    $resource = Resources::factory($post);

    if ($resource->is_scheduled_for_all_groups()) {
      $schedules    = $resource->schedule_config;
      $ok           = (int)$schedules['all']['ok'];
      $class_name   = "tu-schedule-row-{$ok}";
      $datetime_str = $schedules['all']['datetime_str'];
      echo "<span class='{$class_name}'>{$datetime_str}</span>";
    } else if ($resource->is_scheduled()) {
      _e('Yes', 'trainup');
    } else {
      echo '&ndash;';
    }
  }

}


 
