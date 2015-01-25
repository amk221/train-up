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
  private $api = 'https://raw.githubusercontent.com/amk221/train-up/master/';

  /**
   * $download_url
   *
   * The URL that provides a zip containing the latest version
   *
   * @var string
   *
   * @access private
   */
  private $download_url = 'https://github.com/amk221/train-up/archive/master.zip';

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
   * $latest_changes
   *
   * Cached information about the latest changes to Train-Up!
   *
   * @var object
   *
   * @access private
   */
  private $latest_changes = null;

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
    if (!tu()->config['general']['auto_update']) {
      return;
    }

    // set_site_transient('update_plugins', '');

    add_filter('pre_set_site_transient_update_plugins', array($this, '_check'));
    add_filter('plugins_api', array($this, '_info'), 10, 3);
  }

  /**
   * api_url
   *
   * Generate a URL that points to a file on github.
   *
   * @param string $path
   *
   * @access private
   *
   * @return string URL
   */
  private function api_url($path) {
    return $this->api . $path;
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
      'package'     => $this->download_url
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
      return (object)array(
        'name'          => tu()->get_name(),
        'download_link' => $this->download_url,
        'slug'          => tu()->get_slug(),
        'new_version'   => $this->get_latest_version(),
        'requires'      => '3.5.1',
        'sections'      => array(
          'description' => $this->get_latest_changes()
        )
      );
    }
    return false;
  }

  /**
   * get_latest_changes
   *
   * Get the changes from the master changelog on github. Memoise it.
   *
   * @access public
   *
   * @return object Information about the latest available version
   */
  public function get_latest_changes() {
    if (is_null($this->latest_changes)) {
      $response  = wp_remote_get($this->api_url('CHANGELOG.md'));
      $body      = wp_remote_retrieve_body($response);
      $delimiter = '/\d{4}-\d{2}-\d{2} - version \d+\.\d+\.\d+/';
      $chunks    = preg_split($delimiter, $body, null, PREG_SPLIT_NO_EMPTY);

      $this->latest_changes = isset($chunks[0]) ? $chunks[0] : null;
    }

    return $this->latest_changes;
  }

  /**
   * get_latest_version
   *
   * Determine the latest version by checking on github what is in master.
   * Memoise the value.
   *
   * @access public
   *
   * @return integer|string The version number of the latest available version
   */
  public function get_latest_version() {
    if (is_null($this->latest_version)) {
      $response = wp_remote_get($this->api_url('index.php'));
      $body     = wp_remote_retrieve_body($response);

      preg_match('/Version: (\d+\.\d+\.\d+)/', $body, $matches);

      $this->latest_version = isset($matches[1]) ? $matches[1] : null;
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



