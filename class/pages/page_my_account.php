<?php

/**
 * Functionality for the My account page
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class My_account_page extends Page {

  /**
   * $auth_required
   *
   * Whether or not this page requires a user be logged in to access it.
   *
   * @var boolean
   *
   * @access protected
   */
  protected $auth_required = true;

  /**
   * $post_data
   *
   * Default post data for the My Account page.
   * List the Levels available to the logged in Trainee/User
   *
   * @var array
   *
   * @access protected
   */
  protected $post_data = array(
    'post_title'   => 'My account',
    'post_content' => <<<EOT
<ul>
  <li>[edit_my_details_link]</li>
  <li>[my_results_link]</li>
  <li>[logout_link]</li>
</ul>

<h3>Training levels</h3>
[trainee_has_levels][list_trainee_levels][/trainee_has_levels][!trainee_has_levels]No levels available yet[/!trainee_has_levels]
EOT
  );

}


 
