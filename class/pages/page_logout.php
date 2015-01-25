<?php

/**
 * Functionality for the Logout page
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class Logout_page extends Page {

  /**
   * $post_data
   *
   * Default post data for the Logout page.
   *
   * @var array
   *
   * @access protected
   */
  protected $post_data = array(
    'post_title' => 'Logout'
  );

  /**
   * __construct
   *
   * Construct the post as normal, then if it is active logout.
   * 
   * @param mixed $page
   * @param boolean $active
   *
   * @access public
   */
  public function __construct($page = null, $active = false) {
    parent::__construct($page, $active);
    
    if ($this->is_active()) {
      $this->logout();
    }
  }

  /**
   * logout
   *
   * Log the current user out, and redirect to the login page.
   * 
   * @access private
   */
  private function logout() {
    $return_to = isset($_REQUEST['return_to'])
      ? $_REQUEST['return_to']
      : Pages::factory('Login')->url;

    wp_logout();
    
    go_to($return_to);
  }

}


 
