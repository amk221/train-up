<?php

/**
 * Functionality for the My results page
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class My_results_page extends Page {

  /**
   * $auth_required
   *
   * @var boolean
   *
   * @access protected
   */
  protected $auth_required = true;

  /**
   * $post_data
   *
   * Default data used to populate this post.
   * List the Trainee/User's results if they have any.
   *
   * @var array
   *
   * @access protected
   */
  protected $post_data = array(
    'post_title'   => 'My results',
    'post_content' => <<<EOT
[trainee_has_results][list_trainee_results][/trainee_has_results][!trainee_has_results]No results available yet[/!trainee_has_results]
EOT
  );

}


 
