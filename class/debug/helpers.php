<?php

/**
 * General helper functions for working with the debugging stuff
 *
 * @package Train-Up!
 * @subpackage Debug
 */

namespace TU;

class Debug {

  /**
   * is_on
   * 
   * @access public
   * @static
   *
   * @return boolean Whether or not Debug mode is on
   */
  public static function is_on() {
    return defined('TU_DEBUG') && TU_DEBUG;
  }

}


