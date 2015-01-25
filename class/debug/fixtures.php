<?php

/**
 * Helps create sample data
 *
 * @package Train-Up!
 * @subpackage Fixtures
 */

namespace TU;

class Fixtures {

  /**
   * installed
   * 
   * @access public
   * @static
   *
   * @return boolean Whether or not the fixtures (sample data) are installed
   */
  public static function installed() {
    return (bool)get_option('tu_fixtures');
  }

  /**
   * install
   *
   * - Create sample data.
   * - This is probably the worst function I've ever written. Doing this section
   *   was an after thought, its a miracle it even works.
   * 
   * @access public
   * @static
   */
  public static function install() {
    if (self::installed()) {
      throw new \Exception(__('Fixtures already installed', 'trainup'));
    }

    $fixtures = array();
    $tests    = array();
    $trainees = array();
    $groups   = array();

    // Groups

    foreach (self::groups() as $title) {
      $group = Groups::factory(array(
        'post_title' => $title,
        'post_type'  => 'tu_group'
      ));
      $group->save();

      $fixtures[] = $group;
      $groups[]   = $group;
    }

    // Trainees

    for ($i = 1; $i < 27; $i++) {
      $info = self::user();

      if (isset($trainees[$info['user_email']])) continue;

      $trainee = Trainees::factory($info);
      $trainee->save();
      $trainees[$trainee->user_email] = $trainee;

      for ($n = 0; $n < rand(0, 3); $n++) {
        $group_id = $groups[array_rand($groups)]->ID;
        $trainee->add_to_group($group_id);
      }

      $fixtures[] = $trainee;
    }

    for ($i = 1; $i < 11; $i++) {
      $odd = $i % 2 === 1;

      // Levels

      $level = Levels::factory(array(
        'post_title'   => tu()->config['levels']['single'] . ' ' . $i,
        'post_type'    => 'tu_level',
        'menu_order'   => $i,
        'post_content' => tu()->config['levels']['default_content']
      ));
      $level->save();

      if ($odd) {
        $group_ids  = array();
        $max_groups = count($groups) / 2;
        for ($n = 0; $n < rand(3, $max_groups); $n++) {
          $group       = $groups[array_rand($groups)];
          $group_ids[] = $group->ID;
        }
        $level->set_group_ids($group_ids);
      }

      $fixtures[] = $level;

      // Tests

      $test = Tests::factory();
      $test->save(array(
        'post_title'   => $level->post_title,
        'post_type'    => 'tu_test',
        'menu_order'   => $i,
        'post_content' => tu()->config['tests']['default_content']
      ));
      $test->set_level_id($level->ID);
      $test->set_result_status('publish');

      $tests[] = $test;

      for ($m = 1; $m < 11; $m++) {
        $even = $m % 2 === 0;

        // Resources

        $resource = Resources::factory(array(
          'post_title' => tu()->config['resources']['single'] . ' ' . $m,
          'post_type'  => "tu_resource_{$level->ID}",
          'menu_order' => $m
        ));
        $resource->save();
        $resource->set_level_id($level->ID);

        // Questions

        $a = rand(1, 5);
        $b = rand(6, 10);
        $answer = $a + $b;

        $question = Questions::factory(array(
          'post_title'   => sprintf(__('Question %1$s', 'trainup'), $m),
          'post_type'    => "tu_question_{$test->ID}",
          'menu_order'   => $m,
          'post_content' => sprintf(__('What is %1$s + %2$s?', 'trainup'), $a, $b)
        ));
        $question->save();
        $question->set_test_id($test->ID);

        // Answers

        if ($even) {
          $answers = array(
            $answer,
            rand(1, 20),
            rand(1, 20)
          );
          shuffle($answers);
          $question->set_type('multiple');
          $question->save_multiple_answers($answers, $answer);
        } else {
          $question->set_type('single');
          $question->save_single_answer($answer, 'equal-to');
        }
      } 
    }

    // Results

    foreach ($trainees as $email => $trainee) {
      foreach ($tests as $test) {
        list($can_access_test) = $trainee->can_access_test($test);

        if (!$can_access_test) continue;

        $trainee->start_test($test);

        foreach ($test->questions as $question) {
          $correct_answer = $question->get_correct_answer();
          $answer = mt_rand(0, 3) === 0 ? $correct_answer : $answer;

          $trainee->save_temporary_answer_to_question($question, $answer);
        }

        $duration = $trainee->started_test($test->ID) + rand(60, 259200);
        update_user_meta($trainee->ID, "tu_finished_test_{$test->ID}", $duration);

        $trainee->finish_test($test);
      }
    }

    $fixture_cache = array();

    foreach ($fixtures as $fixture) {
      $fixture_cache[] = array(
        'ID'    => $fixture->ID,
        'class' => get_class($fixture)
      );
    }

    add_option('tu_fixtures', $fixture_cache);
  }

  /**
   * uninstall
   *
   * Loop through the cached fixtures and remove them. Only references to
   * Groups, Trainees and Levels are cached. This is because when deleting a
   * Level, its related Resources are deleted automatically. Also, the Level's
   * Test is deleted which automatically deletes its Questions.
   * 
   * @access public
   * @static
   */
  public static function uninstall() {
    global $wpdb;

    if (!self::installed()) {
      throw new \Exception(__('No fixtures to remove', 'trainup'));
    }

    $fixture_cache = get_option('tu_fixtures');

    foreach ($fixture_cache as $info) {
      $fixture = new $info['class']($info['ID']);
      $fixture->delete();
    }

    delete_option('tu_fixtures');
  }

  /**
   * forenames
   * 
   * @access private
   * @static
   *
   * @return array Mixed male and female forenames
   */
  private static function forenames() {
    return preg_split('/\s+/', '
      Andrew James Scott Richard Tim Simon Chris Nick Gareth Alan Adam
      Dave Mike Dan Chrisopher Rupert Neil Ben Matt John Phil Luke Tom
      Georgia Charlotte Tina Gill Michelle Emma Jen Lucy Hannah Laura
      Sarah Rebecca Fiona Claire Emily Jennifer Kim Katie Amanda Holly
    ', 0, PREG_SPLIT_NO_EMPTY);
  }

  /**
   * surnames
   * 
   * @access private
   * @static
   *
   * @return array Mixed male and female surnames
   */
  private static function surnames() {
    return preg_split('/\s+/', '
      Smith Jones Taylor Williams Brown Davies Evans Wilson Thomas Roberts
      Johnson Lewis Walker Robinson Wood White Jackson Green Cooper Miller
      Price Bell Collins Gray Fox Chapman Hunt Foster Dean Booth Barnes
      Dixon Grant Lane McDonald Brooks Webb Spencer Ward Phillips Cook
    ', 0, PREG_SPLIT_NO_EMPTY);
  }

  /**
   * user
   * 
   * @access private
   * @static
   *
   * @return array Some sample data that could represent a single WP user
   */
  private static function user() {
    $forenames  = self::forenames();
    $surnames   = self::surnames();
    $first_name = $forenames[array_rand($forenames)];
    $last_name  = $surnames[array_rand($surnames)];

    return array(
      'first_name' => $first_name,
      'last_name'  => $last_name,
      'user_email' => strtolower("{$first_name}@{$last_name}.com"),
      'user_login' => Users::generate_username($first_name, $last_name),
      'user_pass'  => 'Abc123'
    );
  }

  /**
   * groups
   * 
   * @access private
   * @static
   *
   * @return array Some sample group names
   */
  private static function groups() {
    $chars = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    $groups = array();
    foreach ($chars as $char) {
      $groups[] = tu()->config['groups']['single'] . ' ' . $char;
    }
    return $groups;
  }

}


