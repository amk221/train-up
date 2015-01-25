<?php

/**
 * General helper functions for working with Resources
 *
 * @package Train-Up!
 * @subpackage Resources
 */

namespace TU;

class Resources {

  /**
   * factory
   * 
   * @param array|object $resource
   *
   * @access public
   * @static
   *
   * @return object A Resource instance
   */
  public static function factory($resource = null) {
    return new Resource($resource);
  }

  /**
   * localised_js
   * 
   * @access public
   * @static
   *
   * @return array A hash of localised JS for when managing resources.
   */
  public static function localised_js() {
    $_group  = strtolower(tu()->config['groups']['single']);
    $_groups = strtolower(tu()->config['groups']['plural']);

    return array(
      '_scheduleAlreadyExists'      => sprintf(__('A schedule already exists for that %1$s'), $_group),
      '_scheduleAffectingAllGroups' => sprintf(__('There is already a schedule affecting all %1$s', 'trainup'), $_groups),
      '_allScheduleNotAllowed'      => sprintf(__('Cannot add a schedule affecting all %1$s when schedules exist for individual %1$s', 'trainup'), $_groups),
      '_invalidScheduleTime'        => __('Invalid schedule time', 'trainup'),
      '_confirmDeleteSchedule'      => __('Are you sure you want to remove this schedule?', 'trainup')
    );
  }


}


 
