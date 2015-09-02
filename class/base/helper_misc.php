<?php

/**
 * Miscellaneous helper functions
 *
 * @package Train-Up!
 */

namespace TU;

/**
 * find_post_by_title
 *
 * Like WordPress' get_page_by_title, but not for a specific post type.
 *
 * @param object $title Title of the first post to find
 *
 * @access public
 *
 * @return object or null
 */
function find_post_by_title($title) {
  global $wpdb;

  $sql  = "
    SELECT ID
    FROM {$wpdb->posts}
    WHERE post_title = %s
  ";

  $post_id = $wpdb->get_var($wpdb->prepare($sql, $title));

  return get_post($post_id);
}

/**
 * get_post_instance
 *
 * Accept a WP_Post and return a Train-Up! post as an instance of its post_type.
 * Or, if it has a class specified then use that instead.
 *
 * @param object $post The post to be re-instantiated.
 * @param boolean $active Whether or not the post should be active
 *
 * @access public
 *
 * @return array Train-Up! post and the type it was matched to.
 */
function get_post_instance($post, $active = false) {
  if (is_numeric($post)) $post = get_post($post);
  if (!$post) return;

  preg_match('/^tu_([a-zA-Z]+)_?/i', $post->post_type, $matches);

  if (count($matches) !== 2) return;

  $type        = $matches[1];
  $class_name  = get_post_meta($post->ID, 'tu_class', true);
  $class_name  = $class_name ?: __NAMESPACE__.'\\'.ucfirst($type);
  $instance    = new $class_name($post, $active);

  return array($instance, $type);
}

/**
 * get_user_instance
 *
 * Accept a WP_User and return a Train-Up! user as an instance of its role.
 *
 * @param object $user
 *
 * @access public
 *
 * @return object Train-Up! user and the type it was matched to
 */
function get_user_instance($user) {
  $type       = 'guest';
  $class_name = 'User';

  foreach (Roles_helper::get_roles() as $role => $details) {
    if (!user_can($user, $role)) continue;

    $type       = preg_replace('/^tu_/', '', $role);
    $class_name = $details['class_name'];
    break;
  }

  $class_name = __NAMESPACE__."\\{$class_name}";
  $instance   = new $class_name($user);

  return array($instance, $type);
}


/**
 * register_global_post
 *
 * - Set up a global post object on the TU singleton that is an instance of the
 *   correct class, depending on the post_type.
 * - Unlike tu()->user, tu()->post might not always be present.
 *
 * @param int|object $post
 *
 * @access public
 */
function register_global_post($post) {
  list($instance, $type) = get_post_instance($post, true);

  if ($instance) {
    tu()->post  = &$instance;
    tu()->$type = &$instance;
  }
}

/**
 * register_global_user
 *
 * - Set up a global user object on the TU singleton that is an instance of the
 *   correct class, depending on the role of the user.
 * - Unlike tu()->post, tu()->user will always be available, to represent
 *   a guest.
 *
 * @param object $user
 *
 * @access public
 */
function register_global_user($user) {
  list($instance, $type) = get_user_instance($user);

  tu()->user  = &$instance;
  tu()->$type = &$instance;
}

/**
 * get_as
 *
 * Callback that accepts a bunch of objects and 'converts' them all to a
 * particular instance of a class. The factory method for that class must exist.
 *
 * @param string $class_name
 * @param array $objects
 *
 * @access public
 *
 * @return array Array of objects
 */
function get_as($class_name, $objects) {
  $func    = __NAMESPACE__."\\{$class_name}::factory";
  $objects = is_array($objects) ? array_map($func, $objects) : array();
  return $objects;
}

/**
 * get_posts_as
 *
 * - Call WordPress' get_posts function
 * - Temporarily add a pre post filter to allow limiting of the results to
 *   particular Group Manager.
 *
 * @param string $class_name
 * @param array $args
 *
 * @access public
 *
 * @return array Array of objects
 */
function get_posts_as($class_name, $args) {
  $filter = function($query) use ($class_name) {
    return apply_filters(strtolower("tu_pre_get_{$class_name}"), $query);
  };

  add_filter('pre_get_posts', $filter);

  $posts = get_posts($args);
  $posts = get_as($class_name, $posts);

  remove_filter('pre_get_posts', $filter);

  return $posts;
}

/**
 * get_users_as
 *
 * - Call WordPress' get_users as normal, but return them as instances of the
 *   type of user being requested.
 * - Also like with get_posts_as, temporarily add a filter to allow limiting
 *   of access to users (i.e. For Group managers)
 *
 * @param string $class_name
 * @param array $args
 *
 * @access public
 *
 * @return array Array of objects
 */
function get_users_as($class_name, $args) {
  $filter = function($query) use ($class_name) {
    return apply_filters(strtolower("tu_pre_get_{$class_name}"), $query);
  };

  add_filter('pre_user_query', $filter);

  $users = get_as($class_name, get_users($args));

  remove_filter('pre_user_query', $filter);

  return $users;
}

/**
 * get_known_post_type_names
 *
 * @access public
 *
 * @return array The names of the core post types that the plugin uses
 */
function get_known_post_type_names() {
  return apply_filters(
    'tu_known_post_type_names',
    explode(' ', 'Levels Resources Tests Results Questions Groups Pages')
  );
}

/**
 * filter_ids
 *
 * This function is used to reduce a set of IDs down, to contain only those
 * which appear in the other set. For example:
 * - Available posts are 1, 2, 3.
 * - A Group Manager is allowed to access ID 2:
 *   Therefore filter_ids([2], [1,2,3]) would return [2]
 * - Another Group Manager can't access any IDs:
 *   Therefore filter_ids([], [1,2,3]) would return [0]
 *   The reason for this is to make SQL queries return no results using
 *   `AND IN (0)`, because `AND IN ()` would fail.
 * - `array_values` is used just to reindex the array
 *
 * @param mixed $ids IDs you want.
 * @param mixed $set IDs to search.
 *
 * @see Test_suite::_filter_ids
 *
 * @access public
 *
 * @return array of IDs
 */
function filter_ids($ids = array(), $set = array()) {
  if (is_array($ids) && is_array($set) && count($set) > 0) {
    $ids = array_intersect($ids, $set);
  }

  if (!$ids) {
    $ids = array(0);
  }

  return array_values($ids);
}

/**
 * simplify
 *
 * Similar to `sanitize_title_with_dashes`, but with underscores instead
 * of dashes.
 *
 * @param string $text
 *
 * @access public
 *
 * @return string
 */
function simplify($text) {
  $slug = strtolower(strip_tags($text));
  $slug = preg_replace('/\s+/', '_', $slug);
  $slug = trim($slug, '-_');
  return $slug;
}

/**
 * go_to
 *
 * Drop everything and go to the specified URL.
 *
 * @param mixed $url
 *
 * @access public
 */
function go_to($url) {
  wp_redirect($url);
  exit;
}

/**
 * login_url
 *
 * Generate a URL that will show the login screen, but on logging in will
 * redirect to the given URL.
 *
 * @param string $return_to
 *
 * @access public
 *
 * @return string
 */
function login_url($return_to = '') {
  $url = Pages::factory('login')->url;

  if ($return_to) {
    $url = add_query_arg(array('return_to' => $return_to), $url);
  }

  return $url;
}

/**
 * logout_url
 *
 * Generate a URL that will navigate users to the logout page, and after
 * successfully logging out will redirect on to another page.
 *
 * @param string $return_to
 *
 * @access public
 *
 * @return string
 */
function logout_url($return_to = '') {
  $url = Pages::factory('logout')->url;

  if ($return_to) {
    $url = add_query_arg(array('return_to' => $return_to), $url);
  }

  return $url;
}

/**
 * build_list
 *
 * Accept an array or any type of object with temporary properties `_parent_id`
 * and `_id` which are used to create a parent/child structure.
 *
 * @param mixed  $tree
 * @param int    $parent_id
 * @param mixed  $callback Callback for rendering the contents of a list item
 * @param string $type The type of list, ol or ul
 * @param array  $attributes Optional HTML attribtues for the first list element
 *
 * @access public
 *
 * @return mixed Value.
 */
function build_list($tree, $parent_id = 0, $callback = null, $type = 'ul', $attributes = array()) {
  $html  = '';
  $attrs = html_attributes($attributes);

  foreach ($tree as $item) {
    if ($item->_parent_id == $parent_id) {
      $html .= '
         <li>'.
          call_user_func_array($callback, array($item)).
          build_list($tree, $item->_id, $callback, $type).
        '</li>
      ';
    }
  }

  return "<{$type}{$attrs}>$html</{$type}>";
}

/**
 * html_attributes
 *
 * Convert a hash to HTML attributes
 *
 * @param array $attributes
 *
 * @access public
 *
 * @return string
 */
function html_attributes($attributes = array()) {
  $attrs = '';

  foreach ($attributes as $key => $value) {
    $attrs .= " $key='{$value}'";
  }

  return $attrs;
}

/**
 * rankify
 *
 * - Accepts an array of hashes, and adds a rank column to each one depending
 *   on the value of a specific key.
 * - This is slower (probably) than using SQL, but easier basically & you can
 *   cache the result if you want.
 *
 * @param mixed  $array Of hashes
 * @param string $key To inspect
 *
 * @access public
 *
 * @return mixed Value.
 */
function rankify($array, $key = 'percentage') {
  foreach ($array as $i => &$item) {
    $prev = isset($array[$i-1]) ? $array[$i-1][$key] : null;

    if ($prev === $item[$key]) {
      $item['rank'] = $array[$i-1]['rank'];
    } else {
      $item['rank'] = $i + 1;
    }
  }

  return $array;
}

/**
 * interval_to_str
 *
 * @param object \DateInterval Description.
 *
 * @see http://www.php.net/manual/en/class.dateinterval.php
 *
 * @access public
 *
 * @return string Accepts a Date Interval object and returns its string as an
 * 8601 spec. Why does this not exist already!?
 */
function interval_to_str(\DateInterval $interval) {
  $spec = 'P';

  foreach (str_split('ymdhis') as $key) {
    $value = $interval->$key;

    if ($key === 'h') {
      $spec .= 'T';
    }
    if ($key === 'i') {
      $char = 'M';
    } else {
      $char = strtoupper($key);
    }
    if ($value > 0) {
      $spec .= "{$value}{$char}";
    }
  }

  return $spec;
}


