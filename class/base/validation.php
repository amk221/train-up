<?php

namespace TU;

class Validator {

  public $form = array();

  public $errors = array();

  public function __construct($form = array()) {
    $this->form = $form;
  }

  public function rule_required($str = '') {
    return is_numeric($str) || (bool)$str;
  }

  public function message_required($str = '') {
    return __('This field is required', 'trainup');
  }

  public function rule_checked($str = '') {
    return $this->rule_required($str);
  }

  public function message_checked($str = '') {
    return $this->message_required($str);
  }

  public function rule_check_one($str = '') {
    return $this->rule_required($str);
  }

  public function message_check_One($str = '') {
    return __('Please choose an option', 'trainup');
  }

  public function rule_select_one($str = '') {
    return $this->rule_required($str);
  }

  public function message_select_one($str = '') {
    return __('Please select an option', 'trainup');
  }

  public function rule_email($str = '') {
    return preg_match('/^.+@.+\..+$/', $str);
  }

  public function message_email($str = '') {
    return __('A valid e-mail address is required', 'trainup');
  }

  public function rule_email_unique($str = '') {
    return !email_exists($str);
  }

  public function message_email_unique($str = '') {
    return __('That email address is already taken', 'trainup');
  }

  public function rule_email_exists($str = '') {
    return (bool)get_user_by('email', $str);
  }

  public function message_email_exists($str = '') {
    return __('Invalid email address', 'trainup');
  }

  public function rule_password($str = '') {
    return strlen($str) >= 6;
  }

  public function message_password($str = '') {
    return __('Your password must be more than 5 characters', 'trainup');
  }

  public function rule_password_match($str = '', $field) {
    return $str === $this->form["confirm_{$field}"];
  }

  public function message_password_match($str = '') {
    return __('Your passwords do not match', 'trainup');
  }

  public function rule_username($str = '') {
    return strlen($str) >= 4;
  }

  public function message_username($str = '') {
    return __('Your username must be more than 3 characters', 'trainup');
  }

  public function rule_username_unique($str = '') {
    return !username_exists($str);
  }

  public function message_username_unique($str = '') {
    return __('Sorry, that username is already taken', 'trainup');
  }

  public function rule_activated($str = '') {
    global $wpdb;

    return !$wpdb->get_var($wpdb->prepare("
      SELECT user_activation_key
      FROM   {$wpdb->users}
      WHERE  user_login = %s
    ", $this->form['user_login']));
  }

  public function message_activated($str = '') {
    return __('Your account is not activated', 'trainup');
  }

  public function rule_auth_ok($str = '') {
    return !is_wp_error(wp_signon(array(
      'user_login'    => $this->form['user_login'],
      'user_password' => $this->form['user_password']
    )));
  }

  public function message_auth_ok($str = '') {
    return __('Invalid username or password', 'trainup');
  }

  public function rule_postcode($str = '') {
    return call_user_func_array(
      array($this, 'rule_postcode_' . ICL_LANGUAGE_CODE),
      array($str)
    );
  }

  public function message_postcode($str = '') {
    return __('Please enter a valid postcode', 'trainup');
  }

  public function rule_postcode_en($str = '') {
    return preg_match('/^(\w{1,2}\d{1,2})(\w?\s?\d\w{2})$/', $str);
  }

  public function message_postcode_en($str = '') {
    return $this->message_postcode($str);
  }

  public function rule_postcode_us($str = '') {
    return preg_match('/^(\d{5})(-\d{4})?$/', $str);
  }

  public function message_postcode_us($str = '') {
    return $this->message_postcode($str); 
  }

  public function rule_postcode_pl($str = '') {
    return preg_match('/^[\d]+$/', $str);
  }

  public function message_postcode_pl($str = '') {
    return $this->message_postcode($str);
  }

  public function rule_day($str = '') {
    return preg_match('/^[\d]{1,2}$/', $str);
  }

  public function message_day($str = '') {
    return __('Please select the day of the month', 'trainup');
  }

  public function rule_month($str = '') {
    return preg_match('/^[\d]{1,2}$/', $str);
  }

  public function message_month($str = '') {
    return __('Please select the month', 'trainup');
  }

  public function rule_year($str = '') {
    return preg_match('/^[\d]{4}$/', $str);
  } 

  public function message_year($str = '') {
    return __('Please select the year', 'trainup');
  }

  public function rule_phone_number($str = '') {
    return preg_match('/^[\d \+]+$/i', $str);
  } 

  public function message_phone_number($str = '') {
    return __('Please enter a valid phone number', 'trainup');
  }

  public function validate($validation_rules) {
    $validates = true;

    foreach ($validation_rules as $field => $rules) {
      $validates &= $this->validate_field($field, $rules);
    }

    return $validates;
  }

  public function validate_field($field, $rules) {
    $value     = isset($this->form[$field]) ? $this->form[$field] : null;
    $validates = true;

    for ($i = 0; $i < count($rules); $i++) {
      $rule = $rules[$i];

      $validates &= call_user_func_array(
        array($this, "rule_{$rule}"),
        array($value, $field)
      );

      if (!$validates) {
        $this->set_error($field, $rule);
        break;
      }
    }

    return $validates;
  }

  public function set_error($field, $message) {
    $value = isset($this->form[$field]) ? $this->form[$field] : null;

    $this->errors[$field] = call_user_func_array(
      array($this, "message_{$message}"),
      array($value)
    );
  }

  public function error($field) {
    if (isset($this->errors[$field])) {
      echo "<span class='form-validation'>{$this->errors[$field]}</span>";
    }
  }

}
