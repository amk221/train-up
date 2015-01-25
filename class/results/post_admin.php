<?php

/**
 * The admin screen for working with Results
 *
 * @package Train-Up!
 * @subpackage Results
 */

namespace TU;

class Result_admin extends Post_admin {

  /**
   * $editable_permalinks
   *
   * Turn off the ability for users to edit the permalink of a Level
   *
   * @var mixed
   *
   * @access protected
   */
  protected $editable_permalinks = false;

  /**
   * __construct
   *
   * - Accept a post type, set up the admin area as usual
   * - When active, process any bulk actions
   * - Force highlighting of the Results sub menu item
   * - Position meta boxes underneath the title
   * - Enqueue the scripts and styles that this admin screen needs.
   * - If viewing the list of Results, apply the filters
   * - Append an extra row to the archived answers to let admins give a response
   *
   * @param object $post_type
   *
   * @access public
   */
  public function __construct($post_type) {
    parent::__construct($post_type);

    if ($this->is_active()) {
      $this->process_bulk_actions();
      $this->highlight_sub_menu_item = 'tu_results';

      add_action('admin_head', array($this, '_check'));
      add_action('edit_form_after_title', array($this, '_reposition_advanced_meta_boxes'));
      add_action('admin_enqueue_scripts', array(__NAMESPACE__.'\\Results_admin', '_add_assets'));
      add_action('tu_archived_answer_row', array($this, '_add_answers_meta_fields'));

      if ($this->is_browsing()) {
        add_action('pre_get_posts', array(__NAMESPACE__.'\\Results', '_filter'));
      }
    }
  }

  /**
   * _check
   *
   * - Fired on `admin_head` when we will have access to the global result.
   * - Deny adding new Results (they can only be created by Train-Up!)
   * - Deny access to editing a result if the user doesn't have access to it
   *   via the Groups that they are in.
   *
   * @access private
   */
  public function _check() {
    $editing = $this->is_editing();
    $adding  = $this->is_adding();

    if ($adding) {
      wp_die(sprintf(
        __('You cannot manually add %1$s results', 'trainup'),
        strtolower(tu()->config['tests']['single'])
      ));
    } else if ($editing) {
      list($ok, $error) = tu()->user->can_access_result(tu()->result);
      $ok or wp_die($error);
    }
  }

  /**
   * get_meta_boxes
   *
   * @access protected
   *
   * @return array Hash of meta boxes to show when dealing with Results.
   */
  protected function get_meta_boxes() {
    $meta_boxes = array(
      'shortcodes' => array(
        'title'    => __('Shortcodes', 'trainup'),
        'context'  => 'side',
        'priority' => 'high',
        'closed'   => true
      ),
      'results' => array(
        'title'    => __('Results', 'trainup'),
        'context'  => 'advanced',
        'priority' => 'high'
      )
    );

    if ($this->is_editing()) {
      $meta_boxes['relationships'] = array(
        'title'    => __('Relationships', 'trainup'),
        'context'  => 'side',
        'priority' => 'default'
      );
      $meta_boxes['file_attachments'] = array(
        'title'    => __('File attachments', 'trainup'),
        'context'  => 'side',
        'priority' => 'low'
      );
      $meta_boxes['answers'] = array(
        'title'    => __('Answers', 'trainup'),
        'context'  => 'advanced',
        'priority' => 'high',
        'closed'   => true
      );
    }

    return $meta_boxes;
  }

  /**
   * get_columns
   *
   * Returns a hash of extra columns to include when displaying Results in the
   * backend. Each key gets automatically mapped to a function.
   *
   * @access protected
   *
   * @return array
   */
  protected function get_columns() {
    return array(
      'mark'       => __('Mark', 'trainup'),
      'out_of'     => __('Out of', 'trainup'),
      'percentage' => __('Percentage', 'trainup'),
      'grade'      => __('Grade', 'trainup'),
      'passed'     => __('Passed', 'trainup'),
      'archive'    => __('Archive', 'trainup')
    );
  }

  /**
   * get_sortable_columns
   *
   * - Returns an array of columns that WordPress should make sortable for the
   *   Test-Result post type admin class.
   *
   * @access protected
   *
   * @return array
   */
  protected function get_sortable_columns() {
    return array(
      'mark'       => __('Mark', 'trainup'),
      'percentage' => __('Percentage', 'trainup'),
      'passed'     => __('Passed', 'trainup')
    );
  }

  /**
   * get_archived_result_value
   *
   * A shortcut function for retrieving a value from an archived result.
   *
   * @param string $key
   *
   * @access private
   *
   * @return string
   */
  private function get_archived_result_value($key) {
    global $post;

    $archive = Results::factory($post)->get_archive();

    return $archive[$key];
  }

  /**
   * column_mark
   *
   * @access protected
   */
  protected function column_mark() {
    echo $this->get_archived_result_value('mark');
  }

  /**
   * column_out_of
   *
   * @access protected
   */
  protected function column_out_of() {
    echo $this->get_archived_result_value('out_of');
  }

  /**
   * column_percentage
   *
   * @access protected
   */
  protected function column_percentage() {
    echo $this->get_archived_result_value('percentage').'%';
  }

  /**
   * column_grade
   *
   * @access protected
   */
  protected function column_grade() {
    echo $this->get_archived_result_value('grade');
  }

  /**
   * column_passed
   *
   * @access protected
   */
  protected function column_passed() {
    $passed = $this->get_archived_result_value('passed');
    $text   = $passed ? __('Yes', 'trainup') : __('No', 'trainup');

    echo "<span class='tu-passed-{$passed}'>{$text}</span>";
  }

  /**
   * column_archive
   *
   * @access protected
   */
  protected function column_archive() {
    global $post;

    $user_id = Results::factory($post)->get_user_id();
    $href    = "admin.php?page=tu_results&tu_user_id={$user_id}";
    $text    = __('View archive', 'trainup');

    echo "<a href='{$href}'>{$text}&nbsp;&raquo;</a>";
  }

  /**
   * _reposition_advanced_meta_boxes
   *
   * - Fired on `edit_form_after_title`
   * - Render the advanced meta boxes for this admin class' post type.
   *   then unset them, so they don't get rendered twice.
   * - (The reason for this function is that there is no other way to get
   #   meta boxes to appear above the main WYSIWYG editor).
   *
   * @access private
   */
  public function _reposition_advanced_meta_boxes() {
    global $post, $wp_meta_boxes;
    do_meta_boxes(get_current_screen(), 'advanced', $post);
    unset($wp_meta_boxes[$this->post_type->name]['advanced']);
  }

  /**
   * on_save
   *
   * - Go through the submitted response data for that admins/group managers
   *   may have provided and add their response to the Trainee's attempt archive
   *
   * @param integer $post_id
   * @param object $post
   *
   * @access protected
   */
  protected function on_save($post_id, $post) {
    $result    = Results::factory($post);
    $archive   = $result->get_archive();
    $answers   = unserialize($archive['answers']);
    $responses = isset($_POST['tu_answer_response']) ? $_POST['tu_answer_response'] : array();

    foreach ($answers as &$attempt) {
      foreach ($responses as $question_id => $response) {
        if (isset($attempt['question_id']) and $question_id == $attempt['question_id']) {
          $attempt['response'] = $response;
        }
      }
    }

    $archive['answers'] = serialize($answers);
    Results::update_archive($archive);
  }

  /**
   * meta_box_results
   *
   * - Fired when the 'results' meta box is to be rendered.
   *   (It displays the mark information for the user's latest attempt at the
   *   test).
   * - Echo out the view
   *
   * @access protected
   */
  protected function meta_box_results() {
    $user    = tu()->result->user;
    $test    = tu()->result->test;
    $archive = tu()->result->get_archive();

    $data = $archive + array(
      'resitable'   => $test->is_resitable(),
      'resit_count' => $user->get_resit_attempts($test->ID)
    );

    echo new View(tu()->get_path('/view/backend/results/results_meta'), $data);
  }

  /**
   * meta_box_relationships
   *
   * - Callback for the 'relation' meta box
   * - Render a simple view containing links to this Result's Test, and the
   *   Trainee who took it.
   *
   * @access protected
   */
  protected function meta_box_relationships() {
    echo new View(tu()->get_path('/view/backend/results/relation_meta'), array(
      'test'     => tu()->result->test,
      'user'     => tu()->result->user,
      'archive'  => tu()->result->archive,
      'is_admin' => tu()->user->is_administrator(),
      '_test'    => strtolower(tu()->config['tests']['single']),
      '_trainee' => strtolower(tu()->config['trainees']['single'])
    ));
  }

  /**
   * meta_box_file_attachments
   *
   * - Callback for the 'file_attachments' meta box
   * - Render a simple view that lists all files that are associated with the
   *   Test that this Result is for.
   * - Note that this box is not dependant on the File Attachments Question Type
   *   addon, but it does require that files are stored in the same directory
   *   structure as that addon in order to display correctly.
   * - Note this meta box is hidden with CSS if there are no uploads because
   *   we don't have access to the Test Result object when the meta boxes are
   *   being added :(
   * - Also, this meta box is kind of redundant now that we have the answers
   *   meta box which also lists file attachments...
   *
   * @access protected
   */
  protected function meta_box_file_attachments() {
    echo new View(tu()->get_path('/view/backend/results/file_attachments_meta'), array(
      'uploads' => tu()->result->uploads
    ));
  }

  /**
   * meta_box_answers
   *
   * - Callback for the 'answers' meta box
   * - Render a view that shows the Question, the Trainee's answer to that
   *   question and a space for any post-meta information where admins/group
   *   managers can save a response to each answer.
   *
   * @access protected
   */
  protected function meta_box_answers() {
    echo tu()->result->user->archived_answers(tu()->result->test_id);
  }

  /**
   * _add_answers_post_meta_fields
   *
   * - Callback fired on `tu_archived_answer_row`
   * - Render out a new row in the standard archived answers view that shows
   *   a textarea and allows the user to set a response to an answer.
   *
   * @access private
   */
  public function _add_answers_meta_fields($attempt) {
    echo new View(tu()->get_path('/view/backend/results/answers_meta'), compact('attempt'));
  }

  /**
   * add_crumbs
   *
   * Add a crumb to the root Results page, then add the post type crumbs
   * as usual.
   *
   * @access protected
   */
  protected function add_crumbs() {
    tu()->add_crumb('admin.php?page=tu_results', __('Results', 'trainup'));
    parent::add_crumbs();
  }

  /**
   * process_bulk_actions
   *
   * - Fired when this admin class is active, and the user has submitted
   *   a bulk action.
   * - Process the bulk emailer
   *
   * @access private
   */
  private function process_bulk_actions() {
    if (!isset($_REQUEST['action'])) return;

    $send_email = (
      $_REQUEST['action'] === 'tu_send_email' &&
      isset($_REQUEST['post'])
    );

    if ($send_email) {
      $this->set_up_bulk_emailer($_REQUEST['post']);
    }
  }

  /**
   * set_up_bulk_emailer
   *
   * - Fired when the user submits the 'Send email' bulk action.
   * - Load the results whose IDs were submitted, then go through each one
   *   and get the associated user id.
   *
   * @param array $result_ids
   *
   * @access private
   */
  private function set_up_bulk_emailer($result_ids) {
    global $wpdb;

    if (count($result_ids) < 1) return;

    $trainee_ids = array();

    $sql = "
      SELECT *
      FROM   {$wpdb->posts}
      WHERE  ID IN (".join(', ', $result_ids).")
    ";

    foreach (get_as('Results', $wpdb->get_results($sql)) as $result) {
      $trainee_ids[] = $result->get_user_id();
    }

    $args = array(
      'page'        => 'tu_emailer',
      'user_ids'    => $trainee_ids,
      'tu_context'  => array(
        'title'     => __('Results', 'trainup'),
        'link'      => $this->front_page,
        'menu_slug' => 'tu_results'
      )
    );

    $url = add_query_arg($args, 'admin.php');

    go_to($url);
  }

  /**
   * get_help
   *
   * - Fired on `current_screen`
   * - Add some help related to Test Results
   *
   * @access protected
   */
  protected function get_help() {
    $_test = strtolower(tu()->config['tests']['single']);

    return array(
      'deletion' => array(
        'title'   => sprintf(__('Deleting %1$s results', 'trainup'), $_test),
        'content' => '<p>'.sprintf(__('When a result post is deleted you are only throwing away a certificate of completion of a %1$s. All attempted answers and uploaded files remain in the archive.', 'trainup'), $_test).'</p>'
      )
    );
  }

}



