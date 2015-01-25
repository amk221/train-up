<?php

namespace TU;

class Tin_can_verbs {

  /**
   * $started
   *
   * The 'started' verb. Refers to when a user has started a Test.
   *
   * @var array
   *
   * @access public
   * @static
   */
  public static $started = array(
    'id' => 'http://www.tincanapi.co.uk/verbs/started',
    'display' => array(
      'en-GB' => 'started',
      'en-US' => 'started'
    )
  );

  /**
   * $passed
   *
   * The 'passed' verb. Refers to when a user has passed a Test.
   *
   * @var array
   *
   * @access public
   * @static
   */
  public static $passed = array(
    'id' => 'http://www.tincanapi.co.uk/verbs/passed',
    'display' => array(
      'en-GB' => 'passed',
      'en-US' => 'passed'
    )
  );

  /**
   * $failed
   *
   * The 'failed' verb. Usually refers to when a user has failed a Test.
   *
   * @var array
   *
   * @access public
   * @static
   */
  public static $failed = array(
    'id' => 'http://www.tincanapi.co.uk/verbs/failed',
    'display' => array(
      'en-GB' => 'failed',
      'en-US' => 'failed'
    )
  );

  /**
   * $answered
   *
   * The 'answered' verb. Usually refers to when a user has attempted to 
   * answer a Question.
   *
   * @var array
   *
   * @access public
   * @static
   */
  public static $answered = array(
    'id' => 'http://www.tincanapi.co.uk/verbs/answered',
    'display' => array(
      'en-GB' => 'answered',
      'en-US' => 'answered'
    )
  );
}