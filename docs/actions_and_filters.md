Actions & Filters
=================

##### Authorisation

	add_filter('tu_login_authorised', function($authorised, $form) {
	  // Return whether or not to authorise the login attempt
	}, 10, 2);

	add_filter('tu_trainee_sign_up_authorised', function($authorised, $form) {
	  // Return whether or not to authorise the Trainee's sign up attempt
	});

	add_action('tu_trainee_signed_up', function($trainee) {
	  // Fired when a new Trainee signs up successfully
	});
	
	add_filter('tu_group_manager_role', function($config) {
	  // Change the capabilities that group managers have
	});
	
	add_filter('tu_trainee_role', function($config) {
	  // Change the capabilities that trainees have
	});
	
	add_filter('tu_bail_content', function($content) {
	  // Change what is displayed when Train-Up! bails
	});

##### Access

	add_filter('tu_user_can_start_test', function($result, $user, $test) {
	  // Change whether the given user can start the test
	});
	
	add_filter('tu_user_can_finish_test', function($result, $user, $test) {
	  // Change whether the given user is allowed to finish the test
	});
	
	add_filter('tu_user_is_eligible_for_level', function($result, $user, $level) {
	  // Whether the given user has passed the relevant tests in order to access the level
	});
	
	add_filter('tu_user_can_access_*', function($result, $user, $post) {
	  // Whether the given user can access the level, resource, test, question or result post. e.g. tu_user_can_access_level
	});
	
	add_filter('tu_resource_available_to_user', function($result, $resource, $user) {
	  // Whether the given resource is available to (by way of the schedule) to the user
	});
	
	add_action('tu_user_added_to_group', function($user, $group_id) {
	  // Fired when a user is added to a group
	}, 10, 2);
	
	add_action('tu_user_removed_from_group', function($user, $group_id) {
	  // Fired when a user is removed from a group
	}, 10, 2);

##### Breadcrumbs

	add_filter('tu_question_crumbs', function($crumbs) {
	  // Customise the breadcrumbs shown on a Question page
	});
	
	add_filter('tu_resource_crumbs', function($crumbs) {
	  // Allows you to change the bread crumb trail displayed on Resource pages
	});
	
	add_filter('tu_result_crumbs', function($crumbs) {
	  // Allows you to change the bread crumb trail displayed on Test Result pages
	});
	
	add_filter('tu_test_crumbs', function($crumbs) {
	  // Allows you to change the bread crumb trail displayed on a Test page
	});
	
	add_filter('tu_post_crumbs', function($crumbs) {
	  // Customise the breadcrumbs displayed on all Train-Up! post types
	});

##### JavaScript

	add_filter('tu_js_namespace', function($js) {
	  // Alter the localisation hash for the TU JavaScript namespace
	});
	
	add_filter('tu_questions_js_namespace', function($js) {
	  // Customise the TU_QUESTIONS JavaScript namespace
	});
	
	add_filter('tu_tests_js_namespace', function($js) {
	  // Allows you to alter the TU_TESTS JavaScript namespace
	});

##### Messages
	add_filter('tu_resource_schedule_not_ok', function($schedule) {
	  // Change the message displayed when a schedule prevents a trainee from accessing a resource
	});
	
	add_filter('tu_resource_group_schedule_not_ok', function($schedule) {
	  // Change the message displayed when a group schedule prevents a trainee from accessing a resource
	});

##### HTML
	add_filter('tu_archived_answers', function($view) {
	  // Allows you to change what is displayed for the [archvied_answers] shortcode
	});
	
	add_filter('tu_render_resource', function($content, $resource) {
	  // Allows you to change what is displayed on a given resource
	}, 10, 2);
	
	add_action('tu_user_form_extra_rows', function($form, $validator) {
	  // Print extra form fields on the sign-up/edit my details page
	}, 10, 2);
	
	add_action('tu_login_form_extra_rows', function($form, $validator) {
	  // Print extra form fields on the login page
	}, 10, 2);
	
	add_filter('tu_test_progress_bar', function($progress, $percent, $test) {
	  // Customise the progress bar for the given test
	}, 10, 3);
	
	add_filter('tu_comments_list_args', function($args) {
	  // Customise the arguments that render the comments, see wp_list_comments
	});
	
	add_filter('tu_comments_form_args', function($args) {
	  // Customise the arguments that render the comment form, see comment_form
	});
	
	add_filter('tu_question_title', function($title, $question) {
	  // Allows you to change the title of a question
	}, 10, 2);
	
	add_filter('tu_question_title_*', function($title, $question) {
	  // Allows you to change the title of a question for a specific question type, e.g. tu_question_title_multiple_choice
	}, 10, 2);
	
	add_filter('tu_form_label', function($label, $field_name) {
	  // Allows you to change the field label for a given field
	}, 10, 2);
	
	add_filter('tu_form_button', function($text, $field_name) {
	  // Lets you change the text on a button
	}, 10, 2);
	
	add_filter('tu_theme_header_links', function($html) {
	  // Lets you change the header links on the default Train-Up! theme
	});
	
	add_filter('tu_theme_footer_links', function($html) {
	  // Lets you change the footer links on the default Train-Up! theme
	});
	
	add_filter('tu_view_user_form', function($path) {
	  // Alter what file is used to render the sign up/edit my details form
	});
	
	add_filter('tu_view_forgotten_password_page', function($path) {
	  // Alter what file is used to render the forgotten password page
	});
	
	add_filter('tu_view_login_page', function($path) {
	  // Alter what file is used to render the login page
	});
	
	add_filter('tu_view_reset_password_page', function($path) {
	  // Alter what file is used to render the reset password page
	});
	
	add_action('tu_archived_answer_row', function($attempt) {
	  // Add a row to the Trainee's attempted answers view
	});
	
	add_filter('tu_archived_answer_response_header', function($string) {
	  // Lets you change the label 'Response' on the Trainee's answer sheet.
	});
	
	add_filter('tu_resource_walker_start_el', function($options) {
	  // Allows you to easily customise/hide the list of resources
	});
	
	add_filter('tu_question_walker_start_el', function($options) {
	  // Allows you to easily customise/hide the list of questions
	});

##### Post types

	add_filter('*_options', function($options) {
	  // Change the Custom Post Type options for the post type specified by *, e.g. tu_level_options
	});
	
	add_filter('tu_pre_get_*', function($query) {
	  // Lets you manipulate the query before post type is loaded, e.g. tu_pre_get_levels will filter Levels::find_all()
	});

##### Pagination

	add_filter('tu_question_pagination', function($pagination, $question) {
	  // Allows you to print out custom HTML for the pagination on a Question page
	}, 10, 2);
	
	add_filter('tu_resource_pagination', function($pagination, $resource) {
	  // Allows you to customise the pagination HTML displayed to navigate between questions
	}, 10, 2);

##### Custom Question Types
	add_filter('tu_save_answer', function($answer, $question) {
	  // Filter what gets saved when a Trainee saves their answer to a question
	}, 10, 2);
	
	add_filter('tu_save_answer_*', function($answer, $question) {
	  // Filter what gets saved for a particular question type specified by *, e.g. tu_save_answer_multiple_choice
	}, 10, 2);
	
	add_filter('tu_question_types', function($question_types) {
	  // Allows you to add a new question type
	});
	
	add_filter('tu_validate_answer', function($correct, $users_answer, $question) {
	  // Return whether or not a user's attempt at answering a question is right or wrong
	}, 10, 3);
	
	add_filter('tu_validate_answer_*', function($correct, $users_answer, $question) {
	  // Return whether or not the user's attempt at answer a specific question type is right or wrong, e.g. tu_validate_answer_multiple_choice
	}, 10, 3);
	
	add_filter('tu_save_answer_message', function($message) {
	  // Change the message that is displayed to a user when they save their answer to a question
	});
	
	add_filter('tu_render_answers', function($answers, $answer, $question) {
	  // Allows you to customise what is rendered when the possible answers are shown for a Question.
	}, 10, 3);
	
	add_filter('tu_render_answers_*', function($answers, $answer, $question) {
	  // Allows you to customise what is rendered when the possible answers are shown for a Question of a specific type, e.g. tu_render_answers_multiple_choice
	}, 10, 3);
	
	add_filter('tu_question_meta_boxes', function($meta_boxes) {
	  // Allows you to define a meta box on the Questions admin, use the tu_meta_box_* action for actually rendering the box
	});
	
	add_action('tu_meta_box_X', function() {
	  // Lets you output contents inside your own custom meta box
	});
	
	add_action('tu_save_question_X', function($question) {
	  // Lets you run code when a Question is altered in the backend, here is where you would handle saving your custom question
	});

##### Tin Can

	add_filter('tu_tin_can_interaction_type_X', function($type) {
	  // Allows you to specify the Tin Can Interaction type for a Train-Up! Question type, e.g. Train-Up!'s 'multiple_choice' is just 'choice' in Tin Can.
	});
	
##### Miscellaneous

	add_filter('tu_pre_get_X', function($query) {
	  // Lets you manipulate the user query, e.g. tu_pre_get_trainees would filter Trainees::find_all()
	});
	
	add_action('tu_finished_test', function($test, $user, $result) {
	  // Fired when a user completes a test
	}, 10, 3);
	
	add_action('tu_saved_answer', function($question, $answer) {
	  // Fired when a Trainee saves an answer to a Question
	}, 10, 2);
	
	add_action('tu_saved_answer_ajax', function($question, $answer) {
	  // Fired when a Trainee saves an answer to a Question via Ajax
	}, 10, 2);
	
##### Assets

	add_action('tu_question_backend_assets', function() {
	  // Lets you enqueue assets onto the Question administrator pages, useful for creating custom Questions that require JS/CSS
	});
	
	add_action('tu_question_frontend_assets', function() {
	  // Lets you enqueue custom assets to a Question page
	});
	
	add_action('tu_resource_frontend_assets', function() {
	  // Lets you enqueue custom assets onto a Resource page
	});
	
	add_action('tu_test_frontend_assets', function() {
	  // Lets you enqueue custom assets on to a Test page
	});
	
##### Validation

	add_action('tu_sign_up_validation_rules', function($rules) {
	  // Add more validation to the sign up form
	});
	
	add_action('tu_edit_my_details_validation_rules', function($rules) {
	  // Add more validation to the edit my details form
	});
	
##### Extending

	add_action('tu_install_cpt', function() {
	  // Register your own Custom Post Types that extend Train-Up!'s
	});
	
	add_action('tu_uninstall_cpt', function() {
	  // De-register your own Custom Post Types that extend Train-Up!'s
	});