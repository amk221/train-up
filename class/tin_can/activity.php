<?php

namespace TU;

class Tin_can_activity {  

  /**
   * $id
   *
   * The URI of that uniquely identifies this activity.
   *
   * @var string
   *
   * @access public
   */
  public $id = 'http://';

  /**
   * $objectType
   *
   * The default type of this activity. Default to 'Activity', but could also
   * be an 'Agent'
   *
   * @var string
   *
   * @access public
   */
  public $objectType = 'Activity';

  /**
   * $definition
   *
   * The definition of this activity including:
   * - 'name' a language map that names this activity
   * - 'description' a language map that describes this activity
   * - 'type' the URI that uniquely identifies activities of this type.
   *   e.g. if $type is Quiz, then $id could be Quiz1
   * - 'interactionType'
   * - 'extensions'
   *
   * @var array
   *
   * @access public
   */
  public $definition = array(
    'name'            => array(),
    'description'     => array(),
    'type'            => 'http://',
    'interactionType' => '',
    'extensions'      => array()
  );

}