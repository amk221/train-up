<?php

/**
 * The base admin screen for working with Resources
 *
 * @package Train-Up!
 * @subpackage Resources
 */

namespace TU;

class Resources_admin extends Admin {

  /**
   * $front_page
   *
   * The menu slug of the Resources sub menu item
   *
   * @var string
   *
   * @access protected
   */
  protected $front_page = 'admin.php?page=tu_resources';

  /**
   * __construct
   *
   * - Construct the Admin screen as normal, then add a sub menu item
   *   specifically for a Resources section.
   * - If this admin screen is active, add the breadcrumbs, scripts and styles.
   * 
   * @access public
   */
  public function __construct() {
    parent::__construct();

    add_action('admin_menu', array($this, '_add_sub_menu_item'));

    if ($this->is_active()) {
      $this->add_crumbs();
      add_action('admin_enqueue_scripts', array($this, '_add_assets'));
    }
  }

  /**
   * _add_assets
   *
   * - Fired on 'admin_enqueue_scripts' when Resource administration is active.
   * - Enqueue any scripts and styles
   * 
   * @access public
   * @static
   */
  public static function _add_assets() {
    wp_enqueue_script('tu_resources');
    wp_enqueue_style('tu_resources');
  }

  /**
   * _add_sub_menu_item
   *
   * - Fired on `admin_menu`
   * - Add a sub menu item for Resources to the Train-Up! main menu item,
   * - When clicked, go to the 'index' page
   * 
   * @access private
   */
  public function _add_sub_menu_item() {
    add_submenu_page(
      'tu_plugin',
      tu()->config['resources']['plural'],
      tu()->config['resources']['plural'],
      'edit_tu_resources',
      'tu_resources',
      array($this, '_index')
    );
  }

  /**
   * _index
   *
   * - Callback for the Resources sub menu item
   * - Render a simple view that lets administrators select which Level they
   *   would like to see the Resources for.
   * 
   * @access private
   */
  public function _index() {
    echo new View(tu()->get_path('/view/backend/resources/index'), array(
      'levels'     => Levels::find_all(array('post_status' => 'any')),
      '_level'     => tu()->config['levels']['single'],
      '_resources' => tu()->config['resources']['plural']
    ));
  }

  /**
   * add_crumbs
   *
   * - Fired when this Resources admin screen is active, add a crumb.
   * 
   * @access private
   */
  private function add_crumbs() {
    tu()->add_crumb($this->front_page, tu()->config['resources']['plural']);
  }

}


 
