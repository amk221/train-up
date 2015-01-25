<?php

/**
 * Helper class for rendering messages on the front and backend
 *
 * @package Train-Up!
 */

namespace TU;

class Message_helper {

  /**
   * __construct
   * 
   * When the message helper is instantiated, listen out for `the_content`
   * and `all_admin_notices` actions. These are the two places (frontend and
   * backend respectively) that we want to render messages.
   *
   * @access public
   */
  public function __construct() {
    add_filter('the_content', array($this, '_the_content'));
    add_filter('all_admin_notices', array($this, '_all_admin_notices'));
  }

  /**
   * _the_content
   *
   * - Fired on `the_content`. 
   * - Render the flash message at the top of the content (if there is one)
   *   and only if we're in the Train-Up! part of the website
   * 
   * @param string $content
   *
   * @access private
   *
   * @return string The altered content
   */
  public function _the_content($content) {
    if (tu()->in_frontend()) {
      $this->render_flash();
    }
    return $content;
  }

  /**
   * _all_admin_notices
   *
   * - Fired on `all_admin_notices`
   * - Render the flash message if there is one.
   * 
   * @access private
   */
  public function _all_admin_notices() {
    $this->render_flash();
  }

  /**
   * view
   *
   * - Return the processed view as a string of HTML, for a particular type of
   *   message. 
   * - When on the backend (in WordPress), use a template that looks like the
   *   built in WordPress messages
   * - When on the frontend, use our own message HTML structure and class names.
   * 
   * @param string $type    The message type, e.g. 'success' or 'error'
   * @param string $message The message string itself.
   * @param string $dir     Optional directory to find the message view file.
   *
   * @access public
   *
   * @return string The 'rendered' message
   */
  public function view($type, $message, $dir = null) {
    $dir  = $dir ?: (is_admin() ? 'backend' : 'frontend');
    $view = new View(tu()->get_path("/view/{$dir}/misc/message"), array(
      'message' => $message,
      'type'    => $type
    ));

    return $view->__toString();
  }

  /**
   * render
   *
   * @param string $type Type of message, e.g. error/success
   * @param string $message The message itself
   *
   * @access public
   */
  public function render($type, $message) {
    echo $this->view($type, $message);
  }

  /**
   * __call
   * 
   * Automatically render a message based on the missing method name, e.g.
   * `$this->error('An error occurred')`
   *
   * @param string $name 
   * @param array $args
   *
   * @access public
   */
  public function __call($name, $args) {
    $this->render($name, $args[0]);
  }

  /**
   * set_flash
   * 
   * Store the message, and the type of message in the current session, to be
   * pulled out later.
   *
   * @param string $type
   * @param string $message
   *
   * @access public
   */
  public function set_flash($type, $message) {
    $_SESSION['tu_flash'] = func_get_args();
  }

  /**
   * get_flash
   * 
   * @access private
   *
   * @return array The flash message and type of message if there is one.
   */
  private function get_flash() {
    return isset($_SESSION['tu_flash']) ? $_SESSION['tu_flash'] : null;
  }

  /**
   * clear_flash
   * 
   * Remove the flash message, probably because it has been used.
   *
   * @access private
   */
  private function clear_flash() {
    unset($_SESSION['tu_flash']);
  }

  /**
   * render_flash
   *
   * - Retrieve the flash message from the current session.
   * - Render it.
   *   (rendered inside a div, so that we have a place to insert JS messages)
   * - Remove the flash message from the session, because it's been used.
   * 
   * @access private
   */
  private function render_flash() {
    list($type, $message) = $this->get_flash();
    
    ?><div class="tu-flash"><?php
        $this->render($type, $message);
    ?></div><?php

    $this->clear_flash();
  }

}