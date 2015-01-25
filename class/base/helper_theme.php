<?php

/**
 * Helper class for rendering the front end / working with the theme
 *
 * @package Train-Up!
 */

namespace TU;

class Theme_helper {

  /**
   * __construct
   *
   * When the theme helper is instantiated, listen out for some WordPress hooks
   * that help us set up & alter the theme for the training section of the site.
   * 
   * @access public
   */
  public function __construct() {
    add_action('wp', array($this, '_handle_hiding_of_admin_bar'));
    add_action('wp_enqueue_scripts', array($this, '_add_assets'));
    add_action('wp_print_styles', array($this, '_handle_printing_of_stylesheets'));
    add_filter('template_include', array($this, '_decide_template'));
    add_action('pre_comment_on_post', array($this, '_pre_comment_on_post'));
  }

  /**
   * _add_assets
   *
   * - Fired on `wp_enqueue_scripts`
   * - If the user is in the training section of the website add the scripts
   *   and styles required to run the plugin.
   * 
   * @access private
   */
  public function _add_assets() {
    if (!tu()->in_frontend()) return;

    wp_enqueue_style('tu_frontend');
    wp_enqueue_script('tu_frontend');
  }
  
  /**
   * localised_js
   *
   * - Returns a hash of variables with which JavaScript files associated to the
   *   theme helper can use. Basically, this is the frontend JS namespace (TU).
   *   For the backend namespace (also TU), see plugin.php
   * - Filterable so that developers can customise the TU JS namespace.
   * 
   * @access public
   * @static
   *
   * @return array
   */
  public static function localised_js() {
    $post = isset(tu()->post) ? tu()->post : null;
    $next = $post ? $post->get_next(true)  : null;
    $prev = $post ? $post->get_prev()      : null;

    $js = array(
      'ajaxUrl'        => admin_url('admin-ajax.php'),
      'activePostId'   => $post ? $post->ID         : null,
      'activePostType' => $post ? $post->post_type  : null,
      'nextPostUrl'    => $next ? $next->url        : null,
      'prevPostUrl'    => $prev ? $prev->url        : null
    );

    $result = apply_filters('tu_js_namespace', $js);

    return $result;
  }

  /**
   * _handle_printing_of_stylesheets
   *
   * If the user is in the training section of the website, and the admin has
   * opted to use the built in styles then dequeue all styles except Train-Up's
   * 
   * @access private
   */
  public function _handle_printing_of_stylesheets() {
    global $wp_styles;

    if (!tu()->in_frontend()) return;

    $include = tu()->config['general']['include_theme_css'];
    $retain  = array('admin-bar');

    foreach ($wp_styles->queue as $i => $stylesheet) {
      $ours = preg_match('/^tu_/', $stylesheet);
      $remove = (
        ( ($include && !$ours) || (!$include && $ours) ) &&
        !in_array($stylesheet, $retain)
      );

      if ($remove) {
        unset($wp_styles->queue[$i]);
      }
    }
  }

  /**
   * _handle_hiding_of_admin_bar
   *
   * - Fired on `wp`
   * - If the current user is a trainee, then remove the admin bar, and its
   *   associated styles. They don't have any privileges that would make
   *   it useful to them anyway...
   * 
   * @access private
   */
  public function _handle_hiding_of_admin_bar() {
    if (tu()->in_frontend() && current_user_can('tu_trainee')) {
      add_filter('show_admin_bar', '__return_false');
      remove_action('wp_head', 'wp_admin_bar_header');
      remove_action('wp_head', '_admin_bar_bump_cb');
      wp_dequeue_style('admin-bar');
    }
  }

  /**
   * _decide_template
   * 
   * - Fired on `template_include`
   * - The template that is going to be used is passed in, if the user is in 
   *   the training section of the site, then we can alter this template
   * - Inspect the active post (be it a Level, Resource, Test, Question etc..)
   *   and use the template specified by that object.
   * - OR, if the user has provided their own template within their theme folder
   *   then that one gets priority.
   * - Note: We don't use WordPress' built in template hierarchy because we have
   *   dynamic custom post types, so it would be a pain to have a template file
   *   for each one.
   *
   * @param string $template
   *
   * @access private
   *
   * @return string The altered template
   */
  public function _decide_template($template) {
    if (!tu()->in_frontend()) return $template;

    $filename  = tu()->post->template_file;
    $default   = tu()->get_path("/view/frontend/theme/{$filename}.php");
    $default   = file_exists($default) ? $default : $template;
    $custom    = get_stylesheet_directory() . "/{$filename}";
    $custom_id = "{$custom}_" . tu()->post->ID;
    $possibles = array($custom_id, $custom);

    if (tu()->config['general']['use_built_in_theme']) {
      $template = $default;
    }

    foreach ($possibles as $possible) {
      $override = "{$possible}.php";

      if (file_exists($override)) {
        $template = $override;
        break;
      }
    }

    return $template;
  }

  /**
   * render_test_progress
   *
   * - Outputs a progress bar to show the percentage of 'completeness' for
   *   the current user.
   * - Can be called from anywhere, so check if a test is available (either the
   *   active Test post or the Test post that the active Question belongs to).
   * 
   * @access public
   *
   * @return mixed Value.
   */
  public function render_test_progress_bar() {
    $test = isset(tu()->test) ? tu()->test :
      ( isset(tu()->question) ? tu()->question->test : null );

    if (!$test) return;

    $percent  = $test->get_percent_complete(tu()->user);
    $progress = "
      <span class='tu-test-progress'>
        <span class='tu-test-progress-bar' style='width:{$percent}%'>
          {$percent}%
        </span>
      </span>
    ";

    echo apply_filters('tu_test_progress_bar', $progress, $percent, $test);
  }

  /**
   * _pre_comment_on_post
   *
   * - Fired on `pre_comment_on_post`
   * - This is because for some reason in wp-comments-post.php the `wp`
   *   event doesn't get fired.
   * - Register the Train-Up! global post object as usual.
   * 
   * @access private
   */
  public function _pre_comment_on_post() {
    register_global_post($_POST['comment_post_ID']);
  }

  /**
   * render_comments
   *
   * - Render the comment list and the form to go with it, for the current post.
   * - Make the comments settings filterable in case developers want to use the
   *   built-in theme, but customise its looks.
   * - Allow rendering of comments to be filtered so that we can prevent them
   *   from being shown if the current user doesn't have access to the current 
   *   post.
   * 
   * @access public
   */
  public function render_comments() {
    global $post;

    $field = '
      <div class="tu-form-text">
        <textarea id="comment" name="comment" cols="50" rows="10"></textarea>
      </div>
    ';

    $args = apply_filters('tu_comments_args', array(
      'post_id' => $post->ID,
      'status'  => 'approve'
    ));

    $list_args = apply_filters('tu_comments_list_args', array(
      'style'             => 'div',
      'type'              => 'comment',
      'reverse_top_level' => true,
      'avatar_size'       => 80,
      'max_depth'         => 2
    ));

    $form_args = apply_filters('tu_comments_form_args', array(
      'comment_notes_after'  => '',
      'comment_notes_before' => '',
      'logged_in_as'         => '',
      'title_reply'          => __('Leave a comment', 'trainup'),
      'title_reply_to'       => __('Reply to %s', 'trainup'),
      'comment_field'        => $field
    ));

    $show_comments = apply_filters('tu_comments', true);

    if ($show_comments) {
      if ($post->post_status === 'draft') {
        tu()->message->notice(__('Comments disabled on drafts', 'trainup'));
      } else {
        wp_list_comments($list_args, get_comments($args));
        comment_form($form_args);
      }
    }
  }

}