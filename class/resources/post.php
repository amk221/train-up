<?php

/**
 * A class to represent a single Resource WP_Post
 *
 * @package Train-Up!
 * @subpackage Resources
 */

namespace TU;

class Resource extends Post {

  /**
   * $template_file
   *
   * The template file that is used to render Resources.
   *
   * @var string
   *
   * @access protected
   */
  protected $template_file = 'tu_resource';

  /**
   * __construct
   *
   * When a Resource is instantiated construct the post as normal, then if it is
   * actually active, check the permissions and save the fact that is has been
   * visited by a Trainee/User.
   *
   * @param mixed $post
   * @param boolean $active
   *
   * @access public
   */
  public function __construct($post, $active = false) {
    parent::__construct($post, $active);

    if ($this->is_active()) {
      list($ok, $error) = tu()->user->can_access_resource($this);

      if ($ok) {
        $this->save_visitedness();
        add_action('the_content', array($this, '_the_content'));
        add_action('wp_enqueue_scripts', array($this, '_add_assets'));
      } else {
        $this->bail($error);
      }
    }
  }

  /**
   * _add_assets
   *
   * - Fired on `wp_enqueue_scripts` when this Resource is active.
   * - Add the script that allows for navigation via the arrow keys
   * - Fire another action specific to Resources, so that develoeprs can enqueue
   *   or dequeue scripts assigned to a resource.
   *
   * @access private
   */
  public function _add_assets() {
    if (tu()->config['general']['arrow_key_navigation']['resources']) {
      wp_enqueue_script('tu_frontend_kbd_shortcuts');
    }

    do_action('tu_resource_frontend_assets');
  }

  /**
   * save_visitedness
   *
   * - Fired when this Resource is active
   * - Log the ID of the Resource to the current logged in user's history.
   *
   * @access private
   */
  private function save_visitedness() {
    tu()->user->save_visitied_resource($this->ID);
  }

  /**
   * get_level_id
   *
   * @access public
   *
   * @return integer ID of the Level post that this Resource belongs to.
   */
  public function get_level_id() {
    return get_post_meta($this->ID, 'tu_level_id', true);
  }

  /**
   * set_level_id
   *
   * Set the ID of the Level post that this Resource belongs to.
   *
   * @param integer $level_id
   *
   * @access public
   */
  public function set_level_id($level_id) {
    add_post_meta($this->ID, 'tu_level_id', $level_id, true);
  }

  /**
   * get_level
   *
   * @access public
   *
   * @return object Level post associated with this resource
   */
  public function get_level() {
    return Levels::factory($this->level_id);
  }

  /**
   * pagination
   *
   * If there is no next resource, just go back to the level
   *
   * @access public
   *
   * @return string prev/next link HTML for navigation through this Question's Test
   */
  public function pagination() {
    $pagination = new View(tu()->get_path("/view/frontend/questions/pagination"), array(
      'prev' => $this->get_prev(),
      'next' => $this->get_next(true)
    ));

    $result = apply_filters('tu_resource_pagination', $pagination, $this);

    return $result;
  }

  /**
   * _the_content
   *
   * - Fired on `the_content` when this Resource is active
   * - Append pagination to allow navigation through associated resources
   * - Apply some filters to allow developers to customise what is rendered
   *
   * @param string $content
   *
   * @access private
   *
   * @return string The altered content
   */
  public function _the_content($content) {
    $content .= $this->pagination();

    $content = apply_filters('tu_render_resource', $content, $this);

    return $content;
  }

  /**
   * get_featured_image
   *
   * A Resources's featured image is taken from the Level it is in.
   *
   * @access public
   *
   * @return string The URL of the image
   */
  public function get_featured_image() {
    return $this->level->featured_image;
  }

  /**
   * get_breadcrumb_trail
   *
   * Returns the array of breadcrumbs for this Resource. Let them be filtered
   * so that developers can customise them.
   *
   * @access public
   *
   * @return array
   */
  public function get_breadcrumb_trail() {
    $crumbs = parent::get_breadcrumb_trail();

    array_shift($crumbs);

    $crumbs = array_merge(
      $this->level->breadcrumb_trail,
      $crumbs
    );

    $result = apply_filters('tu_resource_crumbs', $crumbs);

    return $result;
  }

  /**
   * get_next
   *
   * - Loop through the Resources in this Resource's Level, and get the one
   *   that comes after this one.
   * - Optionally, if there is no next Resource, just go back to the Level
   * - This is a 'hack' because WordPress' get_adjacent_post is naff
   *
   * @param boolean $return_to_level
   *
   * @access public
   *
   * @return object|null The next resource
   */
  public function get_next($return_to_level = false) {
    $resources = $this->level->resources;
    $next      = null;

    for ($i = 0, $l = count($resources); $i < $l; $i++) {
      if ($resources[$i]->ID === $this->ID && $i < $l - 1) {
        $next = $resources[$i+1];
        break;
      }
    }

    return $next ?: ($return_to_level ? $this->level : null);
  }

  /**
   * get_prev
   *
   * @see get_next
   * @access public
   *
   * @return object|null The previous resource
   */
  public function get_prev() {
    $resources = $this->level->resources;

    for ($i = 0, $l = count($resources); $i < $l; $i++) {
      if ($resources[$i]->ID === $this->ID && $i > 0) {
        return $resources[$i-1];
      }
    }
  }

  /**
   * set_schedules
   *
   * Accept a hash of Group ID => Date, which specifies when this resource
   * becomes available and to whom (group).
   *
   * Before saving, convert the schedule datetime from a string into a more
   * useful time representation.
   *
   * @param array $data
   *
   * @access public
   */
  public function set_schedules($schedules = array()) {
    update_post_meta($this->ID, 'tu_schedules', $schedules);
  }

  /**
   * get_schedules
   *
   * @access public
   *
   * @see set_schedules
   * @return array Hash of of schedule data for this post.
   */
  public function get_schedules($process = true) {
    return get_post_meta($this->ID, 'tu_schedules', true) ?: array();
  }

  /**
   * get_schedule_config
   *
   * Restructure the schedules for this post into a more useful hash of data
   * including whether or not the schedules are 'ok'. (By that I mean, they
   * have passed, and therefore the resource is available)
   * The schedules are then sorted by date, earliest first.
   *
   * @access public
   */
  public function get_schedule_config() {
    $schedules = array();

    foreach ($this->schedules as $group_id => $datetime) {
      $timestamp    = strtotime($datetime);
      $now          = new \DateTime();
      $then         = new \DateTime();
      $then->setTimestamp($timestamp);
      $ok           = $now >= $then;
      $date_str     = date_i18n(get_option('date_format'), $timestamp);
      $time_str     = date_i18n(get_option('time_format'), $timestamp);
      $datetime_str = sprintf(__('%1$s at %2$s', 'trainup'), $date_str, $time_str);
      $group        = ($group_id === 'all') ? null : Groups::factory($group_id);

      $schedules[$group_id] = compact(
        'ok', 'group', 'timestamp', 'datetime',
        'date_str', 'time_str', 'datetime_str'
      );
    }

    $sort = function($a, $b) {
      return $a['timestamp'] > $b['timestamp'];
    };

    uasort($schedules, $sort);

    return $schedules;
  }

  /**
   * is_scheduled
   *
   * Return whether or not this Resource is scheduled.
   * - A resource is considered to be scheduled if it has datetimes associated
   *   with a group ID.
   * - If a resource has no schedules, it is freely available at any time
   *
   * @access public
   *
   * @return boolean
   */
  public function is_scheduled() {
    return count($this->schedules) >= 1;
  }

  /**
   * is_scheduled_for_all_groups
   *
   * @access public
   *
   * @return boolean Whether or not this resource is scheduled and that
   * schedule affects all groups.
   */
  public function is_scheduled_for_all_groups() {
    return array_key_exists('all', $this->schedules);
  }

  /**
   * is_available_to
   *
   * - Firstly, make this resource available to all unless it appears to have
   *   been scheduled.
   * - Secondly, run through the schedules and make this resource available
   *   only if the user is in the correct group and the time has begun.
   *
   * @access public
   *
   * @return array Whether or not this resource is available to the given user
   */
  public function is_available_to_user($user) {
    $_resource = strtolower(tu()->config['resources']['single']);
    $available = true;
    $error     = '';
    $date_str  = '';
    $time_str  = '';

    if (!$user->is_administrator()) {
      foreach ($this->schedule_config as $group_id => $schedule) {
       $affects_user = ($group_id === 'all') || $user->has_group($group_id);

        if ($affects_user && !$schedule['ok']) {
          $available = false;
          break;
        }
      }

      if (!$available) {
        if ($group_id === 'all') {
          $error = apply_filters('tu_resource_schedule_not_ok', sprintf(
            __('This %1$s is not available until %2$s'),
            $_resource, $schedule['datetime_str']
          ), $schedule);
        } else {
          $error = apply_filters('tu_resource_group_schedule_not_ok', sprintf(
            __('This %1$s is not available to %2$s until %3$s'),
            $_resource, $schedule['group']->post_title, $schedule['datetime_str']
          ), $schedule);
        }
      }
    }

    $result = array($available, $error);
    $result = apply_filters('tu_resource_available_to_user', $result, $this, $user);

    return $result;
  }

}



