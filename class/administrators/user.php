<?php

/**
 * Class to represent an Administrator (Wraps a WP_User)
 *
 * @package Train-Up!
 * @subpackage Administrators
 */

namespace TU;

class Administrator extends User {
 
  /**
   * can_access_trainee
   *
   * - Returns whether or not the Administrator can access a Trainee
   * - The default is true, because at the moment only Group managers have
   *   limited access to a Trainee.
   * 
   * @param object $trainee
   *
   * @access public
   *
   * @return array
   */
  public function can_access_trainee($trainee) {
    $result = array(true, '');

    $result = apply_filters(
      'tu_admin_can_access_trainee',
      $result, $this, $trainee
    );

    return $result;
  }

}


 
