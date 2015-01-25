<?php

/**
 * Functionality for the Forgotten password page
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class Forgotten_password_page extends Page {

  /**
   * $post_data
   *
   * @var array
   *
   * @access protected
   */
  protected $post_data = array(
    'post_title' => 'Forgotten password'
  );

  /**
   * __construct
   * 
   * - Construct the post as normal
   * - Then, if it is active, process the Forgotten Password form
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
   * - Append the Forgotten Password form.
   * - Make the view filterable for developers so they can change what is shown
   *
   * @param string $content 
   *
   * @access private
   *
   * @return string The altered content
   */
  public function _render_content($content) {
    $view = tu()->get_path('/view/frontend/page/forgotten_password');
    $view = apply_filters('tu_view_forgotten_password_page', $view);

    $content .= new View($view, $this->view_data);
    
    return $content;
  }

  /**
   * process
   *
   * - If the user enters a valid email address, assign them an activation key
   *   which temporarily blocks their account.
   * - Send them an email with a link to the Reset password page.
   * 
   * @access private
   */
  private function process() {
    $form       = isset($_POST['user']) ? $_POST['user'] : array();
    $action_url = $this->url;
    $validator  = new Validator($form);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (!wp_verify_nonce($_POST['tu_nonce'], 'tu_forgotten_password')) {
        wp_die(__('Access denied', 'trainup'));
      }

      $validates = $validator->validate(array(
        'user_email' => array('required', 'email', 'email_exists')
      ));

      if ($validates) {

        $user = Users::factory($form['user_email']);

        $user->assign_key();

        $this->send_reset_email($user);

        tu()->message->set_flash('success', __('Please check your inbox', 'trainup'));
        
        unset($form);
      }
    }

    $this->view_data = compact('validator', 'form', 'action_url');
  }

  /**
   * send_reset_email
   *
   * @param object $user The user recipient
   * 
   * @access public
   */
  public function send_reset_email($user) {
    $reset_page = Pages::factory('Reset_password');

    $to      = $user->user_email;
    $subject = __('Reset password', 'trainup');
    $body    = sprintf(__('Hi %1$s,', 'trainup'), $user->first_name);
    $body   .= "\n\n";
    $body   .= sprintf(__('You have requested a new password on %1$s', 'trainup'), tu()->get_nice_url());
    $body   .= "\n\n";
    $body   .= __('Choose a new password by clicking the link below...', 'trainup');
    $body   .= "\n\n";
    $body   .= add_query_arg(array('key' => $user->user_activation_key), $reset_page->url);

    wp_mail($to, $subject, $body);
  }

}


 
