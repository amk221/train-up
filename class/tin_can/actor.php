<?php

namespace TU;

class Tin_can_actor {

  /**
   * $objectType
   *
   * The type of actor, default to 'Agent' which basically means a User.
   *
   * @see http://xmlns.com/foaf/spec/#term_Agent
   * @var string
   *
   * @access public
   */
  public $objectType = 'Agent';
  
  /**
   * $name
   *
   * The first and last name of the user.
   *
   * @var string
   *
   * @access public
   */
  public $name = '';

  /**
   * $mbox
   *
   * The email address of the user, prefixed with 'mailto:'
   *
   * @var string
   *
   * @access public
   */
  public $mbox = 'mailto:';

}