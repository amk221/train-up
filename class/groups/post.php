<?php

/**
 * A class to represent a single Group WP_Post
 *
 * @package Train-Up!
 * @subpackage Groups
 */

namespace TU;

class Group extends Post {

  /**
   * get_trainees
   *
   * @access public
   *
   * @return array Trainee user objects who are in this Group.
   */
  public function get_trainees() {
    return Trainees::find_all(array(
      'meta_key'   => 'tu_group',
      'meta_value' => $this->ID
    ));
  }

  /**
   * get_trainee_ids
   * 
   * @access public
   *
   * @return array The IDs of Trainees who are in this Group
   */
  public function get_trainee_ids() {
    return Groups::get_trainee_ids(array($this->ID));
  }

  /**
   * get_levels
   * 
   * @access public
   *
   * @return array The Level posts associated with this Group
   */
  public function get_levels() {
    return Levels::find_all(array(
      'meta_key'   => 'tu_group',
      'meta_value' => $this->ID
    ));
  }

  /**
   * get_description
   *
   * Get the description of this Group, optionally accept a number of 
   * characters to limit it to.
   * 
   * @param integer $limit
   *
   * @access public
   *
   * @return string
   */
  public function get_description($limit = 0) {
    $desc = strip_shortcodes(strip_tags($this->post_content));

    if ($limit > 0 && strlen($desc) >= $limit) {
      $desc = rtrim(substr($desc, 0, $limit)).'...';
    }

    return $desc ?: '';
  }

  /**
   * add_user
   *
   * Accept a user ID, load that user and add this Group to that user's
   * list of allowed groups.
   * 
   * @param integer $user_id
   *
   * @access public
   */
  public function add_user($user_id) {
    Users::factory($user_id)->add_to_group($this->ID);
  }

  /**
   * remove_user
   *
   * Accept a user ID, load that user and remove this Group from that user's
   * list of allowed groups.
   * 
   * @param integer $user_id
   *
   * @access public
   */
  public function remove_user($user_id) {
    Users::factory($user_id)->remove_from_group($this->ID);
  }

  /**
   * add_users
   * 
   * @param integer $user_ids to associate with this Group
   *
   * @access public
   */
  public function add_users($user_ids) {
    foreach ($user_ids as $user_id) {
      $this->add_user($user_id);
    }
  }

  /**
   * remove_users
   * 
   * Accept 
   *
   * @param integer $user_ids to disassociate them from this Group.
   *
   * @access public
   */
  public function remove_users($user_ids) {
    foreach ($user_ids as $user_id) {
      $this->remove_user($user_id);
    }
  }

  /**
   * set_users
   *
   * Accept a bunch of user IDs, use these as the definitive set of users
   * with which to associate with this group.
   * 
   * @param integer $user_ids
   *
   * @access public
   */
  public function set_users($user_ids) {
    $this->remove_all_users();
    $this->add_users($user_ids);
  }

  /**
   * set_trainees
   *
   * - Removes all trainees in this Group
   * - Re-adds a whole new set of trainees.
   *
   * @see set_users
   *
   * @access public
   */
  public function set_trainees($trainee_ids) {
    $this->remove_all_trainees();
    $this->add_users($trainee_ids);
  }

  /**
   * remove_all_users
   *
   * Remove the meta information that ties a user to this group.
   * 
   * @access private
   */
  private function remove_all_users() {
    global $wpdb;

    $wpdb->query("
      DELETE FROM {$wpdb->usermeta}
      WHERE  meta_key   = 'tu_group'
      AND    meta_value = {$this->ID}
    ");
  }

  /**
   * remove_all_trainees
   *
   * Remove the meta information that ties a trainee to this group.
   * 
   * @access private
   */
  private function remove_all_trainees() {
    global $wpdb;

    $umeta_ids = array();

    $sql = "
      SELECT g.umeta_id FROM {$wpdb->usermeta} g
      JOIN   {$wpdb->usermeta} r
      ON     g.user_id    = r.user_id
      WHERE  r.meta_key   = '{$wpdb->prefix}capabilities'
      AND    r.meta_value LIKE '%trainee%'
      AND    g.meta_key   = 'tu_group'
      AND    g.meta_value = {$this->ID}
    ";

    $refs = $wpdb->get_results($sql);

    if (count($refs) < 1) {
      return;
    }

    foreach ($refs as $row) {
      $umeta_ids[] = $row->umeta_id;
    }

    $wpdb->query("
      DELETE FROM {$wpdb->usermeta}
      WHERE umeta_id IN (".join(',', $umeta_ids).")
    ");
  }

  /**
   * get_colour
   * 
   * @access public
   *
   * @return string The hex code colour for this Group
   */
  public function get_colour() {
    return get_post_meta($this->ID, 'tu_colour', true) ?: '#ccc';
  }

  /**
   * set_colour
   *
   * Set the hex code colour for this Group
   * 
   * @param string $colour
   *
   * @access public
   */
  public function set_colour($colour = '#ccc') {
    update_post_meta($this->ID, 'tu_colour', $colour);
  }

  /**
   * delete
   *
   * Disassociate users who are in this Group from this Group
   *
   * @param boolean $hard Delete the actual post
   * 
   * @access public
   */
  public function delete($hard = true) {
    foreach ($this->trainees as $trainee) {
      $trainee->remove_from_group($this->ID);
    }

    if ($hard) {
      parent::delete();
    }
  }
  
}


 
