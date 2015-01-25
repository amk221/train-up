<?php

/**
 * Helper class for upgrading the plugin
 *
 * @package Train-Up!
 */

namespace TU;

class Upgrade_helper {

  /**
   * $api
   *
   * The URL to make calls to for finding out information about Train-Up!
   *
   * @var string
   *
   * @access private
   */
  private $api;

  /**
   * $latest_version
   *
   * Cached version number of the latest know version
   *
   * @var integer|string
   *
   * @access private
   */
  private $latest_version = null;

  /**
   * $latest_info
   *
   * Cached information about the latest version
   *
   * @var object
   *
   * @access private
   */
  private $latest_info = null;

  /**
   * __construct
   *
   * - Fired when the Train-Up! plugin starts
   * - Added filters to allow the plugin to be updated via our API rather than
   *   the standard WordPress one.
   * - Uncomment the `set_site_transient` line during testing to avoid having
   *   to wait 12 hours before the next upgrade check.
   *
   * @access public
   */
  public function __construct() {
    # set_site_transient('update_plugins', '');

    # TODO, change from old API to github
    return;

    $this->api = tu()->get_homepage() . '/api';

    add_filter('pre_set_site_transient_update_plugins', array($this, '_check'));
    add_filter('plugins_api', array($this, '_info'), 10, 3);
  }

  /**
   * api_url
   *
   * Generate a URL that points to a single action endpoint on our API
   * Each API request required a valid license number to be passed in.
   *
   * @param string $action
   *
   * @access private
   *
   * @return string URL
   */
  private function api_url($action) {
    $license_number = tu()->config['general']['license_number'];
    $license_number = empty($license_number) ? '0' : $license_number;
    $args           = compact('license_number', 'action');

    return add_query_arg($args, $this->api);
  }

  /**
   * _check
   *
   * - Fired on `pre_set_site_transient_update_plugins`
   * - If the plugin is out of date then add information about where WordPress
   *   can get the latest version from.
   *
   * @param object $transient
   *
   * @access private
   *
   * @return mixed Value.
   */
  public function _check($transient) {
    if (empty($transient->checked) || $this->is_up_to_date()) {
      return $transient;
    }

    $transient->response[tu()->get_slug()] = (object)array(
      'slug'        => tu()->get_slug(),
      'url'         => tu()->get_homepage(),
      'new_version' => $this->get_latest_version(),
      'package'     => $this->api_url('latest_download')
    );

    return $transient;
  }

  /**
   * _info
   *
   * - Fired on `plugins_api`
   * - If the slug is Train-Up's, then return the latest information
   *
   * @param boolean $false
   * @param array $action
   * @param object $args
   *
   * @access private
   *
   * @return boolean|object
   */
  public function _info($false, $action, $args) {
    if ($args->slug === tu()->get_slug()) {
      return $this->get_latest_info();
    }
    return false;
  }

  /**
   * get_latest_info
   *
   * - Get the latest information from the API, and cache it internally so we
   *   only have to make the request once.
   * - WordPress requires that the `sections` are an array, but this information
   *   is lost in the json_encode process, so cast it back.
   *
   * @access public
   *
   * @return object Information about the latest available version
   */
  public function get_latest_info() {
    if (is_null($this->latest_info)) {
      $response = wp_remote_get($this->api_url('latest_info'), array(
        'timeout'=> 60
      ));

      $this->latest_info = json_decode($response['body']);
      $this->latest_info->sections = (array)$this->latest_info->sections;
    }

    return $this->latest_info;
  }

  /**
   * get_latest_version
   *
   * Get the latest version from the API, and cache it internally so we only
   * have to make the request once.
   *
   * @access public
   *
   * @return integer|string The version number of the latest available version
   */
  public function get_latest_version() {
    if (is_null($this->latest_version)) {
      $response = wp_remote_get($this->api_url('latest_version'));
      $body     = wp_remote_retrieve_body($response);
      $this->latest_version = json_decode($body);
    }

    return $this->latest_version;
  }

  /**
   * is_out_of_date
   *
   * @access public
   *
   * @return boolean Whether or not Train-Up! is out of date
   */
  public function is_out_of_date() {
    return version_compare($this->get_latest_version(), tu()->version, '>');
  }

  /**
   * is_up_to_date
   *
   * @access public
   *
   * @return boolean Whether or not Train-Up! is up to date
   */
  public function is_up_to_date() {
    return !$this->is_out_of_date();
  }

}



