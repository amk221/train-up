<?php

/**
 * The admin screen for working with Levels
 *
 * @package Train-Up!
 * @subpackage Levels
 */

namespace TU;

class Level_admin extends Post_admin {

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
   * - Creates a new admin section for managing Levels
   * - Load the level post type, refresh it to get the latest info.
   * - Carry on constructing the post type admin as normal,
   *   then if active, add Level specific actions.
   * - If viewing the level list, apply the filters
   *
   * @access public
   */
  public function __construct() {
    $post_type = new Level_post_type;
    $post_type->refresh();

    parent::__construct($post_type);
    
    if ($this->is_active()) {
      add_action('admin_enqueue_scripts', array($this, '_add_assets'));
      add_action('default_content', array($this, '_default_content'));

      if ($this->is_browsing()) {
        add_action('pre_get_posts', array(__NAMESPACE__.'\\Levels', '_filter'));
      }
    }
  }

  /**
   * _add_assets
   *
   * - Fired on `admin_enqueue_scripts` when Level administration is active
   * - Enqueue styles and scripts specific to Levels.
   * 
   * @access private
   */
  public function _add_assets() {
    wp_enqueue_style('tu_levels');
    wp_enqueue_script('tu_levels');
  }

  /**
   * _default_content
   *
   * Fired on `default_content` when Level admin is active, returns the default
   * content for a new Level.
   * 
   * @param string $content
   *
   * @access private
   *
   * @return string
   */
  public function _default_content($content) {
    return tu()->config['levels']['default_content'];
  }

  /**
   * get_meta_boxes
   * 
   * @access protected
   *
   * @return array Hash of meta boxes to show when dealing with Levels.
   */
  protected function get_meta_boxes() {
    $_group   = tu()->config['groups']['single'];
    $_trainee = tu()->config['trainees']['single'];
    $_level   = tu()->config['levels']['single'];

    $meta_boxes = array(
      'shortcodes' => array(
        'title'    => __('Shortcodes', 'trainup'),
        'context'  => 'side',
        'priority' => 'high',
        'closed'   => true
      ),
      'group_access' => array(
        'title'    => sprintf(__('%1$s access', 'trainup'), $_group),
        'context'  => 'side',
        'priority' => 'low'
      ),
      'trainee_eligibility' => array(
        'title'    => sprintf(__('%1$s eligibility', 'trainup'), $_trainee),
        'context'  => 'side',
        'priority' => 'low'
      )
    );

    if ($this->is_editing()) {
      $meta_boxes['relationships'] = array(
        'title'    => __('Relationships', 'trainup'),
        'context'  => 'side',
        'priority' => 'default'
      );
    }

    return $meta_boxes;
  }

  /**
   * get_columns
   *
   * Returns a hash of extra columns to include when displaying Levels in the
   * backend. Each key gets automatically mapped to a function.
   * 
   * @access protected
   *
   * @return array
   */
  protected function get_columns() {
    return parent::get_columns() + array(
      'resources' => tu()->config['resources']['plural'],
      'test'      => tu()->config['tests']['single']
    );
  }

  /**
   * meta_box_relationships
   *
   * - Fired when the 'relationships' meta box is to be rendered.
   *   (It displays links to posts that are associated with the active Level).
   * - Echo out the view
   * 
   * @access protected
   */
  protected function meta_box_relationships() {
    echo new View(tu()->get_path('/view/backend/levels/relation_meta'), array(
      'level'      => tu()->level,
      'test'       => tu()->level->get_test(array('post_status' => 'any')),
      '_test'      => strtolower(tu()->config['tests']['single']),
      '_resources' => strtolower(tu()->config['resources']['plural'])
    ));
  }

  /**
   * meta_box_group_access
   *
   * - Fired when the 'group_access' meta box is to be rendered.
   *   (It displays a select box of Groups to associate with the active Level).
   * - Echo out the view
   * 
   * @access protected
   */
  protected function meta_box_group_access() {
    echo new View(tu()->get_path('/view/backend/levels/access_meta'), array(
      'level'      => tu()->level,
      'test'       => tu()->level->test,
      'groups'     => Groups::find_all(),
      '_test'      => strtolower(tu()->config['tests']['single']),
      '_groups'    => strtolower(tu()->config['groups']['plural']),
      '_level'     => strtolower(tu()->config['levels']['single']),
      '_resources' => strtolower(tu()->config['resources']['plural'])
    ));
  }

  /**
   * meta_box_trainee_eligibility
   *
   * - Fired when the 'trainee_eligibility' meta box is to be rendered.
   *   (It displays a select box of Test that the Trainee must have passed
   *   before being able to access this Level).
   * - Echo out the view
   * 
   * @access protected
   */
  protected function meta_box_trainee_eligibility() {
    $eligibility_config = tu()->level->get_eligibility_config();

    echo new View(tu()->get_path('/view/backend/levels/eligibility_meta'), array(
      'level'    => tu()->level,
      'levels'   => Levels::find_all(),
      'test_ids' => $eligibility_config['test_ids']
    ));
  }

  /**
   * get_help
   * 
   * - Fired on `current_screen`
   * - Add some help related to Levels
   *
   * @access protected
   */
  protected function get_help() {
    $_group    = tu()->config['groups']['single'];
    $_groups   = strtolower(tu()->config['groups']['plural']);
    $_trainee  = tu()->config['trainees']['single'];
    $_trainees = strtolower(tu()->config['trainees']['plural']);
    $_level    = strtolower(tu()->config['levels']['single']);
    $_levels   = strtolower(tu()->config['levels']['plural']);
    $_test     = strtolower(tu()->config['tests']['single']);

    return array(
      'group_access' => array(
        'title'   => sprintf(__('%1$s access', 'trainup'), $_group),
        'content' => '<p>'.sprintf(__('By default, %1$s can be accessed by all %2$s. Using the %3$s-access box you can choose to limit access to a particular %4$s (and its relations), to 1 or more %5$s.', 'trainup'), $_levels, $_trainees, strtolower($_group), $_level, $_groups).'</p>'
      ),
      'trainee_eligibility' => array(
        'title'   => sprintf(__('%1$s eligibility', 'trainup'), $_trainee),
        'content' => '<p>'.sprintf(__('You can use the %1$s-eligibility box to prevent access to a %2$s (and its relations) unless a %1$s has passed a particular %3$s.', 'trainup'), strtolower($_trainee), $_level, $_test).'</p>'
      )
    );
  }

  /**
   * on_save
   *
   * - Fired when a Level is saved
   * - Read in any group IDs, and associate them with the active Level.
   * 
   * @param integer $post_id 
   * @param object $post
   *
   * @access protected
   */
  protected function on_save($post_id, $post) {
    $group_ids = isset($_POST['tu_groups']) ? $_POST['tu_groups'] : array();

    $eligibility_config = array(
      'test_ids' => isset($_POST['tu_eligibility']) ? $_POST['tu_eligibility'] : array()
    );

    $level = Levels::factory($post);
    $level->set_group_ids($group_ids);
    $level->set_eligibility_config($eligibility_config);
    $level->save();
  }

  /**
   * on_delete
   *
   * - Fired when a level is about to be deleted
   * - Load the level and delete it, but pass in false which prevents the post
   *   from actually being deleted, because WordPress is about to do that for us
   * 
   * @param integer $post_id 
   *
   * @access protected
   */
  protected function on_delete($post_id) {
    Levels::factory($post_id)->delete(false);
  }

  /**
   * on_trash
   *
   * - Fired when a level is about to be trashed
   * - Load the level and trash it, but pass in false which prevents the post
   *   from actually being trashed, because WordPress is about to do that for us
   * 
   * @param integer $post_id
   *
   * @access protected
   */
  protected function on_trash($post_id) {
    Levels::factory($post_id)->trash(false);
  }

  /**
   * on_untrash
   *
   * - Fired when a level is about to untrashed
   * - Load the level and untrash it, but pass in false which prevents the post
   *   from actually being untrashed, because WordPress is about to do that.
   * 
   * @param integer $post_id 
   *
   * @access protected
   */
  protected function on_untrash($post_id) {
    Levels::factory($post_id)->untrash(false);
  }

  /**
   * column_resources
   *
   * - Callback for a cell in the 'resources' column.
   * - Output a link to the current level's resources.
   * 
   * @access protected
   */
  protected function column_resources() {
    global $post;

    $href = "edit.php?post_type=tu_resource_{$post->ID}";
    $text = sprintf(__('View %1$s', 'trainup'), strtolower(tu()->config['resources']['plural']));

    echo "<a href='{$href}'>{$text}&nbsp;&raquo;</a>";
  }

  /**
   * column_test
   *
   * - Callback for a cell in the 'test' column
   * - Output a link to the current level's test (if there is one)
   * 
   * @access protected
   */
  protected function column_test() {
    global $post;

    $level = Levels::factory($post);

    if ($level->test) {
      $href = "post.php?post={$level->test->ID}&action=edit";
      $text = sprintf(__('Edit %1$s', 'trainup'), strtolower(tu()->config['tests']['single']));

      echo "<a href='{$href}'>{$text}&nbsp;&raquo;</a>";

    } else {
      $href = "post-new.php?post_type=tu_test&amp;tu_level_id={$level->ID}";
      $text = sprintf(__('Add %1$s', 'trainup'), strtolower(tu()->config['tests']['single']));

      echo "<a href='{$href}'>{$text}&nbsp;&raquo;</a>";
    }
  }


}


 
