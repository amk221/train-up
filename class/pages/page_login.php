<?php

/**
 * Functionality for the Login page
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class Login_page extends Page {

  /**
   * $post_data
   *
   * Default post data for this post.
   *
   * @var array
   *
   * @access protected
   */
  protected $post_data = array(
    'post_title' => 'Login'
  );

  /**
   * __construct
   *
   * - Construct this post as normal
   * - Then, if it is active, process the form & render the content
   * 
   * @param mixed $page 
   * @param boolean $active
   *
   * @access public
   */
  public function __construct($page = null, $active = false) {
    parent::__construct($page, $active);

    if ($this->is_active()) {
      $this->process();

      add_action('the_content', array($this, '_render_content'));
    }
  }

  /**
   * _render_content
   * 
   * - Fired on `the_content` for this page.
   * - Append the form and the view data from a (possible) submission
   * - Make the view filterable so that developers can change what is shown
   *
   * @param string $content
   *
   * @access private
   *
   * @return string The altered content
   */
  public function _render_content($content = '') {
    $view = tu()->get_path('/view/frontend/page/login');
    $view = apply_filters('tu_view_login_page', $view);
    $content .= new View($view, $this->view_data);
    
    return $content;
  }

  /**
   * process
   *
   * - Process the login form
   * - Accept logins by username OR email address. If an email address is 
   *   provided, then attempt to load the user by the email address provided
   *   in order to get their login name.
   * - To pass validation, the user must have been activated (i.e. not have
   *   a `user_activation_key`.
   * - Assign the form data and the validator to the view, so it can rendered.
   * - Make the authorised value be filtered so developers can customise whether
   *   somebody can be logged in or not
   * 
   * @access private
   */
  private function process() {
    $form       = isset($_POST['user']) ? $_POST['user'] : array();
    $action_url = $this->url;
    $return_to  = isset($_REQUEST['return_to']) ? $_REQUEST['return_to'] : '';
    $validator  = new Validator($form);
    $authorised = is_user_logged_in();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (!wp_verify_nonce($_POST['tu_nonce'], 'tu_login')) {
        wp_die(__('Access denied', 'trainup'));
      }

      $form['user_login'] = isset($form['user_login']) ? $form['user_login'] : '';
      
      if (isset($form['user_email'])) {
        $user = Users::factory($form['user_email']);
        $form['user_login'] = $user->loaded() ? $user->user_login : '';
      }

      $validator = new Validator($form);

      $authorised = $validator->validate(array(
        'user_login'    => array('activated', 'auth_ok'),
        'user_password' => array('required')
      ));

      $authorised = apply_filters('tu_login_authorised', $authorised, $form);
    }

    if ($authorised) {
      if ($return_to) {
        go_to($return_to);
      } else {
        Pages::factory('My_account')->go_to();
      }
    }

    $this->view_data = compact('validator', 'form', 'return_to', 'action_url');
  }

}


 
