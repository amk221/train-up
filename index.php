<?php
/*
Plugin Name: Train-Up!
Plugin URI: github.com/amk221/train-up
Description: Open Source e-learning plugin for WordPress
Version: 1.3.4
*/

if (version_compare(phpversion(), '5.3', '<' )) {
  add_action('admin_notices', create_function('', "
    echo '<div id=\"message\" class=\"error\"><p>',
    __('Train-Up! requires PHP version 5.3 or greater', 'trainup'),
    '</p></div>';
   "), 100);
  return;
}

define('TU_DEBUG', false);

include('dependencies.php');

tu()->start();
