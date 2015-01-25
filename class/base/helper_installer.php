<?php

/**
 * Helper class for installing the plugin
 *
 * @package Train-Up!
 */

namespace TU;

class Installer {

  /**
   * __construct
   *
   * - Fired when this plugin is being activated for the first time.
   * - Register the 'static' post types and cache them for the first time.
   * - Instantiate the pages, their related posts will be created automatically.
   * - Create the table that houses the archived result data.
   * - Add a marker `tu_installed` so we don't install the plugin more than once
   * 
   * @access public
   */
  public function __construct() {
    $this->register_post_types();
    $this->create_pages();
    $this->create_archive();

    update_option('tu_installed', true);
  }

  /**
   * create_pages
   *
   * Instantiate the pages, if their corresponding WordPress post does not exist
   * it will be created automatically.
   * 
   * @access private
   */
  private function create_pages() {
    new Login_page;
    new Logout_page;
    new Sign_up_page;
    new Forgotten_password_page;
    new Reset_password_page;
    new My_account_page;
    new Edit_my_details_page;
    new My_results_page;
  }

  /**
   * register_post_types
   *
   * Instantiate the static post types and cache them in the DB, so they will
   * get registered automatically on future requests.
   * 
   * @access private
   */
  private function register_post_types() {
    $levels = new Level_post_type;
    $levels->cache();

    $tests = new Test_post_type;
    $tests->cache();

    $groups = new Group_post_type;
    $groups->cache();

    $pages = new Page_post_type;
    $pages->cache();

    do_action('tu_install_cpt');
  }

  /**
   * create_archive
   *
   * Create the table that houses the Trainee's Result data.
   * 
   * @access private
   */
  private function create_archive() {
    global $wpdb;

    $wpdb->query("
      CREATE TABLE `{$wpdb->prefix}tu_archive` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `date_submitted` datetime DEFAULT NULL,
        `duration` int(11) unsigned DEFAULT NULL,
        `user_id` int(11) unsigned DEFAULT NULL,
        `test_id` int(11) unsigned DEFAULT NULL,
        `user_name` varchar(128) DEFAULT NULL,
        `test_title` varchar(255) DEFAULT NULL,
        `answers` longtext,
        `mark` int(10) unsigned DEFAULT NULL,
        `out_of` int(10) unsigned DEFAULT NULL,
        `percentage` tinyint(10) unsigned DEFAULT NULL,
        `grade` varchar(128) DEFAULT NULL,
        `passed` tinyint(1) DEFAULT NULL,
        `resit_number` tinyint(4) unsigned DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `test_id` (`test_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    ");
  }

}


 
