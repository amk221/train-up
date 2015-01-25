<?php

/**
 * General helper functions for working with Questions
 *
 * @package Train-Up!
 * @subpackage Questions
 */

namespace TU;

class Questions {

  /**
   * factory
   * 
   * @param array|object $question
   *
   * @access public
   * @static
   *
   * @return object A Question instance
   */
  public static function factory($question = null) {
    return new Question($question);
  }

  /**
   * get_types
   * 
   * @access public
   * @static
   *
   * @return array Available question types
   */
  public static function get_types() {
    $types = array(
      'multiple' => __('Multiple choice', 'trainup'),
      'single'   => __('Single answer', 'trainup')
    );

    $result = apply_filters('tu_question_types', $types);

    return $result;
  }

  /**
   * get_comparisons
   *
   * Returns a hash of the different type of comparisons available for
   * determining whether a single-answer Question is correct or not.
   * 
   * @access public
   * @static
   *
   * @return array
   */
  public static function get_comparisons() {
    return array(
      'equal-to' => array(
        'cmi'   => 'fill-in',
        'title' => 'is'
      ),
      'greater-than' => array(
        'cmi'   => 'numeric',
        'title' => __('is greater than', 'trainup')
      ),
      'greater-than-or-equal-to' => array(
        'cmi'   => 'numeric',
        'title' => __('is greater than or equal to', 'trainup')
      ),
      'less-than' => array(
        'cmi'   => 'numeric',
        'title' => __('is less than', 'trainup')
      ),
      'less-than-or-equal-to' => array(
        'cmi'   => 'numeric',
        'title' => __('is less than or equal to', 'trainup')
      ),
      'contains' => array(
        'cmi'   => 'fill-in',
        'title' => __('contains', 'trainup')
      ),
      'between' => array(
        'cmi'   => 'numeric',
        'title' => __('is between (N,M)', 'trainup')
      ),
      'matches-pattern' => array(
        'cmi'   => 'fill-in',
        'title' => __('matches regular expression', 'trainup')
      )
    );
  }

  /**
   * validate_answer
   *
   * - Accept a user instance and a question instance, return whether or not
   *   the user's answer to that question is correct or not.
   * - If the administrators have set the option to, then trim whitespace
   *   from Trainee's attempted answers. This is because sometimes Trainees
   *   may accidentally leave a space at the beginning/end of their answer and
   *   get the question wrong, when technically they were correct.
   * - Allow the logic to be filtered so that developers can create custom
   *   Questions.
   * 
   * @param object $user
   * @param object $question
   *
   * @access public
   * @static
   *
   * @return boolean
   */
  public static function validate_answer($user, $question) {
    $correct        = false;
    $question_type  = $question->get_type();
    $users_answer   = $user->get_answer_to_question($question->ID);
    $correct_answer = $question->get_correct_answer();

    if (is_scalar($users_answer) && tu()->config['tests']['trim_answer_whitespace']) {
      $users_answer = trim($users_answer);
    }

    if ($question_type === 'multiple') {
      $correct = $users_answer == $correct_answer;
    }
    else if ($question_type === 'single') {
      $comparison = $question->comparison;

      switch ($comparison) {
        default:
        case 'equal-to':
          $correct = $users_answer == $correct_answer;
          break;
        case 'greater-than':
          $correct = floatval($users_answer) > floatval($correct_answer);
          break;
        case 'greater-than-or-equal-to':
          $correct = floatval($users_answer) >= floatval($correct_answer);
          break;
        case 'less-than':
          $correct = floatval($users_answer) < floatval($correct_answer);
          break;
        case 'less-than-or-equal-to':
          $correct = floatval($users_answer) <= floatval($correct_answer);
          break;
        case 'contains':
          $correct = (bool)stristr($users_answer, $correct_answer);
          break;
        case 'between':
          preg_match('/(\d+)[^\d]+(\d+)/', $correct_answer, $matches);
          $lower = isset($matches[1]) ? $matches[1] : 0;
          $upper = isset($matches[2]) ? $matches[2] : 0;
          $correct = (
            floatval($users_answer) >= floatval($lower) &&
            floatval($users_answer) <= floatval($upper)
          );
          break;
        case 'matches-pattern':
          $modifier = $question->pattern_modifier;
          $pattern = "/{$correct_answer}/{$modifier}";
          $correct = preg_match($pattern, $users_answer);
          break;
      }
    }

    $correct = apply_filters("tu_validate_answer", $correct, $users_answer, $question);
    $correct = apply_filters("tu_validate_answer_{$question_type}", $correct, $users_answer, $question);
    
    return $correct;
  }

  /**
   * ajax_save_answer
   *
   * - Callback for saving of a user's answer to a Question, via AJAX.
   * - This is optional, the default method of saving is
   *   `handle_saving_of_answers` in questions/post.php
   * - If you change one function, make sure to update the other.
   * 
   * @param string $query A query string sent from the submitted form
   * @param array $files Array of uploaded $_FILES
   *
   * @access public
   *
   * @return array
   */
  public static function ajax_save_answer($query, $files = array()) {
    wp_parse_str($query, $form);

    $question = Questions::factory($form['tu_question_id']);
    $answer   = isset($form['tu_answer']) ? $form['tu_answer'] : '';

    tu()->user->save_temporary_answer_to_question($question, $answer);

    $response = array(
      'type' => 'success',
      'msg'  => apply_filters(
        'tu_save_answer_message',
        __('Your answer was saved', 'trainup')
      )
    );

    $response = apply_filters('tu_saved_answer_ajax', $response, $question, $answer, $form, $files);

    $response['msg_html'] = tu()->message->view(
      $response['type'],
      $response['msg'],
      'frontend'
    );

    return $response;
  }

  /**
   * localised_js
   * 
   * @access public
   * @static
   *
   * @return array A hash of localised JS for when managing Questions.
   */
  public static function localised_js() {
    return array(
      '_confirmDeleteAnswer' => __('Are you sure you want to delete this answer?', 'trainup'),
      '_error'               => __('Error', 'trainup'),
      'questionTypes'        => self::get_types()
    );
  }

  /**
   * localised_frontend_js
   *
   * - JavaScript namespace for Questions.
   * - Filterable so that developers can customise it.
   * 
   * @access public
   * @static
   *
   * @return array
   */
  public static function localised_frontend_js() {
    $js = array(
      'saveViaAjax' => (bool)tu()->config['tests']['ajax_saving'],
      '_uploading'  => __('Uploading...', 'trainup')
    );

    $result = apply_filters('tu_questions_js_namespace', $js);

    return $result;
  }

}

