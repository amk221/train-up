<?php

/**
 * The base admin screen for working with Results
 *
 * @package Train-Up!
 * @subpackage Results
 */

namespace TU;

class Results_admin extends Admin {

  /**
   * $front_page
   *
   * The URL/menu slug for this admin screen.
   *
   * @var string
   *
   * @access protected
   */
  protected $front_page = 'admin.php?page=tu_results';

  /**
   * __construct
   *
   * - Construct this admin screen as normal, then:
   * - Add a sub menu item
   * - when active:
   *   - Load the test and/or user that is being requested.
   *   - Load the archives for the item requested
   *   - Add the crumbs
   *   - Add the styles and scripts
   * 
   * @access public
   */
  public function __construct() {
    parent::__construct();

    add_action('admin_menu', array($this, '_add_sub_menu_item'));

    if ($this->is_active()) {
      $this->test_id      = isset($_REQUEST['tu_test_id'])      ? $_REQUEST['tu_test_id']      : null;
      $this->user_id      = isset($_REQUEST['tu_user_id'])      ? $_REQUEST['tu_user_id']      : null;
      $this->group_id     = isset($_REQUEST['tu_group_id'])     ? $_REQUEST['tu_group_id']     : null;
      $this->view         = isset($_REQUEST['tu_view'])         ? $_REQUEST['tu_view']         : null;
      $this->resit_number = isset($_REQUEST['tu_resit_number']) ? $_REQUEST['tu_resit_number'] : null;
      $this->order_by     = isset($_REQUEST['order_by'])        ? $_REQUEST['order_by']        : null;
      $this->order        = isset($_REQUEST['order'])           ? $_REQUEST['order']           : null;
      $this->flip_order   = $this->order === 'ASC'              ? 'DESC'                       : 'ASC';
      $this->download     = isset($_REQUEST['download'])        ? $_REQUEST['download']        : null;
      $this->deletions    = !empty($_REQUEST['delete_redundant_files']);

      $this->user  = Users::factory($this->user_id);
      $this->test  = Tests::factory($this->test_id);
      $this->group = Groups::factory($this->group_id);

      if ($this->user->loaded()) {
        $this->user_archives = $this->user->get_archives(array(
          'order_by'  => $this->order_by ?: 'date_submitted',
          'order'     => $this->order    ?: 'ASC'
        ));
      } else if ($this->test->loaded()) {
        $this->test_archives = $this->test->get_archives(array(
          'order_by'  => $this->order_by ?: 'percentage',
          'order'     => $this->order    ?: 'DESC',
          'group_id'  => $this->group_id
        ));
      }

      $this->add_crumbs();
      $this->handle_downloads();
      $this->handle_deletions();
      add_action('admin_enqueue_scripts', array($this, '_add_assets'));
    }
  }

  /**
   * _add_assets
   * 
   * - Fired on `admin_enqueue_scripts` when Result administration is active
   * - Enqueue the necessary styles and scripts
   * 
   * @access public
   * @static
   */
  public static function _add_assets() {
    wp_enqueue_script('tu_charts');
    wp_enqueue_script('tu_results');
    wp_enqueue_style('tu_results');
  }

  /**
   * _add_sub_menu_item
   *
   * - Fired on `admin_menu`
   * - Add a sub menu item for Results to the Train-Up! main menu item,
   * - When clicked, fire a function that automatically 'routes' the request.
   * 
   * @access private
   */
  public function _add_sub_menu_item() {
    add_submenu_page(
      'tu_plugin',
      __('Results', 'trainup'),
      __('Results', 'trainup'),
      'edit_tu_results',
      'tu_results',
      array($this, '_display_results_page')
    );
  }

  /**
   * _display_results_page
   *
   * - Callback for the Results sub menu item.
   * - Inspect the request parameters and decide what view to display.
   * 
   * @access private
   */
  public function _display_results_page() {
    if ($this->view === 'emailer') {
      $emailer = new Email_helper;
      $emailer->render();
    } else if ($this->user_id && $this->test_id) {
      $this->view_answers_for_user();
    } else if ($this->user_id) {
      $this->view_results_for_user();
    } else if ($this->test_id) {
      $this->view_results_for_test();
    } else {
      $this->index();
    }
  }

  /**
   * handle_downloads
   *
   * - It would have been nice to put this in _display_results_page, but
   *   WordPress' `add_submenu_page` is fired too late, headers are already sent
   * - Inspect the request params, and serve the appropriate download.
   * 
   * @access private
   */
  private function handle_downloads() {
    if (!$this->download) return;

    $filename = '';
    $data     = null;

    if ($this->user->loaded()) {
      $filename = simplify($this->user->display_name);
      $data     = Results::convert_archives_to_csv($this->user_archives);
    } else if ($this->test->loaded()) {
      $filename = simplify($this->test->post_title);
      $data     = Results::convert_archives_to_csv($this->test_archives);
    }

    if ($data) {
      Csv_helper::serve_download($filename, $data);
    }
  }

  /**
   * handle_deletions
   *
   * - Inspect the request params, and if the administrator has requested so,
   *   then delete all but the most recently uploaded files for a given test.
   * - This is because, as you can imagine - storing all attempted uploads
   *   for all tests is going to take up some serious space :)
   * 
   * @access private
   */
  private function handle_deletions() {
    if ($this->test->loaded() && $this->deletions) {
      $info    = $this->test->delete_redundant_files();
      $message = Tests::deletion_message($info);

      tu()->message->set_flash('success', $message);
    }
  }

  /**
   * index
   *
   * Render the default view for the Results page. It just shows a dropdown
   * that allows the administrator to choose which Test they would like to see
   * the results for.
   * 
   * @access private
   */
  private function index() {
    echo new View(tu()->get_path('/view/backend/results/index'), array(
      'levels' => Levels::find_all(array('post_status' => 'any')),
      '_test' => strtolower(tu()->config['tests']['single'])
    ));
  }

  /**
   * view_results_for_user
   *
   * - Render a user's archive (attempts at Tests)
   * - Also include a graph of the users performance over time.
   * - Bail if the current user is actually a Group manager and doesn't have
   *   access to the Trainee by way of its Groups.
   * 
   * @access private
   */
  private function view_results_for_user() {
    list($ok, $error) = tu()->user->can_access_trainee($this->user);
    $ok or wp_die($error);

    echo new View(tu()->get_path('/view/backend/results/user_archive'), array(
      '_test'       => tu()->config['tests']['single'],
      'user'        => $this->user,
      'performance' => $this->user->performance_data,
      'archives'    => $this->user_archives,
      'order_by'    => $this->order_by,
      'order'       => $this->order,
      'flip_order'  => $this->flip_order
    ));
  }

  /**
   * view_answers_for_user
   * 
   * - Render a user's answers to the requested test.
   * - Bail if the current user is actually a Group manager and doesn't have
   *   access to the Trainee by way of its Groups.
   * - Note, we manually set the ID on the User model even if they no longer
   *   actually exist, so that the model can still function (retrieve answers)
   *
   * @access private
   */
  private function view_answers_for_user() {
    list($ok, $error) = tu()->user->can_access_trainee($this->user);
    $ok or wp_die($error);

    $this->user->ID = $this->user_id;

    echo new View(tu()->get_path('/view/backend/results/user_answers'), array(
      'user_id'    => $this->user->ID,
      'users_name' => $this->user->display_name,
      'test_title' => $this->test->post_title,
      'answers'    => $this->user->archived_answers($this->test_id, $this->resit_number)
    ));
  }

  /**
   * view_results_for_test
   *
   * - Render attempts a Test, only show the latest attempt for each user
   * - Also include a graph of which Group is performing the best for that test.
   * - Bail if the current user is actually a Group manager and doesn't have
   *   access to the Test by way of its Level's Groups.
   * 
   * @access private
   */
  private function view_results_for_test() {
    list($ok, $error) = tu()->user->can_access_test($this->test);
    $ok or wp_die($error);

    echo new View(tu()->get_path('/view/backend/results/test_archive'), array(
      '_test'       => strtolower(tu()->config['tests']['single']),
      '_group'      => strtolower(tu()->config['groups']['single']),
      '_trainee'    => tu()->config['trainees']['single'],
      'performance' => $this->test->group_performance_data,      
      'colours'     => $this->test->groups_colours,
      'test'        => $this->test,
      'archives'    => $this->test_archives,
      'group'       => $this->group,
      'order_by'    => $this->order_by,
      'order'       => $this->order,
      'flip_order'  => $this->flip_order
    ));
  }

  /**
   * add_crumbs
   * 
   * - Add the root Results crumb.
   * - Add an emailer crumb if that is the view we are currently on
   * - Otherwise, add crumbs for the current user or test that we are viewing.
   *
   * @access private
   */
  private function add_crumbs() {
    tu()->add_crumb($this->front_page, __('Results', 'trainup'));

    if ($this->view === 'emailer') {
      tu()->add_crumb('', __('Send emails', 'trainup'));
    } else if ($this->user->loaded()) {
      $url  = "{$this->front_page}&tu_user_id={$this->user->ID}";
      
      tu()->add_crumb($url, $this->user->display_name);

      if ($this->test->loaded()) {
        tu()->add_crumb('', __('Answers', 'trainup'));
      }
    } else if ($this->test->loaded()) {
      tu()->add_crumb('', $this->test->post_title);
    }
  }

}


 
