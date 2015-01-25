<?php

/**
 * The Emailer administration screen
 *
 * @package Train-Up!
 * @subpackage Emailer
 */

namespace TU;

class Emailer_admin extends Admin {

  /**
   * $front_page
   *
   * The menu item 'slug' for the emailer admin screen
   *
   * @var string
   *
   * @access protected
   */
  protected $front_page = 'admin.php?page=tu_emailer';

  /**
   * $context
   *
   * The context is a hash of information used to make the emailer appear as
   * if it is nested in another 'section'. For example, if the emailer was
   * accessed via Groups, the breadcrumbs should be Train-Up! > Groups > Emailer
   *
   * @var array
   *
   * @access protected
   */
  protected $context = null;

  /**
   * __construct
   *
   * - Add a sub menu item for the Emailer
   *   (although, this is hidden with CSS in plugin.php)
   * - Add the crumbs the user took to get to the emailer
   *   (these can be spoofed by setting a `$context`)
   * - Force highlight a sub menu item, because the emailer itself doesn't have
   *   a menu item, but can belong to any other sub menu item's 'context'.
   * 
   * @access public
   */
  public function __construct() {
    parent::__construct();

    add_action('admin_menu', array($this, '_add_sub_menu_item'));

    if ($this->is_active()) {
      $this->check();
      $this->add_crumbs();

      if ($this->context) {
        $this->highlight_sub_menu_item = $this->context['menu_slug'];
      }

      add_action('admin_enqueue_scripts', array($this, '_add_assets'));
    }
  }

  /**
   * check
   *
   * Inspect the request parameters and see if a context is being provided.
   * If so, then set it. (The Emailer admin screen *can* run without a context)
   * 
   * @access private
   */
  private function check() {
    $context = isset($_REQUEST['tu_context']) ? $_REQUEST['tu_context'] : array();

    if (
      isset($context['title']) &&
      isset($context['link'])  &&
      isset($context['menu_slug'])
    ) {
      $this->context = $context;
    }
  }

  /**
   * _add_assets
   *
   * - Fired on `admin_enqueue_scripts` when the Emailer is active
   * - Enqueue the necessary styles for the emailer to work.
   * - Note: the scripts are included globally in base/admin.php because it
   *   helps with bulk actions which are not specific to the emailer page.
   * 
   * @access private
   */
  public function _add_assets() {
    wp_enqueue_style('tu_emailer');
  }

  /**
   * _add_sub_menu_item
   *
   * - Add a sub menu item for the emailer.
   * - The menu item is hidden using CSS in plugin.php, because generally we
   *   don't actually want it to be accessed directly.
   * 
   * @access private
   */
  public function _add_sub_menu_item() {
    add_submenu_page(
      'tu_plugin',
      __('Emailer', 'trainup'),
      __('Emailer', 'trainup'),
      'tu_emailer',
      'tu_emailer',
      array($this, '_index')
    );
  }

  /**
   * _index
   *
   * - Callback for the sub menu item for the emailer.
   * - Read in the user IDs from the request, use these to load the users
   *   and pre-populate the recipients list.
   * 
   * @access private
   */
  public function _index() {
    $user_ids = isset($_REQUEST['user_ids']) ? $_REQUEST['user_ids'] : '';
    $users    = array();

    if ($user_ids) {
      $users = Users::find_all(array(
        'include' => $user_ids
      ));
    }

    $default_template = file_get_contents(
      tu()->get_path("/view/backend/emails/default_template.txt")
    );

    $from_addresses = array(
      array(
        'name'    => tu()->user->display_name,
        'address' => tu()->user->user_email
      ),
      array(
        'name'    => '',
        'address' => Settings::get_default_email_address()
      )
    );

    $data = compact('users', 'default_template', 'from_addresses');

    echo new View(tu()->get_path('/view/backend/emailer/index'), $data);
  }  

  /**
   * add_crumbs
   *
   * - Add crumbs to show the route the user took to get to the emailer.
   * - If a context is provided, spoof the route.
   * 
   * @access private
   */
  private function add_crumbs() {
    if ($this->context) {
      tu()->add_crumb($this->context['link'], $this->context['title']);
    }

    tu()->add_crumb('', __('Send emails', 'trainup'));
  }

}


 
