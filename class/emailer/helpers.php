<?php

/**
 * General helper functions for working with the Emailer
 *
 * @package Train-Up!
 * @subpackage Emailer
 */

namespace TU;

class Emailer {

  /**
   * send
   *
   * Callback for an AJAX request that attempts to mass email.
   * 
   * @param array $recipients
   * @param string $from 
   * @param string $subject 
   * @param string $body
   *
   * @access public
   * @static
   *
   * @return mixed Array details of the send attempt
   */
  public static function ajax_send($recipients = array(), $from, $subject, $body) {
    $permission = (
      tu()->user->is_group_manager() ||
      tu()->user->is_administrator()
    );

    if (!$permission) return;

    $total   = count($recipients);
    $body    = html_entity_decode(stripcslashes($body));
    $sent    = 0;
    $headers = array(
      'Content-type: text/html',
      "from: {$from}"
    );

    foreach ((array)$recipients as $to) {
      preg_match('/(.*)<(.+)>/', $to, $matches);

      if (count($matches) == 3) {
        $address = $matches[2];
      } else {
        $address = $to;
      }
      
      $user     = Users::factory($address);
      $_body    = Users::do_swaps($user, $body);
      $_subject = Users::do_swaps($user, $subject);

      $sent += (int)wp_mail($to, $_subject, $_body, $headers);
    }

    $msg = tu()->message->view('error', __('Error sending email', 'trainup'));

    if ($sent > 0) {
      $msg = tu()->message->view('success', sprintf(
        _n('%1$s email sent', '%1$s emails sent', $sent, 'trainup'),
      $sent));
    }

    $result = compact('sent', 'total', 'msg');

    return $result;
  }

  /**
   * localised_js
   *
   * Returns the hash of localised vars to be used with the emailer JavaScript.
   * 
   * @access public
   * @static
   *
   * @return array
   */
  public static function localised_js() {
    return array(
      '_sendEmail' => __('Send email', 'trainup'),
      '_send'      => __('Send', 'trainup'),
      '_sending'   => __('Sending', 'trainup')
    );
  }

  /**
   * autocomplete
   * 
   * Exposes an autocompletion function for finding users in the system, but
   * specifically for use with the emailer, i.e. we also include the email
   * address to help autocompletion.
   *
   * @param string $search_str 
   *
   * @access public
   * @static
   *
   * @return array
   */
  public static function ajax_autocomplete($search_str) {
    return Users::ajax_autocomplete($search_str, null, "display_name, ' <', user_email, '>'");
  }

}


 
