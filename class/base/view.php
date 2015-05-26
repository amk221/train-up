<?php

/**
 * Simple utility class for rendering views
 *
 * @package Train-Up!
 */

namespace TU;

class View {

  /**
   * $file
   *
   * The path to the file to be rendered.
   *
   * @var string
   *
   * @access private
   */
  private $file = '';

  /**
   * $data
   *
   * A hash of data passed to the view file
   *
   * @var array
   *
   * @access private
   */
  private $data = array();

  /**
   * $view
   *
   * The rendered string
   *
   * @var string
   *
   * @access private
   */
  private $view = '';

  /**
   * __construct
   *
   * Include the specified file, and expand the hash of view data so the
   * view can be rendered, capture this string using an output buffer.
   *
   * @param string $file The path to the file
   * @param array $data A hash of data
   *
   * @access public
   */
  public function __construct($file, $data = array()) {
    $this->file = $file;
    $this->data = $data;

    ob_start();
    extract((array)$this->data);
    include("{$this->file}.php");
    $this->view = ob_get_contents();
    ob_end_clean();
  }

  /**
   * __toString
   *
   * @access public
   *
   * @return string The rendered view
   */
  public function __toString() {
    return $this->view;
  }

}

