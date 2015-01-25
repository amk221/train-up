<?php

/**
 * Renders a widget on the WordPress admin Dashboard page
 *
 * @package Train-Up!
 * @subpackage Dashboard
 */

namespace TU;

class Dashboard_widgets {

  /**
   * __construct
   *
   * When the WordPress Dashboard is set up and ready, render our widget
   * 
   * @access public
   */
  public function __construct() {
    add_action('wp_dashboard_setup', array($this, '_wp_dashboard_setup'));
  }

  /**
   * _wp_dashboard_setup
   *
   * - Fired on `wp_dashboard_setup` when the dashboard is ready
   * - Add a Dashboard widget and render it.
   * 
   * @access private
   */
  public function _wp_dashboard_setup() {
    wp_add_dashboard_widget('tu_recent_activity_widget',
      sprintf(__('%1$s recent activity', 'trainup'), tu()->get_name()),
      array($this, '_recent_activity_widget')
    );
  }

  /**
   * _recent_activity_widget
   *
   * - Fired on `wp_dashboard_setup` inside the Dashboard widget
   * - Render the latest test results from the archive.
   * 
   * @access private
   */
  public function _recent_activity_widget() {
    echo new View(tu()->get_path('/view/backend/widgets/recent_activity'), array(
      '_test'    => tu()->config['tests']['single'],
      '_trainee' => tu()->config['trainees']['single'],
      'archives' => $this->get_latest_from_archive()
    ));
  }

  /**
   * get_latest_from_archive
   * 
   * @access protected
   *
   * @return array The latest test results from the archive, but make sure we
   * only return results of Trainees who the active Group Manager is allowed to
   * see.
   */
  protected function get_latest_from_archive() {
    global $wpdb;

    $archives    = array();
    $trainee_ids = array();

    if (tu()->user->is_group_manager()) {
      $trainee_ids = filter_ids(tu()->group_manager->access_trainee_ids, $trainee_ids);
    }

    $trainee_filter = (count($trainee_ids) > 0)
      ? "WHERE user_id IN (".join(',', $trainee_ids).")"
      : '';

    $sql = "
      SELECT user_id, test_id
      FROM (
        SELECT user_id, test_id, date_submitted
        FROM {$wpdb->prefix}tu_archive
        {$trainee_filter}
        ORDER BY date_submitted DESC
      ) r
      GROUP BY user_id
      ORDER BY date_submitted DESC
      LIMIT 5
    ";

    foreach ($wpdb->get_results($sql) as $row) {
      $archives[] = Users::factory($row->user_id)->get_archive($row->test_id);
    }

    return $archives;
  }

}

