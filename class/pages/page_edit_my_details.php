<?php

/**
 * Functionality for the Edit my details page
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class Edit_my_details_page extends Page {

  /**
   * $auth_required
   *
   * The Edit My Details page requires a user be logged in.
   *
   * @var boolean
   *
   * @access protected
   */
  protected $auth_required = true;

  /**
   * $post_data
   *
   * Default post used for this page.
   *
   * @var array
   *
   * @access protected
   */
  protected $post_data = array(
    'post_title' => 'Edit my details'
  );

  /**
   * __construct
   *
   * - Construct the post as normal
   * - Then, if it is active process an form submission
   * - Add actions to render the content and enqueue the scripts & styles.
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
      add_action('wp_enqueue_scripts', array($this, '_add_assets'));
    }
  }

  /**
   * _add_assets
   *
   * - Fired on `wp_enqueue_scripts`.
   * - Enqueue the necessary scripts and styles for the Edit my details page
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
   * - Fired on `the_content` when this page is active.
   * - Append a form populated with the user data.
   * - Make the view filterable so developers can change what is shown.
   * 
   * @param string $content
   *
   * @access private
   *
   * @return string The altered content
   */
  public function _render_content($content) {
    $this->view_data['config']         = tu()->config;
    $this->view_data['type']           = 'edit';
    $this->view_data['submit_text']    = __('Save changes', 'trainup');
    $this->view_data['all_groups']     = Groups::find_all();
    $this->view_data['trainee_groups'] = tu()->user->groups;
    $this->view_data['form']['groups'] = tu()->user->group_ids;

    $view = tu()->get_path('/view/frontend/forms/user');
    $view = apply_filters('tu_view_user_form', $view);

    $content .= new View($view, $this->view_data);

    return $content;
  }

  /**
   * process
   *
   * - Fired before this page is active, process the form submission.
   * - Validate and save the user information.
   * 
   * @access private
   */
  private function process() {
    $user_data   = tu()->user->all_data;
    $form        = isset($_POST['user']) ? $_POST['user'] : $user_data;
    $validator   = new Validator($form);
    $save_groups = tu()->config['trainees']['can_choose_groups'] !== 'disabled';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (!wp_verify_nonce($_POST['tu_nonce'], 'tu_user_form')) {
        wp_die(__('Access denied', 'trainup'));
      }

      $group_ids = array();

      $rules = apply_filters('tu_edit_my_details_validation_rules', array(
        'first_name' => array('required'),
        'last_name'  => array('required'),
        'user_email' => array('required', 'email'),
        'user_pass'  => array()
      ));

      if ($form['user_email'] != $user_data['user_email']) {
        $rules['user_email'][] = 'email_unique';
      }

      if (!empty($form['user_pass'])) {
        array_push($rules['user_pass'], 'required', 'password');
      } else {
        unset($form['user_pass']);
      }

      if ($save_groups) {
        $rules['groups'] = array('required');
        $group_ids = isset($form['groups']) ? (array)$form['groups'] : array();
        unset($form['groups']);
      }

      $validates = $validator->validate($rules);

      if ($validates) {
        tu()->user->save($form);

        if ($save_groups) {
          tu()->user->set_group_ids($group_ids);
        }

        tu()->message->set_flash('success', __('Your details were updated', 'trainup'));
      }
    }

    $this->view_data = compact('validator', 'form');
  }

}


 
