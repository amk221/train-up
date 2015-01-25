<?php

/**
 * General helper functions for working with Settings
 *
 * @package Train-Up!
 * @subpackage Settings
 */

namespace TU;

class Settings {

  /**
   * get_config
   *
   * - Load the main settings structure (a hash of options with some extra info)
   * - Go through each major group or 'section' of settings and create a new
   *   hash to represent the administrators current choice of settings.
   *
   * @access public
   * @static
   *
   * @return array
   */
  public static function get_config() {
    $structure = self::get_structure();
    $config    = array();

    foreach ($structure as $section_slug => $section) {
      foreach ($section['settings'] as $setting_slug => $setting) {
        $settings = get_option("tu_settings_{$section_slug}");

        if (is_array($settings) && !isset($settings[$setting_slug])) {
          $value = null;
        } else if (isset($settings[$setting_slug])) {
          $value = $settings[$setting_slug];
        } else if (isset($setting['default'])) {
          $value = $setting['default'];
        } else {
          $value = null;
        }

        $config[$section_slug][$setting_slug] = $value;
      }
    }

    return $config;
  }

  /**
   * clear
   *
   * Reset the settings for Train-Up! by clearing any custom options made
   * by the administrators.
   *
   * @access public
   * @static
   */
  public static function clear() {
    foreach (self::get_structure() as $section_slug => $section) {
      delete_option("tu_settings_{$section_slug}");
    }
  }

  /**
   * get_structure
   *
   * - Return a massive hash of each of the major sections of settings that the
   *   plugin has
   * - Each section has a title and some settings
   * - Each setting hash a type, a default value and some validation rules
   *
   * @access public
   * @static
   *
   * @return array
   */
  public static function get_structure() {
    $_trainees       = isset(tu()->config['trainees']['plural']) ? tu()->config['trainees']['plural'] : __('Trainees', 'trainup');
    $_trainee        = isset(tu()->config['trainees']['single']) ? tu()->config['trainees']['single'] : __('Trainee', 'trainup');
    $_test           = isset(tu()->config['tests']['single']) ? tu()->config['tests']['single'] : __('Test', 'trainup');
    $_group          = isset(tu()->config['groups']['single']) ? tu()->config['groups']['single'] : __('Group', 'trainup');
    $_groups         = isset(tu()->config['groups']['plural']) ? tu()->config['groups']['plural'] : __('Groups', 'trainup');
    $_levels         = isset(tu()->config['levels']['plural']) ? tu()->config['levels']['plural'] : __('Levels', 'trainup');
    $_resources      = isset(tu()->config['resources']['plural']) ? tu()->config['resources']['plural'] : __('Resources', 'trainup');
    $_tests          = isset(tu()->config['tests']['plural']) ? tu()->config['tests']['plural'] : __('Tests', 'trainup');
    $_group_managers = isset(tu()->config['group_managers']['plural']) ? tu()->config['group_managers']['plural'] : __('Group managers', 'trainup');

    return array(
      'general' => array(
        'title'    => __('General', 'trainup'),
        'settings' => array(
          'auto_update' => array(
            'type'        => 'checkbox',
            'title'       => __('Keep up to date', 'trainup'),
            'description' => __('Enabled', 'trainup'),
            'default'     => true
          ),
          'title' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Title', 'trainup'),
            'default'    => __('Training', 'trainup')
          ),
          'main_slug' => array(
            'type'        => 'text_input',
            'validation'  => array('required'),
            'title'       => __('Main slug', 'trainup'),
            'default'     => 'training'
          ),
          'use_built_in_theme' => array(
            'type'        => 'checkbox',
            'title'       => __('Use built in theme', 'trainup'),
            'description' => __('Enabled', 'trainup'),
            'default'     => true
          ),
          'include_theme_css' => array(
            'type'        => 'checkbox',
            'title'       => __('Stylesheets', 'trainup'),
            'description' => __('Include theme CSS', 'trainup'),
            'default'     => true
          ),
          'arrow_key_navigation' => array(
            'type'    => 'checkboxes',
            'title'   => __('Arrow key navigation', 'trainup'),
            'options' => array(
              'resources' => $_resources,
              'questions' => __('Questions', 'trainup')
            ),
            'default' => array(
              'resources' => 1,
              'questions' => 1
            )
          ),
          'login_by' => array(
            'type'    => 'radio',
            'title'   => __('Login by', 'trainup'),
            'options' => array(
              'user_email' => __('Email address', 'trainup'),
              'user_login' => __('Username', 'trainup')
            ),
            'default' => 'user_email'
          ),
          'header' => array(
            'type'    => 'radio',
            'title'   => __('Header', 'trainup'),
            'options' => array(
              'title'  => __('Title', 'trainup'),
              'image' => __('Image', 'trainup')
            ),
            'default' => 'title'
          ),
          'header_image' => array(
            'type'    => 'image_upload',
            'title'   => __('Header image', 'trainup'),
            'default' => false
          )
        )
      ),
      'levels' => array(
        'title'    => __('Levels', 'trainup'),
        'settings' => array(
          'single' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Singular', 'trainup'),
            'default'    => __('Level', 'trainup'),
            'help'       => __('e.g. Level, Course, Subject', 'trainup')
          ),
          'plural' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Plural', 'trainup'),
            'default'    => __('Levels', 'trainup'),
            'help'       => __('e.g. Levels, Courses, Subjects', 'trainup')
          ),
          'default_content' => array(
            'type'      => 'editor',
            'title'     => __('Default template', 'trainup'),
            'default'   => file_get_contents(tu()->get_path("/view/backend/levels/default_template.txt"))
          )
        ),
      ),
      'resources' => array(
        'title'    => __('Resources', 'trainup'),
        'settings' => array(
          'single' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Singular', 'trainup'),
            'default'    => __('Resource', 'trainup'),
            'help'       => __('e.g. Material, Resource, or Slide etc.', 'trainup')
          ),
          'plural' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Plural', 'trainup'),
            'default'    => __('Resources', 'trainup'),
            'help'       => __('e.g. Material, Resources, or Slides etc.', 'trainup')
          ),
          'lock_during_test' => array(
            'type'        => 'checkbox',
            'title'       => sprintf(__('Prevent access during %1$s', 'trainup'), strtolower($_test)),
            'description' => __('Enabled', 'trainup'),
            'default'     => false,
            'help'        => __('(Savvy web-users can subvert this)', 'trainup')
          )
        )
      ),
      'tests' => array(
        'title'    => __('Tests', 'trainup'),
        'settings' => array(
          'single' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Singular', 'trainup'),
            'default'    => __('Test', 'trainup'),
            'help'       => __('e.g. Test, Quiz etc.', 'trainup')
          ),
          'plural' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Plural', 'trainup'),
            'default'    => __('Tests', 'trainup'),
            'help'       => __('e.g. Tests, Exams etc.', 'trainup')
          ),
          'trim_answer_whitespace' => array(
            'type'        => 'checkbox',
            'title'       => __('Trim answer whitespace', 'trainup'),
            'description' => __('Enabled', 'trainup'),
            'default'     => false,
            'help'        => sprintf(__('(Removes leading & trailing spaces from %1$s attempted answers)', 'trainup'), strtolower($_trainees))
          ),
          'ajax_saving' => array(
            'type'        => 'checkbox',
            'title'       => __('Save answers via AJAX', 'trainup'),
            'description' => __('Enabled', 'trainup'),
            'default'     => false,
            'help'        => __('(Requires a modern browser with XHR2 support)', 'trainup')
          ),
          'default_content' => array(
            'type'    => 'editor',
            'title'   => sprintf(__('Default %1$s template', 'trainup'), strtolower($_test)),
            'default' => file_get_contents(tu()->get_path("/view/backend/tests/default_template.txt"))
          ),
          'default_result_content' => array(
            'type'    => 'editor',
            'title'   => __('Default result template', 'trainup'),
            'default' => file_get_contents(tu()->get_path("/view/backend/results/default_template.txt"))
          ),
          'default_result_status' => array(
            'title'   => __('Publish results', 'trainup'),
            'type'    => 'radio',
            'options' => array(
              'publish' => __('Instantly', 'trainup'),
              'draft'   => __('Manually', 'trainup')
            ),
            'default' => 'publish'
          ),
          'result_comments' => array(
            'title'       => __('Result comments', 'trainup'),
            'type'        => 'checkbox',
            'description' => __('Enabled', 'trainup'),
            'default'     => true
          ),
          'comment_result_notifications' => array(
            'type'    => 'checkboxes',
            'title'   => __('Result comment notifications', 'trainup'),
            'options' => array(
              'administrators' => __('Email administrators', 'trainup'),
              'group_managers' => sprintf(__('Email %1$s', 'trainup'), strtolower($_group_managers)),
              'trainee'        => sprintf(__('Email %1$s', 'trainup'), strtolower($_trainee))
            ),
            'default' => array(
              'administrators' => 1,
              'group_managers' => 1,
              'trainee'        => 1
            )
          ),
          'grades' => array(
            'callback' => 'render_grade_selector',
            'title'    => __('Default grades', 'trainup'),
            'default'  => array(
              array(
                'description' => __('Unsuccessful', 'trainup')
              ),
              array(
                'percentage'  => 50,
                'description' => __('Pass', 'trainup')
              ),
              array(
                'percentage'  => 70,
                'description' => __('Pass with Merit', 'trainup')
              ),
              array(
                'percentage'  => 98,
                'description' => __('Pass with Distinction', 'trainup')
              )
            )
          )
        )
      ),
      'trainees' => array(
        'title'    => __('Trainees', 'trainup'),
        'settings' => array(
          'single' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Singular', 'trainup'),
            'default'    => __('Trainee', 'trainup'),
            'help'       => __('e.g. Trainee, Student, Pupil', 'trainup')
          ),
          'plural' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Plural', 'trainup'),
            'default'    => __('Trainees', 'trainup'),
            'help'       => __('e.g. Trainees, Students, Pupils', 'trainup')
          ),
          'can_choose_groups' => array(
            'type'    => 'select',
            'title'   => sprintf(__('Can choose their %1$s', 'trainup'), strtolower($_groups)),
            'options' => array(
              'disabled' => __('Disabled', 'trainup'),
              'single'   => sprintf(__('A single %1$s', 'trainup'), strtolower($_group)),
              'muliple'  => sprintf(__('Multiple %1$s', 'trainup'), strtolower($_groups))
            ),
            'default' => 'disabled'
          ),
          'instant_sign_up' => array(
            'title'       => __('Instant sign up', 'trainup'),
            'type'        => 'checkbox',
            'description' => __('Enabled', 'trainup'),
            'help'        => sprintf(__("(Bypasses 'confirm your email address')", 'trainup'), $_trainees, strtolower($_group)),
            'default'     => false
          ),
          'sign_up_notifications' => array(
            'type'    => 'checkboxes',
            'title'   => __('Sign up notifications', 'trainup'),
            'options' => array(
              'administrators' => __('Email administrators', 'trainup'),
              'trainees'       => sprintf(__('Email %1$s', 'trainup'), strtolower($_trainee))
            ),
            'default' => array(
              'administrators' => 1,
              'trainees'       => 0
            )
          ),
          'sign_up_email_template' => array(
            'type'    => 'editor',
            'title'   => __('Sign up confirmation email', 'trainup'),
            'default' => file_get_contents(tu()->get_path("/view/backend/emails/trainee_sign_up.txt"))
          )
        )
      ),
      'groups' => array(
        'title'    => __('Groups', 'trainup'),
        'settings' => array(
          'single' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Singular', 'trainup'),
            'default'    => __('Group', 'trainup'),
            'help'       => __('e.g. Group, Class', 'trainup')
          ),
          'plural' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Plural', 'trainup'),
            'default'    => __('Groups', 'trainup'),
            'help'       => __('e.g. Groups, Teams', 'trainup')
          ),
          'show_groups_on_sign_up' => array(
            'title'       => __('Show on sign up', 'trainup'),
            'type'        => 'checkbox',
            'description' => __('Enabled', 'trainup'),
            'help'        => sprintf(__('(%1$s must choose which %2$s they are in when signing up)', 'trainup'), $_trainees, strtolower($_group)),
            'default'     => false
          )
        ),
      ),
      'group_managers' => array(
        'title'    => __('Group managers', 'trainup'),
        'settings' => array(
          'single' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Singular', 'trainup'),
            'default'    => __('Group manager', 'trainup'),
            'help'       => __('e.g. Team leader, Overseer', 'trainup')
          ),
          'plural' => array(
            'type'       => 'text_input',
            'validation' => array('required'),
            'title'      => __('Plural', 'trainup'),
            'default'    => __('Group managers', 'trainup'),
            'help'       => __('e.g. Subject tutors, Branch managers', 'trainup')
          )
        )
      ),
      'tin_can' => array(
        'title' => __('Tin Can API', 'trainup'),
        'settings' => array(
          'enabled' => array(
            'title'       => __('Tin Can', 'trainup'),
            'type'        => 'checkbox',
            'description' => __('Enabled', 'trainup'),
            'help'        => __('(Turn LRS requests on or off)', 'trainup'),
            'default'     => false
          ),
          'version' => array(
            'type'       => 'text_input',
            'size'       => 10,
            'title'      => __('Version', 'trainup'),
            'default'    => '1.0.0'
          ),
          'lrs_api' => array(
            'type'       => 'text_input',
            'title'      => __('LRS API', 'trainup'),
            'default'    => '',
            'size'       => 40,
            'help'       => __('The URL of your Learning Record Store', 'trainup')
          ),
          'lrs_username' => array(
            'type'       => 'text_input',
            'title'      => __('LRS username', 'trainup'),
            'default'    => ''
          ),
          'lrs_password' => array(
            'type'       => 'text_input',
            'title'      => __('LRS password', 'trainup'),
            'default'    => ''
          ),
          'track' => array(
            'type'    => 'checkboxes',
            'title'   => __('What to track', 'trainup'),
            'options' => array(
              'answer_question' => sprintf(__('%1$s attempts to answer a question', 'trainup'), $_trainee),
              'start_test'      => sprintf(__('%1$s starts a %2$s', 'trainup'), $_trainee, strtolower($_test)),
              'finish_test'     => sprintf(__('%1$s completes a %2$s (passes or fails)', 'trainup'), $_trainee, strtolower($_test))
            ),
            'default' => array(
              'answer_question' => 0,
              'start_test'      => 0,
              'finish_test'     => 1
            )
          )
        )
      )
    );
  }

  /**
   * get_default_email_address
   *
   * @access public
   * @static
   *
   * @return string The assumed-default email address for this WordPress site.
   */
  public static function get_default_email_address() {
    $domain = preg_replace('/^www\./', '', strtolower($_SERVER['SERVER_NAME']));
    return "wordpress@$domain";
  }

}



