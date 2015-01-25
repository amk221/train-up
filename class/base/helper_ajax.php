<?php

/**
 * Helper class for handling AJAX requests
 *
 * @package Train-Up!
 */

namespace TU;

class Ajax_helper {

  /**
   * __construct
   * 
   * Listen out for privileged and non-privileged AJAX requests.
   *
   * @access public
   */
  public function __construct() {
    add_action('wp_loaded', array($this, '_enable_ajax'));
    add_action('wp_ajax_tu_ajax', array($this, '_handle_ajax'));
    add_action('wp_ajax_nopriv_tu_ajax', array($this, '_handle_nopriv_ajax'));
  }

  /**
   * _enable_ajax
   *
   * - Fired on `wp_loaded`, when WordPress is fully loaded.
   * - Set the WordPress ajax action as to be tu_ajax if it looks like the
   *   the request is a TU ajax request. 
   * - This means that WordPress will create callbacks for ajax specific to us
   *   and prevents us from having to specify action: 'tu_ajax' all the time.
   * 
   * @access private
   */
  public function _enable_ajax() {
    if (isset($_REQUEST['tu_func'])) {
      $_REQUEST['action'] = 'tu_ajax';
    }
  }

  /**
   * $ajax
   *
   * - An array of static function names, lowercase'd and missing a semicolon,
   *   for the hell of it.
   * - These static functions are fired automatically when an AJAX request
   *   is made referencing them.
   *
   * @var array
   *
   * @access private
   */
  private $ajax = array(
    'importer:import',
    'emailer:send',
    'emailer:autocomplete',
    'trainees:autocomplete',
    'questions:save_answer',
    'results:set_manual_percentage'
  );

  /**
   * $nopriv_ajax
   *
   * An array of static function names allowed to be automatically fired when
   * an unprivilaged user makes an AJAX request.
   *
   * @var array
   *
   * @access private
   */
  private $nopriv_ajax = array(
    'groups:autocomplete'
  );

  /**
   * is_ajax
   *
   * @access public
   *
   * @return boolean Whether or not the current request is being made via AJAX.
   */
  public function is_ajax() {
    return defined('DOING_AJAX') && DOING_AJAX;
  }

  /**
   * bail
   *
   * Fired when a static function name is provided via an AJAX request, but it
   * doesn't not exist, or is not allowed to be run.
   * 
   * @access private
   */
  private function bail() {
    wp_send_json(__('Invalid action', 'trainup'));
  }

  /**
   * exec
   *
   * - Fired when an AJAX request has been approved, so execute its associated
   *   function, sending in any the request parameters as arguments.
   *   (also send along any uploaded file info)
   * - e.g. ($arg1, $arg2, $files)
   * - Encode the data as json & output it.
   * 
   * @access private
   */
  private function exec() {
    $func  = $_REQUEST['tu_func'];
    $args  = (array)(isset($_REQUEST['tu_args']) ? $_REQUEST['tu_args'] : array());
    $files = (array)(isset($_FILES['tu_args'])   ? $_FILES['tu_args']   : array());
    
    if (count($files) > 0) $args[] = $files;

    preg_match('/^(\w+):(\w+)$/', $func, $matches);

    $class  = ucfirst($matches[1]);
    $method = "ajax_{$matches[2]}";
    $func   = __NAMESPACE__."\\{$class}::{$method}";

    $data = call_user_func_array($func, $args);

    wp_send_json($data);
  }

  /**
   * _handle_ajax
   *
   * - Fired when an AJAX request is made (either a frontend-unprivileged
   *   request or a request with which the user can access the backend too).
   * - If the requested static function to call exists, and is allowed to be
   *   called, then call it... it should return JSON or something.
   * 
   * @access private
   */
  public function _handle_ajax() {
    $both = array_merge($this->ajax, $this->nopriv_ajax);

    if (in_array($_REQUEST['tu_func'], $both)) {
      $this->exec();
    } else {
      $this->bail();
    }
  }

  /**
   * _handle_nopriv_ajax
   *
   * - Fired when an AJAX request is made by a user from the frontend of the
   *   site, i.e. they're not logged in to the backend.
   * - If the requested static function to call exists, and it is allowed to be
   *   called, then call it.
   * 
   * @access private
   */
  public function _handle_nopriv_ajax() {
    if (in_array($_REQUEST['tu_func'], $this->nopriv_ajax)) {
      $this->exec();
    } else {
      $this->bail();
    }
  }

}


