<?php

/**
 * Functionality for the Sign up page
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class Sign_up_page extends Page {

  /**
   * $post_data
   *
   * Default post data for the Sign up page.
   *
   * @var array
   *
   * @access protected
   */
  protected $post_data = array(
    'post_title' => 'Sign up'
  );

  /**
   * __construct
   *
   * - Construct the page as normal
   * - Then, if it is active, check whether or not the user is responding to a
   *   'confirm your email' link. (If so, activate their account & log then in)
   * - Or, process a sign up submission.
   * 
   * @param mixed $page
   * @param boolean $active
   *
   * @access public
   */
  public function __construct($page = null, $active = false) {
    parent::__construct($page, $active);

    if ($this->is_active()) {
      $this->detect_confirm_email();
      $this->process();

      add_filter('the_content', array($this, '_render_content'));
      add_action('wp_enqueue_scripts', array($this, '_add_assets'));
    }
  }

  /**
   * _add_assets
   *
   * - Fired on `wp_enqueue_scripts` when the Sign up page is active.
   * - Enqueue the necessary styles and scripts.
   * 
   * @access private
   */
  public function _add_assets() {
    wp_enqueue_style('tu_autocompleter');
    wp_enqueue_script('tu_autocompleter');
  }

  /**
   * _render_content
   * 
   * - Fired on `the_content`.
   * - Append the user sign up form
   * - Make the view filterable so that developers cna change what is shown
   *
   * @param string $content
   *
   * @access private
   *
   * @return string The altered content
   */
  public function _render_content($content) {
    $this->view_data['config']         = tu()->config;
    $this->view_data['type']           = 'sign-up';
    $this->view_data['submit_text']    = __('Sign up', 'trainup');
    $this->view_data['all_groups']     = Groups::find_all();
    $this->view_data['trainee_groups'] = array();

    $view = tu()->get_path('/view/frontend/forms/user');
    $view = apply_filters('tu_view_user_form', $view);
    $content .= new View($view, $this->view_data);

    return $content;
  }

  /**
   * process
   *
   * - Fired before this page is shown. Validate the sign up form.
   * - The user isn't required to enter a username, instead that is generated
   *   automatically, to speed up the process.
   * - Once the user has signed up, sign them in and redirect to their
   *   My Account page if instant sign up is on.
   * - Otherwise, they will have to confirm their email address first.
   * - Fire an action so that developers can latch on to new sign ups
   * 
   * @access private
   */
  private function process() {
    $form        = isset($_POST['user']) ? $_POST['user'] : array();
    $validator   = new Validator($form);
    $save_groups = (
      tu()->config['trainees']['can_choose_groups'] &&
      tu()->config['groups']['show_groups_on_sign_up']
    );
    $user_login = tu()->config['general']['login_by'] === 'user_login';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $group_ids = array();

      $rules = apply_filters('tu_sign_up_validation_rules', array(
        'first_name' => array('required'),
        'last_name'  => array('required'),
        'user_email' => array('required', 'email', 'email_unique'),
        'user_pass'  => array('required', 'password')
      ));

      if ($save_groups) {
        $rules['groups'] = array('required');
        $group_ids = isset($form['groups']) ? (array)$form['groups'] : array();
        unset($form['groups']);
      }

      if ($user_login) {
        $rules['user_login'] = array('required', 'username_unique');
      }

      $validates = $validator->validate($rules);

      $validates = apply_filters('tu_trainee_sign_up_authorised', $validates, $form);

      if ($validates) {
        $form['role']       = 'tu_trainee';
        $form['user_login'] = $user_login
          ? $form['user_login']
          : Users::generate_username($form['first_name'], $form['last_name']);

        $trainee = Trainees::factory();
        $trainee->save($form);

        if ($save_groups) {
          $trainee->set_group_ids($group_ids);
        }

        add_action('tu_trainee_signed_up', $trainee);

        $this->do_email_notifications($trainee);

        if (tu()->config['trainees']['instant_sign_up']) {

          tu()->message->set_flash('success', __('Sign up successful!', 'trainup'));

          wp_signon(array(
            'user_login'    => $form['user_login'],
            'user_password' => $form['user_pass']
          ));

          Pages::factory('My_account')->go_to();

        } else {

          tu()->message->set_flash('success',
            __('Sign up successful. Please check your inbox', 'trainup')
          );

          $trainee->assign_key();

          $this->send_confirm_your_email($trainee);

          unset($form);

        }
      }
    }

    $this->view_data = compact('validator', 'form');
  }

  /**
   * do_email_notifications
   *
   * - Fired when a new Trainee signs up
   * - Send an email to the Trainee (if applicable)
   * - Send an email to the Administrators too (if applicable)
   * 
   * @param object $trainee
   *
   * @access private
   */
  public function do_email_notifications($trainee) {
    $who      = tu()->config['trainees']['sign_up_notifications'];
    $_trainee = strtolower(tu()->config['trainees']['single']);
    $emails   = array();

    if (isset($who['administrators'])) {
      $emails[] = array(
        'to'      => Administrators::mailing_list(),
        'subject' => sprintf(__('New %1$s sign up', 'trainup'), $_trainee),
        'body'    => file_get_contents(tu()->get_path("/view/backend/emails/administrator_sign_up.txt"))
      );
    }
    if (isset($who['trainees'])) {
      $emails[] = array(
        'to'      => array($trainee->user_email),
        'subject' => sprintf(__('Welcome %1$s', 'trainup'), $trainee->first_name),
        'body'    => tu()->config['trainees']['sign_up_email_template']
      );
    }

    foreach ($emails as $email) {
      foreach ($email['to'] as $to) {
        $body = Users::do_swaps($trainee, $email['body']);
        wp_mail($to, $email['subject'], $body);
      }
    }
  }

  /**
   * send_confirm_your_email
   * 
   * Send an email to the Trainee containing their activation code.
   * The user must click that link, which will remove the activationc code,
   * thereby confirming the email address is theirs, and letting them log in.
   *
   * @param object $trainee 
   *
   * @access private
   */
  private function send_confirm_your_email($trainee) {
    $to      = $trainee->user_email;
    $subject = __('Confirm your email address', 'trainup');
    $body    = sprintf(__('Hi %1$s,', 'trainup'), $trainee->first_name);
    $body   .= "\n\n";
    $body   .= sprintf(__('You just signed up on %1$s', 'trainup'), tu()->get_nice_url());
    $body   .= "\n\n";
    $body   .= __('Please confirm your email address by clicking the link below...', 'trainup');
    $body   .= "\n\n";
    $body   .= add_query_arg(array('key' => $trainee->user_activation_key), $this->get_url());

    wp_mail($to, $subject, $body);
  }

  /**
   * detect_confirm_email
   *
   * Listen out for requests to the confirm email links sent out in emails.
   * When one is visited, delete the Trainee's activation key, activating their
   * account and log them in.
   * 
   * @access private
   */
  private function detect_confirm_email() {
    global $wpdb;

    if (!isset($_GET['key'])) return;

    $trainee = Users::find_by_activation_key($_GET['key']);

    if ($trainee) {

      $trainee->clear_key();
      
      tu()->message->set_flash('success', __('Welcome!', 'trainup'));

      wp_set_auth_cookie($trainee->ID);

      Pages::factory('My_account')->go_to();

    } else {

      tu()->message->set_flash('error', __('Invalid key', 'trainup'));

    }
  }

}

