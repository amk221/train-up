<?php

/**
 * Functionality for the Reset password page
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class Reset_password_page extends Page {

  /**
   * $post_data
   *
   * @var array
   *
   * @access protected
   */
  protected $post_data = array(
    'post_title' => 'Reset password'
  );

  /**
   * __construct
   * 
   * - Construct the post as normal
   * - Then, if it is active, process the Reset Password form
   * - Render the content.
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
      add_filter('the_content', array($this, '_render_content'));
    }
  }

  /**
   * _render_content
   * 
   * - Fired on `the_content` when this post is active.
   * - Append the Reset Password form.
   * - Make the view filterable so developers can change what is shown
   *
   * @param string $content 
   *
   * @access private
   *
   * @return string The altered content
   */
  public function _render_content($content) {
    $view = tu()->get_path('/view/frontend/page/reset_password');
    $view = apply_filters('tu_view_reset_password_page', $view);
    $content .= new View($view, $this->view_data);

    return $content;
  }

  /**
   * process
   *
   * - Try to find a user by the key provided.
   * - When a valid key is given, allow access to the reset password form.
   * - Once the user has submitted their new password, clear their key
   *   and log them in.
   * 
   * @access private
   */
  private function process() {
    $posting = $_SERVER['REQUEST_METHOD'] === 'POST';
    $form    = isset($_POST['user']) ? $_POST['user'] : array();

    if ($posting) {
      $key = $form['user_activation_key'];
    } else {
      $key = isset($_GET['key']) ? $_GET['key'] : '';
      $form['user_activation_key'] = $key;
    }

    $user       = Users::find_by_activation_key($key);
    $action_url = $this->url;
    $validator  = new Validator($form);

    if (!$user) {
      tu()->message->set_flash('error', __('Invalid key', 'trainup'));
      Pages::factory('Forgotten_password')->go_to();
    }
    else if ($posting) {

      if (!wp_verify_nonce($_POST['tu_nonce'], 'tu_reset_password')) {
        wp_die(__('Access denied', 'trainup'));
      }

      $validates = $validator->validate(array(
        'user_pass' => array('required', 'password', 'password_match')
      ));

      if ($validates) {

        $user->save(array(
          'user_pass' => $form['user_pass']
        ));

        $user->clear_key();

        wp_set_auth_cookie($user->ID);

        tu()->message->set_flash('success', __('Password saved', 'trainup'));

        Pages::factory('My_account')->go_to();
        
      }
    }

    $this->view_data = compact('validator', 'form', 'action_url');
  }

}


 
