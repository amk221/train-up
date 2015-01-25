<?php

/**
 * The Page post type
 *
 * @package Train-Up!
 * @subpackage Pages
 */

namespace TU;

class Page_post_type extends Post_type {

  /**
   * $slug
   *
   * The base name for pages.
   * (Because Pages are not a dynamic post type, this is the same as $name)
   *
   * @var string
   *
   * @access public
   */
  public $slug = 'tu_page';

  /**
   * $name
   *
   * The name of this post type
   *
   * @var string
   *
   * @access public
   */
  public $name = 'tu_page';

  /**
   * set_options
   *
   * Set the options on this Page post type, so that it can be serialised.
   * These are the settings passed to WordPress' `register_post_type`
   * 
   * @access protected
   */
  protected function set_options() {
    $uri = rawurldecode(
      sanitize_title_with_dashes(tu()->config['general']['main_slug'])
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
        'name'          => __('Pages', 'trainup'),
        'singular_name' => __('Page', 'trainup'),
        'add_new_item'  => __('Add a new page', 'trainup'),
        'edit_item'     => __('Edit page', 'trainup'),
        'search_items'  => __('Search', 'trainup')
      ),
      'supports' => array(
        'title',
        'editor'
      ),
      'rewrite' => array(
        'slug' => $uri
      )
    );
  }

  /**
   * set_shortcodes
   *
   * Set the shortcodes on this Page post type, so that it can be serialised.
   * 
   * @access protected
   */
  protected function set_shortcodes() {
    $_trainee = simplify(tu()->config['trainees']['single']);
    $_levels  = simplify(tu()->config['levels']['plural']);

    $this->shortcodes = array(
      'my_account_link' => array(
        'shortcode'  => 'my_account_link',
        'attributes' => array('text' => __('My account', 'trainup'))
      ),
      'edit_my_details_link' => array(
        'shortcode'  => 'edit_my_details_link',
        'attributes' => array('text' => __('Edit my details', 'trainup'))
      ),
      'my_results_link' => array(
        'shortcode'  => 'my_results_link',
        'attributes' => array('text' => __('View my results', 'trainup'))
      ),
      'login_link' => array(
        'shortcode'  => __('login_link', 'trainup'),
        'attributes' => array(
          'text'      => __('Login', 'trainup'),
          'return_to' => ''
        )
      ),
      'logout_link' => array(
        'shortcode'  => __('logout_link', 'trainup'),
        'attributes' => array(
          'text'      => __('Logout', 'trainup'),
          'return_to' => ''
        )
      ),
      'trainee_has_levels' => array(
        'shortcode'  => sprintf(__('%1$s_has_%2$s', 'trainup'), $_trainee, $_levels),
        'attributes' => array()
      ),
      '!trainee_has_levels' => array(
        'shortcode'  => sprintf(__('!%1$s_has_%2$s', 'trainup'), $_trainee, $_levels),
        'attributes' => array()
      ),
      'list_trainee_levels' => array(
        'shortcode'  => sprintf(__('list_%1$s_levels', 'trainup'), $_trainee),
        'attributes' => array()
      ),
      'trainee_has_results' => array(
        'shortcode'  => sprintf(__('%1$s_has_results', 'trainup'), $_trainee),
        'attributes' => array()
      ),
      '!trainee_has_results' => array(
        'shortcode'  => sprintf(__('!%1$s_has_results', 'trainup'), $_trainee),
        'attributes' => array()
      ),
      'list_trainee_results' => array(
        'shortcode'  => sprintf(__('list_%1$s_results', 'trainup'), $_trainee),
        'attributes' => array()
      ),
      'results_table' => array(
        'shortcode'  => __('results_table', 'trainup'),
        'attributes' => array(
          'limit'   => 10,
          'columns' => 'avatar, rank, user_name'
        )
      ),
      'trainee_pass_percentage' => array(
        'shortcode' => sprintf(__('%1$s_pass_percentage'), $_trainee),
        'attributes' => array(
          'level_id' => null
        )
      )
    );
  }

  /**
   * shortcode_my_account_link
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string A hyperlink to the My Account page
   */
  protected function shortcode_my_account_link($attributes, $content) {
    $href = Pages::factory('My_account')->url;
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-my-account-link'>{$text}</a>";
  }

  /**
   * shortcode_edit_my_details_link
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string A hyperlink to the Edit My Details page
   */
  protected function shortcode_edit_my_details_link($attributes, $content) {
    $href = Pages::factory('Edit_my_details')->url;
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-edit-my-details-link'>{$text}</a>";
  }

  /**
   * shortcode_my_results_link
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string A hyperlink to the My Results page
   */
  protected function shortcode_my_results_link($attributes, $content) {
    $href = Pages::factory('My_results')->url;
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-my-results-link'>{$text}</a>";
  }

 /**
   * shortcode_login_link
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string A hyperlink to the Login page
   */
  protected function shortcode_login_link($attributes, $content) {
    $href = login_url($attributes['return_to']);
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-login-link'>{$text}</a>";
  }

  /**
   * shortcode_logout_link
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string A hyperlink to the Logout page
   */
  protected function shortcode_logout_link($attributes, $content) {
    $href = logout_url($attributes['return_to']);
    $text = $attributes['text'];

    return "<a href='{$href}' class='tu-logout-link'>{$text}</a>";
  }

  /**
   * shortcode_trainee_has_levels
   *
   * - Callback for the 'trainee_has_levels' shortcode
   * - Output the content wrapped by this shortcode if the currrent Trainee
   *   has access to some Levels.
   * 
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_trainee_has_levels($attributes, $content) {
    if (count(tu()->user->access_level_ids) > 0) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_not_trainee_has_levels
   *
   * - Callback for the '!trainee_has_levels' shortcode
   * - Output the content wrapped by this shortcode if the current Trainee
   *   does not have access to any Levels.
   * 
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_not_trainee_has_levels($attributes, $content) {
    if (count(tu()->user->access_level_ids) < 1) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_list_trainee_levels
   * 
   * @access protected
   *
   * @return string An ordered list of Levels that the current Trainee can
   * access.
   */
  protected function shortcode_list_trainee_levels() {
    $level_ids = tu()->user->access_level_ids;

    if (!tu()->user->is_trainee() || count($level_ids) > 0) {
      return '<ol class="tu-list tu-list-levels">'.wp_list_pages(array(
        'sort_column' => 'menu_order',
        'sort_order'  => 'ASC',
        'echo'        => false,
        'title_li'    => '',
        'post_type'   => 'tu_level',
        'include'     => join(',', $level_ids),
        'walker'      => new Level_walker
      )).'</ol>';
    }
  }

  /**
   * shortcode_trainee_has_results
   *
   * - Callback for the 'trainee_has_results' shortcode
   * - Output the content wrapped by this shortcode if the currrent Trainee
   *   has taken some Tests and Results have been computed for them.
   * 
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_trainee_has_results($attributes, $content) {
    if (count(tu()->user->results) > 0) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_not_trainee_has_results
   *
   * - Callback for the '!trainee_has_results' shortcode
   * - Output the content wrapped by this shortcode if the current Trainee
   *   has not taken any Tests or not Results have yet been created for them.
   * 
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string|null
   */
  protected function shortcode_not_trainee_has_results($attributes, $content) {
    if (count(tu()->user->results) < 1) {
      return do_shortcode($content);
    }
  }

  /**
   * shortcode_list_trainee_results
   *
   * - Callback for the 'list_trainee_results' shortcode
   * - List all the levels the active Trainee can access, and if they have
   *   that level has a test, and they've taken the test then produce a link
   *   to that result page.
   * - This is a pretty expensive function, it'll probably produce loads of
   *   select statements.
   * 
   * @access protected
   *
   * @return string
   */
  protected function shortcode_list_trainee_results() {
    $level_ids = tu()->user->access_level_ids;

    if (!tu()->user->is_trainee() || count($level_ids) > 0) {
      return '<ol class="tu-list tu-list-results">'.wp_list_pages(array(
        'sort_column' => 'menu_order',
        'sort_order'  => 'ASC',
        'echo'        => false,
        'title_li'    => '',
        'post_type'   => 'tu_level',
        'include'     => join(',', $level_ids),
        'walker'      => new Result_walker
      )).'</ol>';
    }
  }

  /**
   * _list_trainee_result
   *
   * Callback fired when a list item is to be rendered containing a test Result.
   * Instead of printing out the Result post title (which would be that of the
   * user's name who the Result is for, use the Test title).
   * 
   * @param mixed $result
   *
   * @access private
   *
   * @return string a List item
   */
  public function _list_trainee_result($result) {
    return "
      <span class='tu-result'>
        <a href='{$result->url}'>{$result->test->post_title}</a>
      </span>
    ";
  }

  /**
   * shortcode_results_table
   *
   * - Callback for the 'results_table' shortcode
   * - Outputs a table of overall test result data
   * - Order by percentage by default because that allows us to show the `rank` col.
   * - NOTE: Most of the columns retreived from the archive will be of no use
   *   e.g. date_submitted and grade. These will not be representitive of the
   *   Trainee's *overall* grade. We only sum their marks.
   * 
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return string
   */
  protected function shortcode_results_table($attributes, $content) {
    $archives = Results::archives(array(
      'limit'    => $attributes['limit'],
      'order_by' => 'percentage',
      'order'    => 'DESC'
    ));

    return new View(tu()->get_path('/view/frontend/results/table'), array(
      'archives' => $archives,
      'columns'  => array_flip(array_map('trim', explode(',', $attributes['columns'])))
    ));
  }

  /**
   * shortcode_trainee_pass_percentage
   *
   * Returns the percentage of levels completed (test's passed)
   * Optionally access a level ID to get the percentage of sub levels complete.
   *
   * @param array $attributes
   * @param string $content
   *
   * @access protected
   *
   * @return integer;
   */
  protected function shortcode_trainee_pass_percentage($attributes, $content) {
    if (isset($attributes['level_id'])) {
      $level = Levels::factory($attributes['level_id']);
      return tu()->user->get_pass_percentage($level);
    } else {
      return tu()->user->get_pass_percentage();
    }
  }

}


 
