<?php

/**
 * Abstract class to help with all backend administration screens
 *
 * @package Train-Up!
 */

namespace TU;

abstract class Admin {

  /**
   * $title
   *
   * The title of the admin page
   *
   * @var string
   *
   * @access protected
   */
  protected $title = '';

  /**
   * $highlight_sub_menu_item
   *
   * The slug of the menu item which should be forced to be on. We do this by
   * overriding the parent_file and submenu_file.
   *
   * @var string
   *
   * @access protected
   */
  protected $highlight_sub_menu_item = '';

  /**
   * $unhighlight_menu_item
   *
   * The slug of the main menu item to be forced off. This is a hack, to get
   * other views in the WordPress admin to appear 'underneath' the menu.
   *
   * @var string
   *
   * @access protected
   */
  protected $unhighlight_menu_item = '';

  /**
   * $front_page
   *
   * Just a helper var, to keep a record of what the entry point to this admin
   * class is.
   *
   * @var string
   *
   * @access protected
   */
  protected $front_page = '';

  /**
   * __construct
   *
   * When an admin class is instantiated, only ever add actions if this class
   * is actually active.
   * 
   * @access public
   */
  public function __construct() {
    if ($this->is_active()) {
      add_action('admin_enqueue_scripts', array($this, '_add_common_assets'));
      add_action('parent_file', array($this, '_unhighlight_menu_item'), 1000);
      add_action('parent_file', array($this, '_highlight_sub_menu_item'), 1000);
      add_action('parent_file', array($this, '_override_title'), 1000);
    }
  }

  /**
   * is_active
   *
   * By default (for the abstract class), an admin class is considered active
   * if the user is on the front page that this class is for. Other admin
   * classes that extend this one, might have different conditions.
   * 
   * @access public
   *
   * @return string
   */
  public function is_active() {
    return $this->is_on_front_page();
  }

  /**
   * is_on_front_page
   *
   * Determine this if the request URI contains this class' front page slug.
   * (i.e. ignore any potential query string)
   * 
   * @access public
   *
   * @return boolean Whether the user is on the front page for this admin class
   */
  public function is_on_front_page() {
    $front_page = preg_quote($this->front_page);
    return preg_match("/^{$front_page}/i", basename($_SERVER['REQUEST_URI']));
  }
  
  /**
   * _highlight_sub_menu_item
   *
   * - Fired as late as possible for the `parent_file` filter
   * - If a highlight sub menu item has been specified, then this admin class
   *   has requested a sub menu item should be forced as on. We require this
   *   because WordPress' API is inflexible.
   * - Override the parent_file to be that of the plugin's main menu item.
   * - Override the submenu_file to be that of the sub menu item requested.
   * 
   * @param string $parent_file 
   *
   * @access private
   *
   * @return string
   */
  public function _highlight_sub_menu_item($parent_file) {
    global $self, $submenu_file;

    if (empty($this->highlight_sub_menu_item)) return;

    $self         = 'tu_plugin';
    $parent_file  = $self;
    $submenu_file = $this->highlight_sub_menu_item;

    return $parent_file;
  }

  /**
   * _unhighlight_menu_item
   *
   * - Fired as late as possible for `parent_file` filter
   * - If a menu item as been requested to be unhighlighted, then be cheeky and
   *   manipulate that menu item's slug by appending a question mark.
   * - This means WordPress can no longer match that string (because it's
   *   different) and so gets unhiglighted, but remains a valid URL.
   * 
   * @param string $parent_file 
   *
   * @access private
   *
   * @return string
   */
  public function _unhighlight_menu_item($parent_file) {
    global $menu, $parent_file;
    
    if (empty($this->unhighlight_menu_item)) return;

    foreach ($menu as $i => $item) {
      if ($item[2] == $this->unhighlight_menu_item) {
        $menu[$i][2] .= '?';
        break;
      }
    }

    return $parent_file;
  }

  /**
   * _override_title
   *
   * - Fired as late as possible for the `parent_file` filter
   * - Override the page title if one has been set by this admin class,
   *   otherwise, just use the default.
   * 
   * @param string $parent_file
   *
   * @access private
   *
   * @return string
   */
  public function _override_title($parent_file) {
    global $title;

    if (!empty($this->title)) {
      $title = $this->title;
    }

    return $parent_file;
  }

  /**
   * _add_common_assets
   *
   * Scripts and styles to be included whenever the plugin (any of its
   * admins screens) are active.
   * 
   * @access private
   */
  public function _add_common_assets() {
    wp_enqueue_script('tu_backend');
    wp_enqueue_style('tu_backend');
    wp_enqueue_script('tu_emailer');
  }

}


 
