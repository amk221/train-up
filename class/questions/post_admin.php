<?php

/**
 * The admin screen for working with Questions
 *
 * @package Train-Up!
 * @subpackage Questions
 */

namespace TU;

class Question_admin extends Post_admin {

   /**
   * __construct
   * 
   * - Creates a new admin section for managing Questions
   * - Accept a dynamic question post type
   * - Carry on constructing the post type admin as normal,
   *   then if active, add Question specific actions.
   *
   * @param object $post_type
   *
   * @access public
   */
  public function __construct($post_type) {
    parent::__construct($post_type);

    if ($this->is_active()) {
      
      if ($this->is_uneditable()) {
        tu()->message->set_flash('error', $this->uneditable_message());
      }

      $this->highlight_sub_menu_item = 'edit.php?post_type=tu_test';

      add_action('admin_head', array($this, '_set_edit_title'));
      add_action('add_meta_boxes', array($this, '_auto_set_menu_order'));
      add_action('admin_enqueue_scripts', array(__NAMESPACE__.'\\Questions_admin', '_add_assets'));
    }
  }

  /**
   * _set_edit_title
   *
   * - Fired on `admin_head` (when the tu() post accessor is available)
   * - Improve the title when editing a question to make it more useful
   *   than the default Question Post type label.
   * 
   * @access private
   */
  public function _set_edit_title() {
    if ($this->is_editing()) {
      $this->title = sprintf(
        __('Edit %1$s for %2$s', 'trainup'),
        tu()->question->post_title,
        tu()->question->test->post_title
      );
    }
  }

  /**
   * _auto_set_menu_order
   *
   * - Fired on `add_meta_boxes`
   * - If a new question is being added, get the next menu order based on the
   *   current number of questions in the test.
   * - Pre-set the the menu order
   * 
   * @access private
   */
  public function _auto_set_menu_order() {
    global $post, $wpdb;

    if (!$this->is_adding()) return;

    $sql = "
      SELECT MAX(menu_order) + 1
      FROM   {$wpdb->posts}
      WHERE  post_type = %s
    ";

    $post->menu_order = $wpdb->get_var($wpdb->prepare($sql, $this->post_type->name));
  }

  /**
   * get_meta_boxes
   *
   * We define the standard question type meta boxes: multiple and single
   * If the Question being edited isn't one of these, then apply the filters
   * so that developers can add their own meta box for the custom question type.
   * 
   * @access protected
   *
   * @return array A hash of meta boxes to show when dealing with Questions.
   */
  protected function get_meta_boxes() {
    $meta_boxes = array(
      'shortcodes' => array(
        'title'    => __('Shortcodes', 'trainup'),
        'context'  => 'side',
        'priority' => 'high',
        'closed'   => true
      ),
      'type' => array(
        'title'    => __('Question type', 'trainup'),
        'context'  => 'side',
        'priority' => 'default'
      ),
      'multiple' => array(
        'title'    => __('Multiple choice answers', 'trainup'),
        'context'  => 'advanced',
        'priority' => 'default'
      ),
      'single' => array(
        'title'    => __('Single answer', 'trainup'),
        'context'  => 'advanced',
        'priority' => 'default'
      )
    );

    $meta_boxes    = apply_filters('tu_question_meta_boxes', $meta_boxes);
    $question_id   = $this->is_editing();
    $question_type = Questions::factory($question_id)->type;

    if ($question_id && $question_type) {
      $meta_boxes = array(
        'shortcodes'   => $meta_boxes['shortcodes'],
        $question_type => $meta_boxes[$question_type]
      );
    }

    return $meta_boxes;
  }

  /**
   * meta_box_multiple
   *
   * - Fired when the 'multiple' answer meta box is to be rendered.
   *   (It displays text fields for each possible answer to the question)
   * - Echo out the view
   * 
   * @access protected
   */
  protected function meta_box_multiple() {
    echo new View(tu()->get_path('/view/backend/questions/multiple_answers_meta'), array(
      'question'       => tu()->question,
      'answers'        => tu()->question->answers,
      'correct_answer' => tu()->question->correct_answer
    ));
  }

  /**
   * meta_box_single
   *
   * - Fired when the 'single' answer meta box is to be rendered.
   *   (It displays a text field to specify the correct answer to the question)
   * - Echo out the view
   * 
   * @access protected
   */
  protected function meta_box_single() {
    echo new View(tu()->get_path('/view/backend/questions/single_answer_meta'), array(
      'comparison_name'  => tu()->question->comparison,
      'comparisons'      => Questions::get_comparisons(),
      'correct_answer'   => tu()->question->correct_answer,
      'pattern_modifier' => tu()->question->pattern_modifier,
      '_trainee'         => strtolower(tu()->config['trainees']['single'])
    ));
  }

  /**
   * meta_box_type
   *
   * - Fired when the question 'type' meta box is to be rendered.
   *   (It displays radio buttons to allowing choosing of the question type)
   * - Echo out the view
   * 
   * @access protected
   */
  protected function meta_box_type() {
    echo new View(tu()->get_path('/view/backend/questions/question_type_meta'), array(
      'question_types' => Questions::get_types(),
      'question_type'  => tu()->question->type ?: 'multiple'
    ));
  }

  /**
   * on_save
   *
   * - Fired when a Question is saved, bail if it is not editable
   *   Note: Physically saved by pressing Save/Publish (not just on the 
   *   initial save by WordPress, because no form data will exist).
   * - Set the ID of the Test that the Question belongs to.
   * - If the question type is single or multiple, then save the answers.
   * - Fire an action to allow saving of addon questions,
   *   otherwise, the question is assumed to be custom, so store the custom key.
   * - Call `save` on the actual Question to auto title and order it.
   * 
   * @param integer $post_id
   * @param object $post
   *
   * @access protected
   */
  protected function on_save($post_id, $post) {
    if ($this->is_uneditable()) {
      wp_die($this->uneditable_message());
    } else if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    $question = Questions::factory($post);
    $question->set_test_id($this->post_type->test_id);

    if (isset($_POST['question_type'])) {
      $question->set_type($_POST['question_type']);
    } else if (isset($_POST['custom_question_type'])) {
      $question->set_type($_POST['custom_question_type']);
    }
    
    if ($question->type === 'single') {
      if (empty($_POST['single_answer'])) {
        wp_die(__('Single-answer questions require an answer to be set', 'trainup'));
      }
      $question->save_single_answer(
        $_POST['single_answer'],
        $_POST['single_answer_comparison']
      );
      if ($question->comparison === "matches-pattern") {
        $question->set_pattern_modifier($_POST['pattern_modifier']);
      }
    }
    else if ($question->get_type() === 'multiple') {
      if (!isset($_POST['multiple_answer']) || count($_POST['multiple_answer']) < 2) {
        wp_die(__('Multiple choice questions require at least two answers', 'trainup'));
      } else if (!isset($_POST['correct_answer'])) {
        wp_die(__('You must choose a correct answer', 'trainup'));
      }

      $question->save_multiple_answers(
        $_POST['multiple_answer'],
        $_POST['multiple_answer'][$_POST['correct_answer']]
      );
    }
    
    do_action("tu_save_question_{$question->type}", $question);

    $question->save();
  }

  /**
   * on_delete
   *
   * - Fired when a Question is about to be deleted
   * - Do a 'soft' deletion of the question so that we can clean up data 
   *   associated with the question about to be deleted, but don't delete the
   *   actual post because WordPress is about to do that anyway.
   * 
   * @param integer $post_id
   *
   * @access protected
   */
  protected function on_delete($post_id) {
    Questions::factory($post_id)->delete(false);
  }

  /**
   * is_uneditable
   *
   * If the admin screen is editing a post, get its ID.
   * Load the Question and check if it can be edited. (A Question can be
   * edited if the Test which it belongs to hasn't been started by Trainees).
   * 
   * @access private
   *
   * @return boolean
   */
  private function is_uneditable() {
    $question_id = $this->is_editing();

    return $question_id && !Questions::factory($question_id)->can_edit();
  }

  /**
   * uneditable_message
   *
   * A string notifying the administrator they cannot edit the active question.
   * 
   * @access private
   *
   * @return string
   */
  private function uneditable_message() {
    return sprintf(
      __('You cannot edit this question because its %1$s has been started', 'trainup'),
      strtolower(tu()->config['tests']['single'])
    );
  }

  /**
   * add_crumbs
   *
   * - Get the ID of the Test that the current Question-post-type admin
   *   screen is for.
   * - Add crumbs that lead from the root through the Question's associated Test
   *   and down to the Question itself.
   * 
   * @access protected
   */
  protected function add_crumbs() {
    $test     = Tests::factory($this->post_type->test_id);
    $test_url = "post.php?post={$test->ID}&action=edit";

    tu()->add_crumb('edit.php?post_type=tu_test', tu()->config['tests']['plural']);
    tu()->add_crumb($test_url, $test->post_title);
    tu()->add_crumb("{$test_url}#questions", __('Questions', 'trainup'));

    if ($this->is_adding()) {
      tu()->add_crumb('', __('Add question', 'trainup'));
    } else if ($this->is_editing()) {
      tu()->add_crumb('', __('Edit question', 'trainup'));
    }
  }

}


 
