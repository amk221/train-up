<?php

/**
 * Class to represent an Group manager (Wraps a WP_User)
 *
 * @package Train-Up!
 * @subpackage Group managers
 */

namespace TU;

class Group_manager extends User {

  /**
   * save
   *
   * When saving a Group manager User, always force its role
   * 
   * @param array $data
   *
   * @access public
   */
  public function save($data = array()) {
    parent::save(array_merge($data, array(
      'role' => 'tu_group_manager'
    )));
  }

  /**
   * get_access_result_ids
   *
   * - Returns IDs of Results that this Group manager can access.
   * - These will be Results whose Trainee is within a Group that this Group
   *   manager manages. 
   * 
   * @access public
   *
   * @return array
   */
  public function get_access_result_ids() {
    global $wpdb;

    $cache_grp  = 'tu_group_manager_access_result_ids';
    $cache_id   = $this->ID;
    $result_ids = wp_cache_get($cache_id, $cache_grp, false, $found) ?: array();

    if ($found) return $result_ids;

    if (count($this->access_trainee_ids) >= 1) {
      $sql = "
        SELECT DISTINCT ID
        FROM   {$wpdb->posts} p
        JOIN   {$wpdb->postmeta} m
        ON     p.ID = m.post_id
        WHERE  p.post_type REGEXP('^tu_result_[0-9]+')
        AND    m.meta_key = 'tu_user_id'
        AND    m.meta_value IN (".join(',', $this->access_trainee_ids).")
      ";

      foreach ($wpdb->get_results($sql) as $row) {
        $result_ids[] = $row->ID;
      }
    }

    wp_cache_set($cache_id, $result_ids, $cache_grp);

    return $result_ids;
  }

  /**
   * get_access_trainee_ids
   *
   * - Returns IDs of users that this Group manager has access to.
   * - They will be IDs of Trainees who are in Groups that this Group
   *   manager manages, or un-grouped trainees.
   * 
   * @access public
   *
   * @return array
   */
  public function get_access_trainee_ids() {
    $cache_grp   = 'tu_group_manager_access_trainee_ids';
    $cache_id    = $this->ID;
    $trainee_ids = wp_cache_get($cache_id, $cache_grp, false, $found);

    if ($found) return $trainee_ids;

    $ungrouped_trainee_ids = Trainees::get_ungrouped_ids();
    $grouped_trainees_ids  = Groups::get_trainee_ids($this->get_group_ids());

    $trainee_ids = array_unique(array_merge(
      $ungrouped_trainee_ids, $grouped_trainees_ids
    ));

    wp_cache_set($cache_id, $trainee_ids, $cache_grp);

    return $trainee_ids;
  }

  /**
   * can_access_trainee
   *
   * - Returns whether or not this Group manager has access to this Trainee by
   *   way of its Groups.
   * - If the Trainee is in one of the Groups that this Group manager manages
   *   then all is good.
   * 
   * @param object $trainee
   *
   * @access public
   *
   * @return array
   */
  public function can_access_trainee($trainee) {
    $error = '';
    $ok    = in_array($trainee->ID, $this->get_access_trainee_ids());
    
    if (!$ok) {
      $error = __('Access denied', 'trainup');
    }

    $result = array($ok, $error);
    
    $result = apply_filters(
      'tu_group_manager_can_access_trainee',
      $result, $this, $trainee
    );

    return $result;
  }

  /**
   * can_access_group_manager
   *
   * - Returns whether or not this Group manager 'has access' to another 
   *   Group manager. By this, we mean they both belong to a common group, 
   *   and therefore can see the same content.
   * - Should probably refactor this to use array_intersect
   * 
   * @param mixed $group_manager
   *
   * @access public
   *
   * @return array
   */
  public function can_access_group_manager($group_manager) {
    $error = '';
    $ok    = false;

    foreach ($this->group_ids as $my_group_id) {
      if ($ok) return;

      foreach ($group_manager->group_ids as $their_group_id) {
        if ($my_group_id == $their_group_id) {
          $ok = true;
          return;
        }
      }
    }

    if (!$ok) {
      $error = __('Access denied', 'trainup');
    }

    $result = array($ok, $error);

    $result = apply_filters(
      'tu_group_manager_can_access_group_manager',
      $result, $this, $group_manager
    );

    return $result;
  }

}


 
