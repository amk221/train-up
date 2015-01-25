<?php

/**
 * A bunch of WordPress walker classes
 *
 * @package Train-Up!
 */

namespace TU;

class Page_walker extends \Walker_Page {

  /**
   * start_el
   *
   * When a Page is walked, wrap it in a span (useful for styling).
   * 
   * @param string  $output
   * @param object  $page
   * @param integer $depth
   * @param array   $args
   * @param integer $current_page
   *
   * @access public
   */
  public function start_el(&$output, $page, $depth = 0, $args = array(), $current_page = 0) {
    $args['link_before'] .= '<span class="tu-page">';
    $args['link_after']  .= '</span>';

    parent::start_el($output, $page, $depth, $args, $current_page);
  }

}

class Level_walker extends Page_walker {}
 
class Question_walker extends \Walker_Page {

  /**
   * start_el
   *
   * - When a Question is walked, wrap it in a span (useful for styling), and
   *   append an extract of the question.
   * - Also add a class that specifies whether or not the current trainee
   *   has attempted to answer that question.
   * - Allow developers to filter how the walker starts elements and let them
   *   easily hide a list item by setting show to false.
   * 
   * @param string  $output
   * @param object  $page
   * @param integer $depth
   * @param array   $args
   * @param integer $current_page
   *
   * @access public
   */
  public function start_el(&$output, $page, $depth = 0, $args = array(), $current_page = 0) {
    $show     = true;
    $question = Questions::factory($page->ID);
    $excerpt  = $question->get_title(true, 50);
    $answered = (int)tu()->user->has_answered_question($question);

    $args['link_before'] .= "<span class='tu-question tu-answered-{$answered}'>";
    $args['link_before'] .= '  <span class="tu-question-title">';
    $args['link_after']  .= "  </span>";
    $args['link_after']  .= "  <em class='tu-question-excerpt'>{$excerpt}</em>";
    $args['link_after']  .= "</span>";

    extract(apply_filters(
      'tu_question_walker_start_el',
      compact('output', 'page', 'depth', 'args', 'current_page', 'show')
    ));

    if ($show) {
      parent::start_el($output, $page, $depth, $args, $current_page);
    }
  }

}


class Resource_walker extends \Walker_Page {

  /**
   * start_el
   * 
   * - When a Resource is walked, wrap it in a span (useful for styling).
   * - Also add a class that specifies whether or not the current trainee
   *   has accessed the Resource.
   * - Also add a class that specifies whether or not the current trainee
   *   has access to the Resource via its schedule
   * - Allow developers to filter how the walker starts elements and let them
   *   easily hide a list item by setting show to false.
   *
   * @param string  $output
   * @param object  $page
   * @param integer $depth
   * @param array   $args
   * @param integer $current_page
   *
   * @access public
   */
  public function start_el(&$output, $page, $depth = 0, $args = array(), $current_page = 0) {
    $show        = true;
    $resource    = Resources::factory($page);
    $visited     = (int)tu()->user->has_visited_resource($page->ID);
    $schedule_ok = (int)tu()->user->resource_schedule_ok($resource);

    $class =  'tu-resource';
    $class .= " tu-visited-{$visited}";
    $class .= " tu-schedule-{$schedule_ok}";

    $args['link_before'] .= "<span class='{$class}'>";
    $args['link_after']  .= '</span>';

    extract(apply_filters(
      'tu_resource_walker_start_el',
      compact('output', 'page', 'depth', 'args', 'current_page', 'show')
    ));

    if ($show) {
      parent::start_el($output, $page, $depth, $args, $current_page);
    }
  }

}


class Result_walker extends \Walker_Page {

  /**
   * start_el
   *
   * When a Level is walked, find out if the active user has taken the Test
   * associated with it (if there is one). Then, link to their test result.
   * 
   * @param string  $output
   * @param object  $page
   * @param integer $depth
   * @param array   $args
   * @param integer $current_page
   *
   * @access public
   */
  public function start_el(&$output, $page, $depth = 0, $args = array(), $current_page = 0) {
    $level  = Levels::factory($page);
    $test   = $level->test;
    $result = $test ? tu()->user->get_result($test->ID) : null;

    if ($result) {
      $args['link_before'] .= '<span class="tu-result">';
      $args['link_after']  .= '</span>';
      $result->post_title   = $level->post_title;

      parent::start_el($output, $result, $depth, $args, $current_page);
    } else {
      $output .= "<li class='tu-no-result'>{$level->post_title}";
    }
  }

}

