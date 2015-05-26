<?php

/**
 * The Level post type
 *
 * @package Train-Up!
 * @subpackage Levels
 */

namespace TU;

class Level_post_type extends Post_type {

  /**
   * $slug
   *
   * The base name for levels.
   * (Because Levels are not a dynamic post type, this is the same as $name)
   *
   * @var string
   *
   * @access public
   */
  public $slug = 'tu_level';

  /**
   * $name
   *
   * The name of this post type
   *
   * @var string
   *
   * @access public
   */
  public $name = 'tu_level';

  /**
   * set_options
   *
   * Set the options on this Level post type, so that it can be serialised.
   * These are the settings passed to WordPress' `register_post_type`
   *
   * @access protected
   */
  protected function set_options() {
    $levels = tu()->config['levels'];

    $uri = rawurldecode(
      sanitize_title_with_dashes(tu()->config['general']['main_slug'])
      . '/' .
      sanitize_title_with_dashes($levels['single'])
    );

    $this->options = array(
      'hierarchical'      => true,
      'public'            => true,
      'show_ui'           => true,
      'show_in_menu'      => 'tu_plugin',
      'show_in_admin_bar' => false,
      'map_meta_cap'      => true,
      'capability_type'   => $this->slug,
      'has_archive'       => false,
      'labels' => array(
        'name'          => $levels['plural'],
        'singular_name' => $levels['single'],
        'add_new_item'  => sprintf(__('Add a new %1$s', 'trainup'), $levels['single']),
        'edit_item'     => sprintf(__('Edit %1$s', 'trainup'), $levels['single']),
        'search_items'  => __('Search', 'trainup')
      ),
      'supports' => array(
        'title',
        'excerpt',
        'editor',
        'page-attributes',
        'thumbnail'
      ),
      'rewrite' => array(
        'slug' => $uri
      )
    );
  }

  /**
   * set_shortcodes
   *
   * Set the shortcodes on this Level post type, so that it can be serialised.
   *
   * @access protected
   */
  protected function set_shortcodes() {
    $_test      = simplify(tu()->config['tests']['single']);
    $_level     = simplify(tu()->config['levels']['single']);
    $_levels    = simplify(tu()->config['levels']['plural']);
    $_resources = simplify(tu()->config['resources']['plural']);

    $this->shortcodes = array(
      'level_title' => array(
        'shortcode'  => sprintf(__('%1$s_title', 'trainup'), $_level),
        'attributes' => array()
      ),
      'list_level_resources' => array(
        'shortcode'  => sprintf(__('list_%1$s_%2$s', 'trainup'), $_level, $_resources),
        'attributes' => array(
          'id' => null
        )
      ),
      'number_of_level_resources' => array(
        'shortcode'  => sprintf(__('number_of_%1$s_%2$s', 'trainup'), $_level, $_resources),
        'attributes' => array()
      ),
      'level_has_resources' => array(
        'shortcode'  => sprintf(__('%1$s_has_%2$s', 'trainup'), $_level, $_resources),
        'attributes' => array()
      ),
      '!level_has_resources' => array(
        'shortcode'  => sprintf(__('!%1$s_has_%2$s', 'trainup'), $_level, $_resources),
        'attributes' => array()
      ),
      'level_has_test' => array(
        'shortcode'  => sprintf(__('%1$s_has_%2$s', 'trainup'), $_level, $_test),
        'attributes' => array()
      ),
      '!level_has_test' => array(
        'shortcode'  => sprintf(__('!%1$s_has_%2$s', 'trainup'), $_level, $_test),
        'attributes' => array()
      ),
      'level_test_link' => array(
        'shortcode'  => sprintf(__('%1$s_%2$s_link', 'trainup'), $_level, $_test),
        'attributes' => array(
          'text' => sprintf(__('View %1$s', 'trainup'), $_test) . '&nbsp;&raquo;'
        )
      ),
      'list_sub_levels' => array(
        'shortcode'  => sprintf(__('list_sub_%1$s', 'trainup'), $_levels),
        'attributes' => array()
      ),
      'has_sub_levels' => array(
        'shortcode'  => sprintf(__('has_sub_%1$s', 'trainup'), $_levels),
        'attributes' => array()
      ),
      '!has_sub_levels' => array(
        'shortcode'  => sprintf(__('!has_sub_%1$s', 'trainup'), $_levels),
        'attributes' => array()
      ),
      'number_of_sub_levels' => array(
        'shortcode'  => sprintf(__('number_of_sub_%1$s', 'trainup'), $_levels),
        'attributes' => array()
      ),
      'level_test_results_table' => array(
        'shortcode'  => sprintf(__('%1$s_%2$s_results_table', 'trainup'), $_level, $_test),
        'attributes' => array(
          'limit'   => 10,
          'columns' => 'avatar, rank, user_name'
        )
      ),
      'resume_resources_link' => array(
        'shortcode' => sprintf(__('resume_%1$s_link', 'trainup'), $_resources),
        'attributes' => array(
          'text' => sprintf(__('Resume %1$s', 'trainup'), $_resources)
        )
      )
    );
  }

  /**
   * shortcode_number_of_level_resources
   *
   * - Callback for the 'number_of_level_resources' shortcode
   * - Return the number of Resources associated with the active level
   *
   * @access protected
   *
   * @return integer The number of resources the active level has.
   */
  protected function shortcode_number_of_level_resources($attributes, $content) {
    return count(tu()->level->resources);
  }

  /**
   * shortcode_number_sub_levels
   *
   * @access protected
   *
   * @return integer The number of children the active level has.
   */
  protected function shortcode_number_of_sub_levels($attributes, $content) {
    return count(tu()->level->children);
  }

  /**
   * shortcode_list_level_resources
   *
   * - Callback for the 'list_level_resources' shortcode
   * - Returns a HTML list of Resources associated with the active level,
   *   or optionally a specific level.
   *
   * @access protected
   *
   * @return string An ordered list of resources.
   */
  protected function shortcode_list_level_resources($attributes, $content) {
    $level_id = tu()->level->ID;

    if (isset($attributes['id'])) {
      $level_id = $attributes['id'];
    }

    return '<ol class="tu-list tu-list-resources">'.wp_list_pages(array(
      'sort_column' => 'menu_order',
      'sort_order'  => 'ASC',
      'echo'        => false,
      'title_li'    => '',
      'meta_key'    => 'tu_level_id',
      'meta_value'  => $level_id,
      'post_type'   => "tu_resource_{$level_id}",
      'walker'      => new Resource_walker
    )).'</ol>';
  }

  /**
   * shortcode_level_has_resources
   *
   * - Callback for the 'level_has_resources' shortcode
   * - Output the content wrapped by this shortcode if the active level
   *   does have some associated resources.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_level_has_resources($attributes, $content) {
    if (count(tu()->level->resources) > 0) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_not_level_has_resources
   *
   * - Callback for the '!level_has_resources' shortcode
   * - Output the content wrapped by this shortcode if the active level
   *   does not have any associated resources.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_not_level_has_resources($attributes, $content) {
    if (count(tu()->level->resources) < 1) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_level_title
   *
   * @access protected
   *
   * @return string The title of the active level.
   */
  protected function shortcode_level_title() {
    return tu()->level->post_title;
  }

  /**
   * shortcode_level_has_test
   *
   * - Callback for the 'level_has_test' shortcode
   * - Output the content wrapped by this shortcode if the active level
   *   does have an associated Test.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_level_has_test($attributes, $content) {
    if (tu()->level->test) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_not_level_has_test
   *
   * - Callback for the '!level_has_test' shortcode
   * - Output the content wrapped by this shortcode if the active level
   *   does not have an associated Test.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_not_level_has_test($attributes, $content) {
    if (!tu()->level->test) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_level_test_link
   *
   * - Callback for the 'level_test_link' shortcode
   * - Output a hyperlink to the active Level's Test.
   * - Optionally accept an attribute to allow the user to change the link text.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_level_test_link($attributes, $content) {
    $test = tu()->level->test;
    $href = $test ? $test->url : '';
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-test-link'>{$text}</a>";
  }

  /**
   * shortcode_list_sub_levels
   *
   * @access public
   *
   * @return string
   */
  public function shortcode_list_sub_levels() {
    return '<ol class="tu-list tu-list-sub-levels">'.wp_list_pages(array(
      'sort_column' => 'menu_order',
      'sort_order'  => 'ASC',
      'echo'        => false,
      'child_of'    => tu()->level->ID,
      'title_li'    => '',
      'post_type'   => 'tu_level',
      'walker'      => new Level_walker
    )).'</ol>';
  }

  /**
   * shortcode_has_sub_levels
   *
   * - Callback for the 'has_sub_levels' shortcode
   * - Output the content wrapped by this shortcode if the active level
   *   has children.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_has_sub_levels($attributes, $content) {
    if (count(tu()->level->children) > 0) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_not_has_sub_levels
   *
   * - Callback for the '!has_sub_levels' shortcode
   * - Output the content wrapped by this shortcode if the active level
   *   doesn't have any children.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_not_has_sub_levels($attributes, $content) {
    if (count(tu()->level->children) < 1) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_level_test_results_table
   *
   * - Callback for the 'level_test_results_table' shortcode
   * - Outputs a table of test result data for the active level's test.
   * - Order by percentage by default, because that allows us to show the `rank`
   *   column.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_level_test_results_table($attributes, $content) {
    if (tu()->level->test) {
      $archives = tu()->level->test->get_archives(array(
        'limit'    => $attributes['limit'],
        'order_by' => 'percentage',
        'order'    => 'DESC'
      ));

      return new View(tu()->get_path('/view/frontend/results/table'), array(
        'archives' => $archives,
        'columns'  => array_flip(array_map('trim', explode(',', $attributes['columns'])))
      ));
    }
  }

  /**
   * shortcode_resume_resources_link
   *
   * - Callback for the 'resume_resources_link' shortcode
   * - Outputs a hyperlink to the next unvisited resource in the level.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_resume_resources_link($attributes, $content) {
    $resources = tu()->level->resources;
    $next      = null;

    foreach ($resources as $resource) {
      if (!tu()->user->has_visited_resource($resource->ID)) {
        $next = $resource;
        break;
      }
    }

    $href = $next ? $next->url : ($resources ? $resources[0]->url : '#');
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-resume-resources-link'>{$text}</a>";
  }

}



