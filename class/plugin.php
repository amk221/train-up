<?php

/**
 * @package Train-Up!
 */

namespace TU;

class Plugin {

  /**
   * $instance
   *
   * The singleton that is Train-Up!
   *
   * @var Plugin
   *
   * @access private
   * @static
   */
  private static $instance = null;

  /**
   * $config
   *
   * Contains the active configuration for the plugin
   *
   * @var array
   *
   * @access public
   */
  public $config = array();

  /**
   * $version
   *
   * The version of Train-Up! automatically loaded, read-in from the main
   * plugin file - index.php
   *
   * @var integer|string
   *
   * @access public
   */
  public $version = null;

  /**
   * $name
   *
   * Name of the plugin itself
   *
   * @var string
   *
   * @access private
   */
  private $name = 'Train-Up!';

  /**
   * $homepage
   *
   * URL of the plugin
   *
   * @var string
   *
   * @access private
   */
  private $homepage = 'http://github.com/amk221/train-up';

  /**
   * $breadcrumb_trail
   *
   * Items used to build the admin breadcrumb trail
   *
   * @var array
   *
   * @access private
   */
  private $breadcrumb_trail = array();

  /**
   * get_name
   *
   * @access public
   *
   * @return string The name of the plugin
   */
  public function get_name() {
    return $this->name;
  }

  /**
   * set_name
   *
   * Allow customisation of the plugin's name, useful for white-labelling
   *
   * @param string $name
   *
   * @access public
   */
  public function set_name($name) {
    $this->name = $name;
  }

  /**
   * get_slug
   *
   * The slug is the folder name of the plugin plus the main plugin file,
   * It is used to uniquely identify this plugin (when upgrading etc..).
   * It is computed automatically because the folder could be renamed by hand.
   * But usually it is 'train-up/index.php'
   *
   * @access public
   *
   * @return string
   */
  public function get_slug() {
    return plugin_basename($this->plugin_file);
  }

  /**
   * get_homepage
   *
   * @access public
   *
   * @return string The url of the plugin
   */
  public function get_homepage() {
    return $this->homepage;
  }

  /**
   * get_url
   *
   * @param string $path An optional further path to append to the url
   *
   * @access public
   *
   * @return string A url for this plugin
   */
  public function get_url($path = '') {
    return plugins_url() .'/'. basename($this->get_path()) . $path;
  }

  /**
   * get_path
   *
   * @param string $path An optional further path to append to the path
   *
   * @access public
   *
   * @return string A path for this plugin
   */
  public function get_path($path = '') {
    return realpath(dirname(__FILE__).'/../') . $path;
  }

  /**
   * get_nice_url
   *
   * Helps generate a URL to a user facing page. Unlike get_url(), which returns
   * a URL to a plugin asset.
   *
   * @param string $path
   *
   * @access public
   *
   * @return string
   */
  public function get_nice_url($path = '') {
    $domain = preg_replace('/^www\./', '', strtolower($_SERVER['SERVER_NAME']));
    $slug   = tu()->config['general']['main_slug'];
    return "{$domain}/{$slug}{$path}";
  }

  /**
   * in_frontend
   *
   * @access public
   *
   * @return boolean Whether or not the front end of this plugin is active,
   * i.e. we're going to be rendering a Level, Resource, Test, Question, Result.
   */
  public function in_frontend() {
    return !is_admin() && isset(tu()->post);
  }

  /**
   * __construct
   *
   * Prevent this class from being instantiated
   *
   * @access private
   */
  private function __construct() {}

  /**
   * instance
   *
   * Create an instance of this plugin unless one exists already, in which case
   * return that one. This is the main entry point for developers to the plugin,
   * a shortcut function tu() is the desired way to access it.
   *
   * @see dependencies.php
   * @access public
   * @static
   *
   * @return object
   */
  public static function instance() {
    if (is_null(self::$instance)) {
      self::$instance = new Plugin;
    }

    return self::$instance;
  }

  /**
   * start
   *
   * This is what kicks everything off, the order goes something like this:
   *
   * - Bail if this plugin has already been started, we don't want to bind
   *   all these events more than once. True, this could have gone in the
   *   __construct(), but if we keep it in a separate function we can refer to
   *   tu(), because the instance already exists then.
   * - The root crumb is added.
   * - The actual WordPress plugin file path is determined.
   * - A few helpers are instantiated
   * - The standard and dynamic post types are registered
   * - An admin area is created for each of the post types
   * - Model accessors are created for the front and backend
   * - The main menu item is added
   * - The sub menu items are ordered to our liking
   * - All assets are registered for later use
   * - The breadcrumb trail is rendered at the top of the page
   * - Add some internal filtering
   * - The de/activate hooks are registered
   * - The plugin uses sessions, so one is started if not already
   * - Register the dashboard widget
   * - Finally, send some headers with info about Train-Up!
   *
   * @access public
   */
  public function start() {
    $n = __NAMESPACE__.'\\';

    if (property_exists($this, 'plugin_file')) {
      throw new \Exception(__('Plugin already started.', 'trainup'));
    }

    $this->plugin_file = $this->get_path('/index.php');

    add_action('init', array($this, '_rewrite_rules'));
    add_action('init', array($this, '_register_post_types'));
    add_action('init', array($this, '_create_user_accessor'));
    add_action('init', array($this, '_create_admin_areas'));
    add_action('init', array($this, '_load_plugin_version'));
    add_action('init', array($this, '_runcom'));
    add_action('admin_title', array($this, '_create_backend_post_accessor'));
    add_action('wp', array($this, '_create_frontend_post_accessor'));
    add_action('admin_menu', array($this, '_add_menu_item'));
    add_action('admin_menu', array($this, '_order_sub_menu_items'), 100);
    add_action('admin_menu', array($this, '_remove_redundant_menu_items'));
    add_action('adminmenu', array($this, '_remove_redundant_sub_menu_items'), 100);
    add_action('wp_enqueue_scripts', array($this, '_register_assets'));
    add_action('admin_enqueue_scripts', array($this, '_register_assets'));
    add_action('admin_enqueue_scripts', array($this, '_add_global_assets'));
    add_action('all_admin_notices', array($this, '_render_admin_breadcrumb_trail'), 20);
    add_action('admin_bar_menu', array($this, '_edit_post_buttons'), 100);
    add_filter('admin_body_class', array($this, '_stylable_user_types'));
    add_action('plugins_loaded', array($this, '_localise'));
    add_filter('plugin_action_links_'.plugin_basename($this->plugin_file), array($this, '_plugin_links'));
    add_action('send_headers', array($this, '_send_headers'));

    add_filter('tu_pre_get_levels', "{$n}Levels::_filter");
    add_filter('tu_pre_get_groups', "{$n}Groups::_filter");
    add_filter('tu_pre_get_results', "{$n}Results::_filter");
    add_filter('tu_pre_get_tests', "{$n}Tests::_filter");
    add_filter('tu_pre_get_trainees', "{$n}Trainees::_filter");

    register_activation_hook($this->plugin_file, array($this, '_activate'));
    register_deactivation_hook($this->plugin_file,  array($this, '_deactivate'));

    if (!session_id()) {
      session_start();
    }

    $this->post_types     = Post_type::find_cached();
    $this->config         = Settings::get_config();
    $this->ajax_helper    = new Ajax_helper;
    $this->roles_helper   = new Roles_helper;
    $this->theme_helper   = new Theme_helper;
    $this->comment_helper = new Comments_helper;
    $this->message        = new Message_helper;
    $this->tin_can        = new Tin_can_helper;
    $this->dashboard      = new Dashboard_widgets;
    $this->upgrader       = new Upgrade_helper;

    $this->add_crumb('#', $this->get_name());


  }

  /**
   * _activate
   *
   * - Fired when the plugin is activated.
   * - The plugin is installed unless it has been installed already
   * - It is then marked as activated.
   *
   * @access private
   */
  public function _activate() {
    if (!get_option('tu_installed')) {
      new Installer;
    }
    update_option('tu_activated', true);
  }

  /**
   * _deactivate
   *
   * - Fired when the plugin is deactivated
   * - Just mark it as de-activated
   *
   * @access private
   */
  public function _deactivate() {
    update_option('tu_activated', false);
  }

  /**
   * _register_post_types
   *
   * Loop through the cached post types and register them with WordPress.
   * Also, if our plugin has scheduled rewrite rules for flushing,
   * then flush them.
   *
   * @access private
   */
  public function _register_post_types() {
    foreach ($this->post_types as $post_type) {
      $post_type->register();
    }

    if (get_option('tu_flush_rewrite_rules')) {
      Post_type::flush();
    }
  }

  /**
   * _create_admin_areas
   *
   * - Creates an admin area for each post-type.
   *   Afterwards, unset the post_types because their job is done.
   * - Bail if the user cannot access the backend of our plugin, because
   *   they can still attempt to access the WordPress backend, which in turn
   *   would try to create the admin areas.
   *
   * @access private
   */
  public function _create_admin_areas() {
    if (!current_user_can('tu_backend')) return;

    // Post type admin areas
    new Level_admin;
    new Test_admin;
    new Group_admin;
    new Page_admin;

    // Dynamic post type admin areas
    new Resources_admin;
    new Questions_admin;
    new Results_admin;

    foreach ($this->post_types as $post_type) {
      if ($post_type->is_dynamic) {
        $admin = $post_type->admin_handler;
        new $admin($post_type);
      }
    }

    // User admin areas
    new Trainee_user_admin;
    new Group_manager_user_admin;
    new Administrator_user_admin;

    // Miscellaneous admin areas
    new Settings_admin;
    new Emailer_admin;
    new Debug_admin;

    unset($this->post_types);
  }

  /**
   * _edit_post_buttons
   *
   * - Fired on `admin_bar_menu`
   * - Check if the current post is a Train-Up! one, and if so manually add
   *   an 'Edit post' link to the admin bar.
   * - This is instead of relying on `show_in_admin_bar` post type option
   *   because we have so many post types, it would get messy.
   *
   * @param object $admin_bar
   *
   * @access private
   */
  public function _edit_post_buttons($admin_bar) {
    global $post;

    if (is_admin() || !isset(tu()->post) || !tu()->user->is_administrator()) {
      return;
    }

    $post_type = get_post_type_object($post->post_type);

    $admin_bar->add_menu(array(
      'id'    => 'tu-edit-post-link',
      'title' => $post_type->labels->edit_item,
      'href'  => get_edit_post_link($post->ID)
    ));
  }

  /**
   * _remove_redundant_menu_items
   *
   * - Fired on `admin_menu`
   * - If the user is a Group Manager, remove the Tools, Posts and User items
   *   because although they have access to these - they are of no use to them.
   *
   * @access private
   */
  public function _remove_redundant_menu_items() {
    if (tu()->user->is_group_manager()) {
      remove_menu_page('tools.php');
      remove_menu_page('edit.php');
      remove_menu_page('users.php');
    }
  }

  /**
   * _order_sub_menu_items
   *
   * - Fired very late on `admin_menu`
   * - Hack to order the sub menu how we want it.
   * - Bail if the use cannot access the backend, because the menu would not
   *   have been created so we wouldn't be able to re order it.
   * - Notice the @ used supress a bug in which certain versions of PHP don't
   *   seem to like the anon function.
   *
   * @access private
   */
  public function _order_sub_menu_items() {
    global $submenu;

    if (!current_user_can('tu_backend')) return;

    $order = array(
      'edit_tu_levels'    => 0,
      'edit_tu_resources' => 1,
      'edit_tu_tests'     => 2,
      'edit_tu_results'   => 3,
      'tu_trainees'       => 4,
      'edit_tu_groups'    => 5,
      'tu_group_managers' => 6,
      'edit_tu_pages'     => 7,
      'tu_settings'       => 8,
      'tu_emailer'        => 9,
      'tu_debugger'       => 10
    );

    $sort = function($a, $b) use ($order) {
      if (isset($order[$a[1]]) && isset($order[$b[1]])) {
        return $order[$a[1]] > $order[$b[1]];
      }
    };

    @usort($submenu['tu_plugin'], $sort);
  }

  /**
   * _remove_redundant_sub_menu_items
   *
   * - Fired on 'adminmenu' just after the admin menu is output on the page
   * - Remove links to Train-Up! pages that don't require a direct link in
   *   the sub menu.
   *
   * @access private
   */
  public function _remove_redundant_sub_menu_items() {
    echo '
      <script>
        jQuery("#adminmenu a[href$=tu_emailer]").parent().remove();
      </script>
    ';
  }

  /**
   * _add_menu_item
   *
   * - Add the plugin's main menu item.
   * - Note, the float val is so the plugin position is less likely to clash
   *   with other plugins
   *
   * @see http://codex.wordpress.org/Function_Reference/add_menu_page
   * @access private
   */
  public function _add_menu_item() {
    add_menu_page(
      $this->get_name(),
      $this->get_name(),
      'tu_backend',
      'tu_plugin',
      null,
      $this->get_url('/img/@2x/icon_32.png'),
      '100.61803398874989484820'
    );
  }

  /**
   * _rewrite_rules
   *
   * - Fired on `init`
   * - Add some general rewrite rules.
   * - Add a rule that is the 'root' rule, that takes the user from /training
   *   to their account page (if not logged in they will be taken to the login)
   *
   * @access private
   */
  public function _rewrite_rules() {
    $root = sanitize_title_with_dashes(tu()->config['general']['main_slug']);
    $my_account = 'index.php?p='. Pages::factory('My_account')->ID;

    add_rewrite_rule("^{$root}$", $my_account, 'top');
  }

  /**
   * _load_plugin_version
   *
   * - Fired on `init`
   * - This is instead of using get_plugin_data, because that is only available
   *   when in the backend, and also it is only available too late, like lots
   *   of WordPress functions annoyingly.
   * - Pull in the version of this plugin from the main plugin file, we need
   *   this so WordPress can check if Train-Up! is out of date or not.
   * - Cache the value so that we don't have to parse the file when in
   *   the frontend.
   *
   * @access private
   */
  public function _load_plugin_version() {
    if (is_admin()) {
      $info = get_file_data($this->plugin_file, array('Version' => 'Version'));
      $this->version = $info['Version'];
      update_option('tu_version', $this->version);
    } else {
      $this->version = get_option('tu_version');
    }
  }

  /**
   * _runcom
   *
   * - Fired on `init`
   * - Parse the .trainuprc file
   * - Fire an action with the configuration payload used for different builds.
   *
   * @access private
   */
  public function _runcom() {
    $rc = $this->get_path('/.trainuprc');

    if (file_exists($rc)) {
      do_action('tu_rc', json_decode(file_get_contents($rc)));
    }
  }

  /**
   * _send_headers
   *
   * - Fired on `send_headers` when WordPress is outputting HTTP headers
   * - Add a custom header so we can detect which sites ar using Train-Up!
   *
   * @access private
   */
  public function _send_headers() {
    header('X-Train-Up-Version: ' . $this->version);
  }

  /**
   * _stylable_user_types
   *
   * - Fired on `admin_body_class`
   * - Append the user role to the class name so we can target it using CSS.
   *
   * @param string $classes
   *
   * @access private
   *
   * @return string The altered classes
   */
  public function _stylable_user_types($classes) {
    $classes .= ' is-' . tu()->user->role.' ';

    return $classes;
  }

  /**
   * _localise
   *
   * - Fired on `plugins_loaded`
   * - Tell WordPress which domain the plugin uses to tag its localisable string
   *   strings.
   *
   * @access private
   */
  public function _localise() {
    load_plugin_textdomain('trainup', false, dirname($this->get_slug()) . '/lang/');
  }

  /**
   * add_crumb
   *
   * Pushes a hash of crumb information to the trail of crumbs.
   *
   * @param string $url   The crumb destination
   * @param string $title The crumb title
   *
   * @access public
   */
  public function add_crumb($url, $title) {
    $this->breadcrumb_trail[] = array(
      'url'   => $url,
      'title' => $title
    );
  }

  /**
   * _render_admin_breadcrumb_trail
   *
   * Fired on `all_admin_notices`, a suitable place (i.e. the top of the page)
   * to render the breadcrumb trail.
   *
   * @access private
   */
  public function _render_admin_breadcrumb_trail() {
    echo new View($this->get_path('/view/backend/misc/breadcrumb_trail'), array(
      'crumbs' => $this->breadcrumb_trail
    ));
  }

  /**
   * _plugin_links
   *
   * - Fired on `plugin_action_links_`
   * - Adds a hyperlink to the plugin's Settings page.
   * - Removes the link to edit the plugin, why is this even a thing?
   *
   * @param array $links
   *
   * @access private
   *
   * @return array The altered links
   */
  public function _plugin_links($links) {
    $href = 'admin.php?page=tu_settings';
    $text = __('Settings', 'trainup');
    $links['settings'] = "<a href='{$href}'>{$text}</a>";
    unset($links['edit']);

    return $links;
  }

  /**
   * _create_frontend_post_accessor
   *
   * Fired on `wp`, create the global post accessor for Train-Up!
   * (The earliest opportunity the global post is available in the frontend).
   *
   * @access private
   */
  public function _create_frontend_post_accessor() {
    global $post;

    if (!is_admin()) {
      register_global_post($post);
    }
  }

  /**
   * _create_backend_post_accessor
   *
   * Fired on `admin_title`, create the global post accessor for Train-Up!
   * (The *earliest* opportunity the global post is available in the backend).
   *
   * @see register_global_post
   * @access private
   */
  public function _create_backend_post_accessor($title) {
    global $post;

    register_global_post($post);

    return $title;
  }

  /**
   * _create_user_accessor
   *
   * Fired on `init`, always create the global user accessor for Train-Up!
   *
   * @see register_global_user
   * @access private
   */
  public function _create_user_accessor() {
    register_global_user(wp_get_current_user());
  }

  /**
   * _add_global_assets
   *
   * Enqueue some styles and scripts that are active even when not 'within'
   * the plugin itself
   *
   * @access private
   */
  public function _add_global_assets() {
    wp_enqueue_style('tu_global', tu()->get_url('/css/backend/global.css'));
  }

  /**
   * _register_assets
   *
   * - Fired on `wp_enqueue_scripts` and `admin_enqueue_scripts`
   * - Tell WordPress about all the assets the plugin may use.
   * - Also localise the scripts.
   *
   * @access private
   */
  public function _register_assets() {
    wp_register_style('tu_frontend', tu()->get_url('/css/frontend/train_up.css'));
    wp_register_script('tu_frontend', tu()->get_url('/js/frontend/train_up.js'), array('jquery'));
    wp_localize_script('tu_frontend', 'TU', Theme_helper::localised_js());
    wp_register_script('tu_charts', 'https://www.google.com/jsapi');
    wp_register_style('tu_autocompleter', tu()->get_url('/css/autocompleter.css'));
    wp_register_script('tu_autocompleter', tu()->get_url('/js/autocompleter.js'), array('jquery-ui-autocomplete'));
    wp_register_style('tu_image_browser', tu()->get_url('/css/backend/image_browser.css'));
    wp_register_script('tu_image_browser', tu()->get_url('/js/backend/image_browser.js'));
    wp_register_style('tu_grades', tu()->get_url('/css/backend/grades.css'));
    wp_register_script('tu_grades', tu()->get_url('/js/backend/grades.js'));
    wp_localize_script('tu_grades', 'TU_GRADES', Tests::localised_grades_js());
    wp_register_style('tu_emailer', tu()->get_url('/css/backend/emailer.css'));
    wp_register_script('tu_emailer', tu()->get_url('/js/backend/emailer.js'), array('tu_autocompleter'));
    wp_localize_script('tu_emailer', 'TU_EMAILER', Emailer::localised_js());
    wp_register_style('tu_importer', tu()->get_url('/css/backend/importer.css'));
    wp_register_script('tu_importer', tu()->get_url('/js/backend/importer.js'), array('jquery-ui-dialog'));
    wp_localize_script('tu_importer', 'TU_IMPORTER', Importer::localised_js());
    wp_register_script('tu_backend',tu()->get_url('/js/backend/admin.js'));
    wp_localize_script('tu_backend', 'TU', Plugin::localised_js());
    wp_register_style('tu_backend', tu()->get_url('/css/backend/admin.css'));
    wp_register_script('tu_groups', tu()->get_url('/js/backend/groups.js'), array('tu_autocompleter', 'wp-color-picker'));
    wp_register_style('tu_groups', tu()->get_url('/css/backend/groups.css'), array('wp-color-picker'));
    wp_register_style('tu_levels', tu()->get_url('/css/backend/levels.css'));
    wp_register_script('tu_levels', tu()->get_url('/js/backend/levels.js'));
    wp_localize_script('tu_levels', 'TU_LEVELS', Levels::localised_js());
    wp_register_style('tu_questions', tu()->get_url('/css/backend/questions.css'));
    wp_register_script('tu_questions', tu()->get_url('/js/backend/questions.js'));
    wp_localize_script('tu_questions', 'TU_QUESTIONS', Questions::localised_js());
    wp_register_script('tu_frontend_questions', tu()->get_url('/js/frontend/questions.js'));
    wp_localize_script('tu_frontend_questions', 'TU_QUESTIONS', Questions::localised_frontend_js());
    wp_register_style('tu_resources', tu()->get_url('/css/backend/resources.css'));
    wp_register_script('tu_resources', tu()->get_url('/js/backend/resources.js'));
    wp_localize_script('tu_resources', 'TU_RESOURCES', Resources::localised_js());
    wp_register_style('tu_results', tu()->get_url('/css/backend/results.css'));
    wp_register_script('tu_results', tu()->get_url('/js/backend/results.js'));
    wp_localize_script('tu_results', 'TU_RESULTS', Results::localised_js());
    wp_register_style('tu_settings', tu()->get_url('/css/backend/settings.css'));
    wp_register_script('tu_settings', tu()->get_url('/js/backend/settings.js'));
    wp_register_style('tu_tests', tu()->get_url('/css/backend/tests.css'));
    wp_register_script('tu_tests', tu()->get_url('/js/backend/tests.js'));
    wp_localize_script('tu_tests', 'TU_TESTS', Tests::localised_js());
    wp_register_script('tu_users', tu()->get_url('/js/backend/users.js'));
    wp_localize_script('tu_users', 'TU_USERS', Users::localised_js());
    wp_register_script('tu_frontend_tests', tu()->get_url('/js/frontend/tests.js'));
    wp_localize_script('tu_frontend_tests', 'TU_TESTS', Tests::localised_frontend_js());
    wp_register_script('tu_frontend_kbd_shortcuts', tu()->get_url('/js/frontend/keyboard_shortcuts.js'));
  }

  /**
   * localised_js
   *
   * @access private
   * @static
   *
   * @return array Localised strings for use in the backend.
   */
  private static function localised_js() {
    global $post;

    return array(
      'ajaxUrl'      => admin_url('admin-ajax.php'),
      'activePostId' => isset($post) ? $post->ID : null
    );
  }

}
