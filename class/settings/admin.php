<?php

/**
 * The admin screen for working with Settings
 *
 * Important note: The use of serialise in the Settings_admin class is because
 * if sessions have already been started by *another plugin*, then classes will
 * not be unserialiseable because they will be incomplete. So forcing the 
 * serialisation of them is a quick fix, rather than writing a class autoloader.
 *
 * @package Train-Up!
 * @subpackage Settings
 */

namespace TU;

class Settings_admin extends Admin {

  /**
   * $front_page
   *
   * The slug of the sub menu item for the Settings page
   *
   * @var string
   *
   * @access protected
   */
  protected $front_page = 'admin.php?page=tu_settings';

  /**
   * __construct
   *
   * - Construct the admin section as normal
   * - Then, add a sub menu item and register the sections and settings
   * - If active, add the breadcrumbs and some actions to make it all work.
   * - If WordPress has specified that the settings have been updated (by
   *   the presence of `settings-updated` request param, then fire a function
   *   to finalise stuff)
   * 
   * @access public
   */
  public function __construct() {
    parent::__construct();

    add_action('admin_menu', array($this, '_add_sub_menu_item'));
    add_action('admin_init', array($this, '_add_sections_and_settings'));

    if ($this->is_active()) {  
      $this->add_crumbs();

      add_action('admin_enqueue_scripts', array($this, '_add_assets'));
      add_action('admin_head', array($this, '_set_validator'));
      add_action('admin_footer', array($this, '_unset_validator'));
      
      if (isset($_GET['settings-updated'])) {
        $this->after_save();
      }
    }
  }

  /**
   * _add_sub_menu_item
   *
   * - Fired on `admin_menu`
   * - Add a sub menu item for Settings to the Train-Up! main menu item,
   * - When clicked, go to the 'index' page
   * 
   * @access private
   */
  public function _add_sub_menu_item() {
    add_submenu_page(
      'tu_plugin',
      __('Settings', 'trainup'),
      __('Settings', 'trainup'),
      'tu_settings',
      'tu_settings',
      array($this, '_index')
    );
  }

  /**
   * _add_assets
   *
   * - Fired on `admin_enqueue_scripts` when the Settings administration pages
   *   are active.
   * - Enqueue the necessary styles and scripts
   * 
   * @access private
   */
  public function _add_assets() {
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');  
    wp_enqueue_style('tu_settings');
    wp_enqueue_script('tu_settings');
    wp_enqueue_style('tu_image_browser');
    wp_enqueue_script('tu_image_browser');
    wp_enqueue_style('tu_grades');
    wp_enqueue_script('tu_grades');
  }

  /**
   * get_active_section
   * 
   * @access private
   *
   * @return string The slug of the settings section currently being requested,
   * default to 'general'.
   */
  private function get_active_section() {
    return isset($_REQUEST['section']) ? $_REQUEST['section'] : 'general';
  }

  /**
   * get_active_section_config
   * 
   * @access private
   *
   * @return string The settings configuration for the specified section. Default
   * to the general settings.
   */
  private function get_active_section_config() {
    $structure = Settings::get_structure();

    return $structure[$this->get_active_section()];
  }

  /**
   * _index
   *
   * - Callback for the Settings sub menu item
   * - Echo out the settings page, passing in the ID of the active section
   *   i.e. the type of settings we want to render, default to 'general'.
   * 
   * @access private
   */
  public function _index() {
    $active_section_slug = $this->get_active_section();
    $active_section_id   = "tu_settings_{$active_section_slug}";

    echo new View(tu()->get_path('/view/backend/page/settings'), array(
      'plugin_name'         => tu()->get_name(),
      'active_section_slug' => $active_section_slug,
      'active_section_id'   => $active_section_id,
      'structure'           => Settings::get_structure(),
      'config'              => tu()->config
    ));
  }

  /**
   * add_crumbs
   *
   * - Fired when the Settings class is active
   * - Add a root crumb for 'Settings'
   * - If a sub section being requested isn't the 'general' settings, then
   *   add an extra crumb.
   * 
   * @access private
   */
  private function add_crumbs() {
    tu()->add_crumb($this->front_page, __('Settings', 'trainup'));

    if ($this->get_active_section() !== 'general') {
      $active_section = $this->get_active_section_config();
      tu()->add_crumb('', $active_section['title']);
    }
  }

  /**
   * _add_sections_and_settings
   *
   * - Fired on `admin_init`
   * - Inform WordPress of each of the major setting-sections, and then
   *   also inform it of each of the settings within that section.
   * - Whilst doing this, automatically add a field for each setting with a
   *   magic callback function that chooses an appropriate HTML input depending
   *   on the type of setting field.
   * 
   * @access private
   */
  public function _add_sections_and_settings() {
    foreach (Settings::get_structure() as $section_slug => $section) {
      $section_id = "tu_settings_{$section_slug}";

      add_settings_section($section_id, '', '__return_false', $section_id);

      foreach ($section['settings'] as $setting_slug => $setting) {
        $setting['name']  = "{$section_id}[{$setting_slug}]";
        $setting['value'] = tu()->config[$section_slug][$setting_slug];
        $setting['slug']  = $setting_slug;

        add_settings_field(
          $setting_slug,
          $setting['title'],
          array($this, '_render_field'),
          $section_id,
          $section_id,
          $setting
        );

        register_setting($section_id, $section_id, array($this, '_validate_fields'));
      }
    }
  }

  /**
   * _render_field
   *
   * - Fired when a setting field is to be rendered
   * - Load the validator from a potential previous form submission, send it to
   *   the view to render any errors from that attempt.
   * - Render the view automatically based on the setting input-type.
   * - If this setting has its own callback rather than relying on the automatic
   *   view rendering, then fire that too.
   * 
   * @param array $setting
   *
   * @access private
   */
  public function _render_field($setting) {
    $setting['validator'] = unserialize($_SESSION['tu_settings_validator']);

    if (isset($setting['validator']->errors[$setting['slug']])) {
      $setting['value'] = $setting['validator']->form[$setting['slug']];
    }
    if (isset($setting['type'])) {
      echo new View(tu()->get_path("/view/backend/forms/{$setting['type']}"), $setting);
    }
    if (isset($setting['callback'])) {
      $this->$setting['callback']($setting);
    }
  }

  /**
   * render_grade_selector
   *
   * - Callback for when a the tests -> grades setting field is rendered
   * - Render a custom view
   * 
   * @param array $setting
   *
   * @access private
   */
  private function render_grade_selector($setting) {
    if (isset(tu()->config['tests']['grades'])) {
      $grades = tu()->config['tests']['grades'];
    } else {
      $grades = $setting['default'];
    }

    echo new View(tu()->get_path("/view/backend/forms/grade_selector"), array(
      'grades'    => $grades,
      'small'     => false,
      'disabled'  => false
    ));
  }

  /**
   * after_save
   *
   * Fired after the administrator has submitted the form, thereby saving
   * their settings. Because they have the ability to alter the single and
   * plural names of concepts within the plugin, and they translate directly to
   * post types, we need to flush the post-type cache so that each one is
   * refreshed on the next page load.
   * 
   * @access private
   */
  private function after_save() {
    Post_type::refresh_cache();
  }

  /**
   * _validate_fields
   *
   * - Fired when a setting section is validated
   * - Get the section slug from the post parameter
   * - Then load the settings for the section that is being validated
   * - Go through each of the settings in that section, and see if they have
   *   specified any validation rules, if so add them to the rules array.
   * - If they all validate, return the data which will save the settings.
   *   Otherwise, add a single error using WordPress settings API, but rely
   *   instead on the validator to provide more in depth errors next to the
   *   field they relate to. Unfortunately we have to store the validator in
   *   the session because it is the only way to maintain the state.
   * 
   * @param array $data
   *
   * @access private
   */
  public function _validate_fields($data) {
    preg_match('/^tu_settings_(\w+)/', $_POST['option_page'], $matches);

    $section_slug = $matches[1];
    $structure    = Settings::get_structure();
    $settings     = $structure[$section_slug]['settings'];
    $validator    = new Validator($data);
    $rules        = array();

    foreach ($settings as $setting_slug => $config) {
      if (isset($config['validation'])) {
        $rules[$setting_slug] = $config['validation'];
      }
    }

    if ($validator->validate($rules)) {
      return $data;
    } else {
      add_settings_error(
        $section_slug,
        '',
        __('Invalid settings', 'trainup'), 'error'
      );

      $_SESSION['tu_settings_validator'] = serialize($validator);      

      return tu()->config[$section_slug];
    }
  }

  /**
   * _set_validator
   *
   * - Fired on `admin_head`
   * - Make sure there is always a validator instance available to pass to the
   *   settings page.
   * 
   * @access private
   */
  public function _set_validator() {
    if (!isset($_SESSION['tu_settings_validator'])) {
      $_SESSION['tu_settings_validator'] = serialize(new Validator);
    }
  }

  /**
   * _unset_validator
   *
   * - Fired on `admin_footer`
   * - Reset the validator so the errors from the previous form submission
   *   are not shown more than once.
   * 
   * @access private
   */
  public function _unset_validator() {
    $_SESSION['tu_settings_validator'] = serialize(new Validator);
  }

}


 
