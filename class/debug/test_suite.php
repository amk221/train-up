<?php

/**
 * Some simple tests to check stuff actually works, not ideal really
 *
 * - Each test is a function that handles its own sub-tests.
 * - Each test sets up and tearsdown its own environment.
 *
 * @package Train-Up!
 * @subpackage Test_suite
 */

namespace TU;

class Test_suite {

  public static $output = array();

  /**
   * run
   *
   * A quick function to run all the tests and build an array of the results
   *
   * @access public
   * @static
   */
  public static function run() {
    foreach (get_class_methods(__CLASS__) as $method) {
      if ($method{0} === '_') {
        list($name, $result) = self::$method();
        self::$output[$name] = $result;
      }
    }
  }

  /**
   * _trimming
   *
   * This test checks that if whitespace trimming is on, then it makes it less
   * likely that Trainees will accidently get a question wrong.
   *
   * @access private
   * @static
   *
   * @return array Test data
   */
  private static function _trimming() {
    $name   = 'Whitespace trimming of answers';
    $result = array();
    $user   = tu()->user;
    $question = Questions::factory(array('post_title' => 'tu_temp'));
    $question->save();
    $question->set_type('single');
    $question->save_single_answer('foo', 'equal-to');

    $user->save_temporary_answer_to_question($question, ' foo ');

    if (tu()->config['tests']['trim_answer_whitespace']) {
      $result["' foo ' should be equal to 'foo'"] = Questions::validate_answer($user, $question);
    } else {
      $result["' foo ' should not be equal to 'foo'"] = !Questions::validate_answer($user, $question);
    }

    $question->delete();

    return array($name, $result);
  }

  /**
   * _equal_to
   *
   * This test checks that the 'equal-to' answer type works as expected.
   *
   * @access private
   * @static
   *
   * @return array Test data
   */
  private static function _equal_to() {
    $name   = 'Equal-to answer type';
    $result = array();
    $user   = tu()->user;
    $question = Questions::factory(array('post_title' => 'tu_temp'));
    $question->save();
    $question->set_type('single');
    $question->save_single_answer('foo', 'equal-to');

    $user->save_temporary_answer_to_question($question, 'foo');
    $result["'foo' should be equal to 'foo'"] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 'Foo');
    $result["'Foo' should not be equal to 'foo'"] = !Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 'bar');
    $result["'bar' should not be equal to 'foo'"] = !Questions::validate_answer($user, $question);

    $question->save_single_answer(3, 'equal-to');

    $user->save_temporary_answer_to_question($question, 3);
    $result["3 should be equal to 3"] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, '3');
    $result["'3' should be equal to 3"] = Questions::validate_answer($user, $question);

    $question->delete();

    return array($name, $result);
  }

  /**
   * _greater_than
   *
   * This test checks that the 'greater-than' answer type works as expected.
   *
   * @access private
   * @static
   *
   * @return array Test data
   */
  private static function _greater_than() {
    $name   = 'Greater-than answer type';
    $result = array();
    $user   = tu()->user;
    $question = Questions::factory(array('post_title' => 'tu_temp'));
    $question->save();
    $question->set_type('single');
    $question->save_single_answer('10', 'greater-than');

    $user->save_temporary_answer_to_question($question, '11');
    $result["'11' should be greater than 10"] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 11);
    $result['11 should be greater than 10'] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 10.1);
    $result['10.1 should be greater than 10'] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 9.5);
    $result['9.5 should be less than 10'] = !Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 'foo');
    $result["'foo' should be less than 10"] = !Questions::validate_answer($user, $question);

    $question->save_single_answer('-10', 'greater-than');
    $user->save_temporary_answer_to_question($question, '-20');
    $result["'-20' should be less than -10"] = !Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, '-5');
    $result["'-5' should be greater than -10"] = Questions::validate_answer($user, $question);

    $question->delete();

    return array($name, $result);
  }

  /**
   * _contains
   *
   * This test checks that the 'contains' answer type works as expected.
   *
   * @access private
   * @static
   *
   * @return array Test data
   */
  private static function _contains() {
    $name   = 'Contains answer type';
    $result = array();
    $user   = tu()->user;
    $question = Questions::factory(array('post_title' => 'tu_temp'));
    $question->save();
    $question->set_type('single');
    $question->save_single_answer('bar', 'contains');

    $user->save_temporary_answer_to_question($question, 'foo bar baz');
    $result["'bar' should be found in 'foo bar baz'"] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 'BAR');
    $result["'BAR' should be found in 'foo bar baz'"] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 'qux');
    $result["'qux' should not be found in 'foo bar baz'"] = !Questions::validate_answer($user, $question);

    $question->delete();

    return array($name, $result);
  }

  /**
   * _between
   *
   * This test checks that the 'between' answer type works as expected.
   *
   * @access private
   * @static
   *
   * @return array Test data
   */
  private static function _between() {
    $name   = 'Between answer type';
    $result = array();
    $user   = tu()->user;
    $question = Questions::factory(array('post_title' => 'tu_temp'));
    $question->save();
    $question->set_type('single');
    $question->save_single_answer('10,20', 'between');

    $user->save_temporary_answer_to_question($question, 10);
    $result["10 should be between 10 and 20"] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 11);
    $result["11 should be between 10 and 20"] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 9);
    $result["9 should not be between 10 and 20"] = !Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, '11');
    $result["'11' should be between 10 and 20"] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 'foo');
    $result["'foo' should not be between 10 and 20"] = !Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 20.1);
    $result["20.1 should not be between 10 and 20"] = !Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 10.1);
    $result["10.1 should between 10 and 20"] = Questions::validate_answer($user, $question);

    $question->delete();

    return array($name, $result);
  }

  /**
   * _matches_pattern
   *
   * This test checks the pattern matching answer type
   *
   * @access private
   * @static
   *
   * @return array Test data
   */
  private static function _matches_pattern() {
    $name     = "Matches pattern answer type";
    $result   = array();
    $user     = tu()->user;
    $question = Questions::factory(array('post_title' => 'tu_temp'));
    $question->save();
    $question->set_type('single');
    $question->save_single_answer(addslashes('FOO\sBAR'), 'matches-pattern');

    $user->save_temporary_answer_to_question($question, 'foo bar');
    $result["'foo bar' should not match '/FOO\sBAR/'"] = !Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 'FOO BAR');
    $result["'FOO BAR' should match '/FOO\sBAR/'"] = Questions::validate_answer($user, $question);

    $user->save_temporary_answer_to_question($question, 'Foo Bar');
    $question->set_pattern_modifier('i');
    $result["'Foo Bar' should match '/FOO\sBAR/i'"] = Questions::validate_answer($user, $question);

    $question->delete();

    return array($name, $result);
  }

  /**
   * _filter_ids
   *
   * This is a regression test to check that filter_ids behaves as expected
   *
   * @access private
   * @static
   *
   * @return array Test data
   */
  private static function _filter_ids() {
    $name   = 'Filter ids';
    $result = array();

    $has_access_to = array(2, 4, 6, 8);
    $available     = array(1, 2, 3, 5, 6, 9);

    $result[] = filter_ids($has_access_to, $available) == array(2, 6);

    $has_access_to = array(3);
    $available     = array(3);

    $result[] = filter_ids($has_access_to, $available) == array(3);

    $has_access_to = null;
    $available     = array(10, 20);

    $result[] = filter_ids($has_access_to, $available) == array(0);

    $has_access_to = array(3);
    $available     = null;

    $result[] = filter_ids($has_access_to, $available) == array(3);

    return array($name, $result);
  }

  /**
   * _bubble_up
   *
   * Checks that properties and methods bubble up to the WordPress post
   * (The same concept is used on WP_User)
   *
   * @access private
   * @static
   *
   * @return array Test data
   */
  private static function _bubble_up() {
    $name   = 'Bubble up';
    $result = array();

    $post  = new \WP_Post((object)array('post_title' => 'tu_temp'));
    $level = Levels::factory($post);
    $level->foo = 'bar';

    $result['gets properties'] = $level->foo === 'bar';
    $result["doesn't get missing properties"] = $level->bar !== 'baz';
    $result["bubbles up properties to WP_Post"] = $level->post_title === 'tu_temp';
    $result["calls getter"] = $level->get_template_file() === 'tu_level';
    $result["calls getter (shorthand)"] = $level->template_file === 'tu_level';
    $result["bubbles up methods to WP_Post"] = is_array($level->to_array());

    return array($name, $result);
  }

}


