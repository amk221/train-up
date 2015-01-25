<?php

/**
 * Class to represent an Trainee (Wraps a WP_User)
 *
 * @package Train-Up!
 * @subpackage Trainees
 */

namespace TU;

class Trainee extends User {

  /**
   * save
   *
   * When saving a Trainee User, always force its role
   * 
   * @param array $data
   *
   * @access public
   *
   * @return array
   */
  public function save($data = array()) {
    parent::save(array_merge($data, array(
      'role' => 'tu_trainee'
    )));
  }

  /**
   * get_group_managers
   * 
   * @access public
   *
   * @return array Group managers who manage this Trainee. i.e. They are 
   * managers of a Group that this Trainee is in.
   */
  public function get_group_managers() {
    return Group_managers::find_all(array(
      'meta_query' => array(
        array(
          'key'     => 'tu_group',
          'value'   => $this->get_group_ids(),
          'compare' => 'IN'
        )
      )
    ));
  }

}


 
