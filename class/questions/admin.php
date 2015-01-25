<?php

/**
 * The base admin screen for working with Questions
 *
 * @package Train-Up!
 * @subpackage Questions
 */

namespace TU;

class Questions_admin extends Admin {

  /**
   * $front_page
   *
   * The sub menu item slug for Questions administration. It points to the 
   * test's page, because Questions 'sit' underneath Tests in the breadcrumb
   * hierarchy.
   *
   * @var string
   *
   * @access protected
   */
  protected $front_page = 'admin.php?page=tu_test';

  /**
   * __construct
   *
   * - Create the Questions admin page as normal
   * - Then if active add styles & scripts
   * 
   * @access public
   */
  public function __construct() {
    parent::__construct();

    if ($this->is_active()) {
      add_action('admin_enqueue_scripts', array($this, '_add_assets'));
    }
  }

  /**
   * _add_assets
   *
   * - Fired when Question administration is active
   * - Enqueue styles and scripts necessary to make the Question section work
   * - Fired an action `tu_question_assets` so that addons can enqueue styles
   *   and scripts for custom questions.
   * 
   * @access public
   * @static
   */
  public static function _add_assets() {
    wp_enqueue_script('tu_questions');
    wp_enqueue_style('tu_questions');
    do_action('tu_question_backend_assets');
  }

}


 
