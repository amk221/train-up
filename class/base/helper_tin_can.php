<?php

/**
 * Helper class for working with Tin Can API requests.
 *
 * @package Train-Up!
 */

namespace TU;

class Tin_can_helper {

  /**
   * __call
   *
   * Make requests automatically depending on the type
   * 
   * @param mixed $method
   * @param mixed $arguments
   *
   * @access public
   *
   * @return mixed
   */
  public function __call($method, $args) {
    if (preg_match('/GET|POST|PUT|DELETE/i', $method)) {
      array_unshift($args, $method);
      return call_user_func_array(array($this, 'request'), $args);
    }
  }

  /**
   * request
   * 
   * @param mixed $method
   * @param mixed $url
   * @param mixed $data
   *
   * @access public
   *
   * @return object
   */
  public function request($method, $url, $data) {
    $url = tu()->config['tin_can']['lrs_api'] . $url;

    $args = array_merge(array(
      'method'   => strtoupper($method),
      'body'     => json_encode($data),
      'headers'  => array(
        'Content-type'  => 'application/json',
        'Authorization' => 'Basic ' . base64_encode(
          tu()->config['tin_can']['lrs_username'] . ':' .
          tu()->config['tin_can']['lrs_password']
        ),
        'x-experience-api-version' => tu()->config['tin_can']['version']
      )
    ));

    return json_decode(wp_remote_retrieve_body(wp_remote_request($url, $args)));
  }

  /**
   * start_test
   *
   * - Fired when a user starts a test
   * - If the Tin Can API is turned on, and tracking of starting a test is
   *   also enabled, then create a statement.
   * 
   * @param object $user
   * @param object $test
   *
   * @access public
   */
  public function start_test($user, $test) {
    if (tu()->config['tin_can']['enabled'] && isset(tu()->config['tin_can']['track']['start_test'])) {
      $statement         = new Tin_can_statement;
      $statement->verb   = Tin_can_verbs::$started;
      $statement->actor  = $user->as_tin_can_actor();
      $statement->object = $test->as_tin_can_activity();
      $statement->create();
    }
  }

  /**
   * finish_test
   *
   * - Fired when a user finishes a test (i.e. they submitted their answers,
   *   and so have either passed it or failed it).
   * - If the Tin Can API is turned on, and tracking of finishing a test is
   *   also enabled, then create a statement.
   * 
   * @param object $user
   * @param object $test
   * @param object $result
   *
   * @access public
   */
  public function finish_test($user, $test, $result) {
    if (tu()->config['tin_can']['enabled'] && isset(tu()->config['tin_can']['track']['finish_test'])) {
      $archive           = $user->get_archive($test->ID);
      $verb              = $archive['passed'] ? 'passed' : 'failed';
      $statement         = new Tin_can_statement;
      $statement->verb   = Tin_can_verbs::$$verb;
      $statement->actor  = $user->as_tin_can_actor();
      $statement->object = $test->as_tin_can_activity();
      $statement->result = $result->as_tin_can_object();
      $statement->create();
    }
  }

  /**
   * answer_question
   *
   * - Fired when a user answers a question
   * - If the Tin Can API is turned on, and tracking of answering questions is
   *   also enabled, then create a statement.
   * - TODO: Note, a user can attempt an answer at any time so they might be
   *   correcting themselves. Do we need to store the statement ID, and update
   *   the statement to reflect the user's change of mind?
   * 
   * @param object $user
   * @param object $question
   * @param object $answer
   *
   * @access public
   */
  public function answer_question($user, $question, $answer) {
    if (tu()->config['tin_can']['enabled'] && isset(tu()->config['tin_can']['track']['answer_question'])) {
      $statement         = new Tin_can_statement;
      $statement->verb   = Tin_can_verbs::$answered;
      $statement->actor  = $user->as_tin_can_actor();
      $statement->object = $question->as_tin_can_activity();
      $statement->create();
    }
  }

}
