<?php

/**
 * Abstract class to help with custom post type administration UIs
 *
 * @package Train-Up!
 */

namespace TU;

abstract class Post_admin extends Admin {

  /**
   * $post_type
   *
   * An instance of the post type that this admin class helps manage.
   *
   * @var Post_Type
   *
   * @access protected
   */
  protected $post_type;

  /**
   * $editable_permalinks
   *
   * Whether or not to show the bar that allows administrators to edit a post's
   * permalink on this admin page.
   *
   * @var mixed
   *
   * @access protected
   */
  protected $editable_permalinks = true;

  /**
   * __construct
   *
   * - When creating a new post_admin helper, accept an instance of the post
   *   type that is supposed to help with.
   * - Set the highlight_sub_menu_item, to be that of the post type's edit
   *   screen, so that it is forced to be active in Train-Up's menu.
   * - Add some crumbs for Adding/editing the post type
   * - Add loads of events to help manage the post type.
   *
   * @param object $post_type
   *
   * @access public
   */
  public function __construct($post_type) {
    $this->post_type  = $post_type;
    $this->front_page = "edit.php?post_type={$post_type->name}";

    if ($this->is_active()) {
      parent::__construct();

      $this->highlight_sub_menu_item = $this->front_page;
      $this->add_crumbs();

      add_action('current_screen', array($this, '_add_help'));
      add_action('add_meta_boxes', array($this, '_add_meta_boxes'));
      add_action('admin_init', array($this, '_pre_close_meta_boxes'));
      add_filter('admin_body_class', array($this, '_stylable_post_type'));
      add_action('pre_get_posts', array($this, '_set_order'));
      add_filter('get_sample_permalink_html', array($this, '_get_sample_permalink_html'), 10, 4);
      add_filter("manage_edit-{$this->post_type->name}_columns", array($this, '_manage_edit_columns'));
      add_action("manage_edit-{$this->post_type->name}_sortable_columns", array($this, '_manage_edit_sortable_columns'));
      add_action("manage_{$this->post_type->name}_posts_custom_column", array($this, '_manage_posts_custom_column'), 10, 2);
      add_action('before_delete_post', array($this, '_delete_post'));
      add_action('wp_trash_post', array($this, '_trash_post'));
      add_action('untrash_post', array($this, '_untrash_post'));
      add_action('save_post', array($this, '_save_post'), 10, 2);
    }
  }

  /**
   * _get_sample_permalink_html
   *
   * - Fired on `get_sample_permalink_html`
   * - If this admin class has specified that it disallows editing of the
   *   permalink of a post, then replace it with just a link to the post.
   *
   * @param string $html
   *
   * @access private
   *
   * @return string
   */
  public function _get_sample_permalink_html($html) {
    global $post;

    if ($this->editable_permalinks) return $html;

    $href = get_permalink($post);
    $text = __('View', 'trainup');

    return "<a href='{$href}' class='button button-small'>{$text}&nbsp;&raquo;</a>";
  }

  /**
   * _add_meta_boxes
   *
   * - Fired on `add_meta_boxes`
   * - If this class has specified that is has meta boxes to be added,
   *   then fire the function that adds them.
   *
   * @access private
   */
  public function _add_meta_boxes() {
    if (method_exists($this, 'get_meta_boxes')) {
      $this->add_meta_boxes();
    }
  }

  /**
   * _add_help
   *
   * - Fired on `current_screen`
   * - Add some general help
   * - Then, if this class has also defined some screen-specific help, add that
   *
   * @access private
   */
  public function _add_help() {
    $general_help = array(
      'shortcodes' => array(
        'title'   => __('Shortcodes', 'trainup'),
        'content' => '<p>'.__('Shortcodes allow you to insert dynamic content into the editor.', 'trainup').'</p>'
      )
    );

    $specific_help = array();

    if (method_exists($this, 'get_help')) {
      $specific_help = $this->get_help();
    }

    $help = $general_help + $specific_help;

    $this->add_help($help);
  }

  /**
   * _stylable_post_type
   *
   * - Fired on `admin_body_class` whenever this admin class is active.
   * - Append the post type class name so we can target it using CSS.
   *
   * @param string $classes
   *
   * @access private
   *
   * @return string The altered classes
   */
  public function _stylable_post_type($classes) {
    $classes .= " on-{$this->post_type->slug} ";
    return $classes;
  }

  /**
   * _set_order
   *
   * - Fired on `pre_get_posts`
   * - Default to by ascending menu order unless another order has been
   *   specified by clicking the column headings.
   *
   * @param object $query
   *
   * @access public
   *
   * @return object The altered query
   */
  public function _set_order($query) {
    if (!isset($_REQUEST['orderby'])) {
      $query->set('orderby', 'menu_order');
      $query->set('order', 'ASC');
    }

    return $query;
  }

  /**
   * _delete_post
   *
   * - Fired on `before_delete_post`
   * - Temporarily unbind delete and save actions to avoid an infinite loop.
   * - Fire an 'on_delete' function for child classes to utilise if they wish
   *
   * @access private
   */
  public function _delete_post() {
    if (method_exists($this, 'on_delete')) {
      remove_action('before_delete_post', array($this, '_delete_post'));
      remove_action('save_post', array($this, '_save_post'));
      call_user_func_array(array($this, 'on_delete'), func_get_args());
      add_action('before_delete_post', array($this, '_delete_post'));
      add_action('save_post', array($this, '_save_post'), 10, 2);
    }
  }

  /**
   * _trash_post
   *
   * - Fired on `wp_trash_post`
   * - Temporarily unbind trash and save actions to avoid an infinite loop.
   * - Fire an 'on_trash' function for child classes to utilise if they wish.
   *
   * @access private
   */
  public function _trash_post() {
    if (method_exists($this, 'on_trash')) {
      remove_action('wp_trash_post', array($this, '_trash_post'));
      remove_action('save_post', array($this, '_save_post'));
      call_user_func_array(array($this, 'on_trash'), func_get_args());
      add_action('wp_trash_post', array($this, '_trash_post'));
      add_action('save_post', array($this, '_save_post'), 10, 2);
    }
  }

  /**
   * _untrash_post
   *
   * - Fired on `untrash_post`
   * - Temporarily unbind untrash and save actions to avoid an infinite loop.
   * - Fire an 'on_untrash' function for child classes to utilise if they wish.
   *
   * @access private
   */
  public function _untrash_post() {
    if (method_exists($this, 'on_untrash')) {
      remove_action('untrash_post', array($this, '_untrash_post'));
      remove_action('save_post', array($this, '_save_post'));
      call_user_func_array(array($this, 'on_untrash'), func_get_args());
      add_action('untrash_post', array($this, '_untrash_post'));
      add_action('save_post', array($this, '_save_post'), 10, 2);
    }
  }

  /**
   * _save_post
   *
   * - Fired on `save_post`
   * - If the post being saved isn't a revision, temporarily unbind the save
   *   action so we don't create an infinite loop.
   * - Fire an 'on_save' function for child classes to utilise if they wish.
   *
   * @param integer $post_id
   * @param object $post
   *
   * @access private
   */
  public function _save_post($post_id, $post) {
    if (!wp_is_post_revision($post_id) && method_exists($this, 'on_save')) {
      remove_action('save_post', array($this, '_save_post'));
      call_user_func_array(array($this, 'on_save'), func_get_args());
      add_action('save_post', array($this, '_save_post'), 10, 2);
    }
  }

  /**
   * get_columns
   *
   * Returns an array of extra column names to be inserted into the default
   * WordPress list.
   *
   * @access protected
   *
   * @return array
   */
  protected function get_columns() {
    return array(
      'order' => __('Order', 'trainup')
    );
  }

  /**
   * get_sortable_columns
   *
   * - Returns an array of columns that WordPress should make sortable for this
   *   post type admin class.
   * - The majority of the post types make use of menu_order heavily, so add
   *   that by default.
   *
   * @access protected
   *
   * @return array
   */
  protected function get_sortable_columns() {
    return array(
      'order' => __('Order', 'trainup')
    );
  }

  /**
   * column_order
   *
   * - Fired automatically for the order column
   * - Output the post's menu order in the list of posts.
   *
   * @access protected
   */
  protected function column_order() {
    global $post;
    echo $post->menu_order;
  }

  /**
   * _manage_edit_columns
   *
   * - Fired on `manage_edit-[X]_columns`
   *   (when the post type that this admin class manages is being managed!)
   * - If this class has specified it has extra columns, then add them.
   *
   * @param array $columns
   *
   * @access private
   *
   * @return array The altered columns
   */
  public function _manage_edit_columns($columns) {
    if (method_exists($this, 'get_columns')) {
      $columns = $this->add_columns($columns);
    }
    return $columns;
  }

  /**
   * _manage_edit_sortable_columns
   *
   * - Fired on `manage_edit-[X]_sortable_columns`
   *   (when the post type that this admin class manages is being managed!)
   * - If this class has specified it has columns that should be sortable
   *   then add them to the existing set.
   *
   * @param array $columns
   *
   * @access private
   *
   * @return array The altered columns
   */
  public function _manage_edit_sortable_columns($columns) {
    if (method_exists($this, 'get_sortable_columns')) {
      $columns += $this->get_sortable_columns();
    }
    return $columns;
  }

  /**
   * _manage_posts_custom_column
   *
   * - Fired on `manage_[X]_posts_custom_column`
   *   (when the cells are being rendered for the custom column)
   * - Automatically fire a function so that each column can easily print
   *   out its content.
   *
   * @param string $column
   * @param integer $post_id
   *
   * @access private
   */
  public function _manage_posts_custom_column($column, $post_id) {
    $func = "column_$column";
    if (method_exists($this, $func)) {
      $this->$func($post_id);
    }
  }

  /**
   * _on_meta_box
   *
   * - Fired when a meta box is about to be rendered.
   * - Get the ID of it, and then fire a function automatically so that
   *   we can easily return content for that meta box in child classes.
   * - If the admin class doesn't have a function to handle the meta box then
   *   fire an action so that if we really need to we can add functionality
   *
   * @param object $post
   * @param array $args
   *
   * @access private
   */
  public function _on_meta_box($post, $args) {
    $box_id = $args['args'][0];
    $func = "meta_box_{$box_id}";

    if (method_exists($this, $func)) {
      $this->$func();
    } else {
      do_action("tu_{$func}");
    }
  }

  /**
   * add_crumbs
   *
   * Automatically add a Root, Add and Edit crumbs for the post type that
   * this admin class helps manage.
   *
   * @access protected
   */
  protected function add_crumbs() {
    if (!$this->is_active()) return;

    $options = $this->post_type->options['labels'];

    tu()->add_crumb($this->front_page, $options['name']);

    if ($this->is_adding()) {
      tu()->add_crumb('', $options['add_new_item']);
    } else if ($this->is_editing()) {
      tu()->add_crumb('', $options['edit_item']);
    }
  }

  /**
   * add_meta_boxes
   *
   * - Fired via the `add_meta_box` action, but only if this class actually
   *   has meta boxes that want to be rendered.
   * - Go through each one and add them.
   *
   * @access protected
   */
  protected function add_meta_boxes() {
    foreach ($this->get_meta_boxes() as $name => $box) {
      add_meta_box(
        $name,
        $box['title'],
        array($this, '_on_meta_box'),
        $this->post_type->name,
        $box['context'],
        $box['priority'],
        array($name)
      );
    }
  }

  /**
   * add_help
   *
   * - Fired on `current_screen`
   * - Adds the screen-specific help
   *
   * @access protected
   */
  private function add_help($help) {
    $screen = get_current_screen();

    foreach ($help as $id => $help) {
      $help['id'] = $id;
      $screen->add_help_tab($help);
    }
  }

  /**
   * is_active
   *
   * Returns whether this post type admin class is considered to be active.
   * i.e. we are Adding, Editing, or looking at the list of posts.
   *
   * @access public
   *
   * @return boolean
   */
  public function is_active() {
    return $this->is_adding() || $this->is_editing()  || $this->is_browsing();
  }

  /**
   * is_adding
   *
   * Returns whether we appear to be adding a new post of the type that this
   * admin class is for.
   *
   * @access public
   *
   * @return boolean
   */
  public function is_adding() {
    global $pagenow;

    return (
      $pagenow === 'post-new.php' &&
      isset($_GET['post_type']) &&
      $_GET['post_type'] === $this->post_type->name
    );
  }

  /**
   * is_editing
   *
   * Returns whether we appear to be editing an existing post of the type that
   * this admin class is for.
   *
   * @access public
   *
   * @return boolean
   */
  public function is_editing() {
    global $pagenow;

    $post_id = isset($_GET['post']) ? $_GET['post'] : (
      isset($_POST['post_ID']) ? $_POST['post_ID'] : null
    );

    $is_editing = (
      $pagenow === 'post.php' &&
      get_post_type($post_id) === $this->post_type->name &&
      isset($_REQUEST['action']) &&
      preg_match('/^edit|trash|delete/', $_REQUEST['action'])
    );

    return $is_editing ? $post_id : false;
  }

  /**
   * is_browsing
   *
   * Returns whether we appear to be browsing posts of the type that this
   * admin class is for.
   *
   * @access public
   *
   * @return boolean
   */
  public function is_browsing() {
    global $pagenow;

    return (
      $pagenow === 'edit.php' &&
      isset($_GET['post_type']) &&
      $_GET['post_type'] === $this->post_type->name
    );
  }

  /**
   * meta_box_shortcodes
   *
   * Callback for the 'shortcodes' meta box. Render its related view.
   *
   * @access protected
   */
  protected function meta_box_shortcodes() {
    echo new View(tu()->get_path('/view/backend/misc/shortcodes_meta'), array(
      'shortcodes' => $this->post_type->shortcodes
    ));
  }

  /**
   * add_columns
   *
   * - Slightly over kill, but it makes child classes easier to manage.
   * - Get the columns that the child class wishes to add, go through them
   *   If the 'order' column was specified make sure it appears before the
   *   Title column.
   * - Always make sure the new columns are inserted after the title column
   *   and before the date column, to be consistent.
   *
   * @param array $columns
   *
   * @access private
   *
   * @return array The altered columns
   */
  private function add_columns($columns) {
    $new_columns = $this->get_columns();

    if (is_array($new_columns)) {
      if (array_key_exists('order', $new_columns)) {

        $order_column = array('order' => $new_columns['order']);
        unset($new_columns['order']);

        $actual_columns = array()
          + array_slice($columns, 0, 1) // Checkbox column
          + $order_column               // Order column
          + array_slice($columns, 1, 1) // Title column
          + $new_columns                // New columns
          + $columns;                   // Date column

      } else {
        $actual_columns = array()
          + array_slice($columns, 0, 2) // Checkbox & title column
          + $new_columns                // New column
          + $columns;                   // Date column
      }
    } else {
      $actual_columns = $columns;
    }

    return $actual_columns;
  }

  /**
   * _pre_close_meta_boxes
   *
   * - If this admin class is being used to add or edit a post then get the
   *   closed meta box information for the current user.
   * - Loop through this class' meta boxes and if any specify that they should
   *   automatically be closed, then add them to the users closed meta box
   *   information and save that setting.
   *
   * @access private
   */
  public function _pre_close_meta_boxes() {
    if ( !($this->is_adding() || $this->is_editing()) ) return;

    $user_id   = get_current_user_id();
    $post_type = 'closedpostboxes_'. $this->post_type->name;
    $closed    = get_user_meta($user_id, $post_type, true) ?: array();

    if (!method_exists($this, 'get_meta_boxes')) return;

    foreach ($this->get_meta_boxes() as $name => $details) {
      if (
        isset($details['closed']) &&
        $details['closed'] === true &&
        !in_array($name, $closed)
      ) {
        array_push($closed, $name);
      }
    }

    update_user_option($user_id, $post_type, $closed, true);
  }

}



