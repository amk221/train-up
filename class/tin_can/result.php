<?php

namespace TU;

class Tin_can_result {  

  /**
   * $score
   *
   * The 'Score' of a Tin Can Result is a representation of the Train-Up!
   * Result's percentage.
   *
   * @var object
   *
   * @access public
   */
  public $score;

  /**
   * $success
   *
   * Whether or not the result is a pass or fail
   *
   * @var boolean
   *
   * @access public
   */
  public $success = false;

  /**
   * $completion
   *
   * Whether or not this result is complete. Kind of pointless, so true by
   * default because a result by its very nature is complete.
   *
   * @var boolean
   *
   * @access public
   */
  public $completion = true;

  /**
   * $response
   *
   * A Tin Can Result response is the 'post_content' from a Train-Up! Result
   *
   * @var string
   *
   * @access public
   */
  public $response = '';

  /**
   * $duration
   *
   * An ISO 8601 string representing an interval of time, e.g. PT1S (1 second)
   *
   * @var string
   *
   * @access public
   */
  public $duration = '';

}