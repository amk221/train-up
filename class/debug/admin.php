<?php

/**
 * The debug administration screen
 *
 * @package Train-Up!
 * @subpackage Debug
 */

namespace TU;

class Debug_admin extends Admin {

  /**
   * $front_page
   *
   * The sub menu item slug that is the front page for the Debug admin class.
   *
   * @var string
   *
   * @access protected
   */
  protected $front_page = 'admin.php?page=tu_debug';

  /**
   * __construct
   * 
   * If the debug flag is on, enable some extra helpful functionality.
   *
   * @access public
   */
  public function __construct() {
    if (!Debug::is_on()) return;

    parent::__construct();

    add_action('admin_menu', array($this, '_add_sub_menu_item'));

    if ($this->is_active()) {  
      $this->add_crumbs();
      add_action('admin_init', array($this, '_process_action'));
    }
  }

  /**
   * _add_sub_menu_item
   *
   * Add an admin page for debugging related stuff.
   * 
   * @access private
   */
  public function _add_sub_menu_item() {
    add_submenu_page(
      'tu_plugin',
      __('Debug', 'trainup'),
      __('Debug', 'trainup'),
      'tu_debugger',
      'tu_debug',
      array($this, '_index')
    );
  }

  /**
   * _index
   *
   * Callback for the debug sub menu item. Render the debug page.
   * 
   * @access private
   */
  public function _index() {
    echo new View(tu()->get_path('/view/backend/page/debug'), array(
      'plugin_name'       => tu()->get_name(),
      'has_fixtures'      => Fixtures::installed(),
      'test_suite_output' => Test_suite::$output
    ));
  }

  /**
   * _process_action
   *
   * - Fired on `admin_init`
   * - Inspect the request parameters, fire a function if it exists.
   * 
   * @access private
   */
  public function _process_action() {
    $action = isset($_REQUEST['tu_action']) ? $_REQUEST['tu_action'] : null;
      
    if ($action === 'install_fixtures') {
      Fixtures::install();
    } else if ($action === 'uninstall_fixtures') {
      Fixtures::uninstall();
    } else if ($action === 'run_tests') {
      Test_suite::run();
    } else if ($action === 'delete_redundant_files') {
      $this->delete_redundant_files();
    }
  }

  /**
   * add_crumbs
   *
   * @access private
   */
  private function add_crumbs() {
    tu()->add_crumb($this->front_page, __('Debug', 'trainup'));
  }

  /**
   * delete_redundant_files
   * 
   * @access private
   */
  private function delete_redundant_files() {
    $info    = Tests::delete_redundant_files();
    $message = Tests::deletion_message($info);

    tu()->message->set_flash('success', $message);
  }

}


 
