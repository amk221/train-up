Tips
====

* [WordPress Multisite](#wordpress-multisite)
* [Automatically jump to the next Question](#automatically-jump-to-the-next-question)
* [Add explanatory text if Trainees get a Question wrong](#add-explanatory-text-if-trainees-get-a-question-wrong)
* [Enable comments on Resource pages](#enable-comments-on-resource-pages)
* [Let group managers edit groups](#let-group-managers-edit-groups)
* [Register a new custom post type (Experimental)](register-a-new-custom-post-type-experimental)

##### WordPress Multisite
Train-Up! works in a multi-site environment so you can have multiple instances of the LMS.

To install, you must activate the plugin on each individual site, rather than choosing the Network Install option. This is because Train-Up! creates an archive database table to store Trainee's test results and this needs to exist for each site.

##### Automatically jump to next Question
When a Trainee saves their answer to a Question, you may wish to automatically move them on to the next Question. This is very easy to achieve but it does not come as a standard setting simply because other factors affect its success, such as: If the answer being saved via Ajax, and the button text you are using etc.

	// If this button is the Save Answer button, then change the text

	add_filter('tu_form_button', function($text, $type) {
	  if ($type === 'save_answer') {
	    return htmlentities('Save & next');
	  }
	}, 10, 2);

	// When an answer to a question is saved, get the next one and go to it!

	add_action('tu_saved_answer', function($response, $question, $answer) {
	  $question->get_next(true)->go_to();
	}, 10, 2);

##### Add explanatory text if Trainees get a Question wrong

When Trainees take a Test, they are presented with their attempted answers and whether or not each one was right or wrong. This is usually enough, but you may also want to add some explanatory text as to why they were wrong.

Add this code to your functions.php file:

	// Add support for meta fields to Questions
	// so you can add explanatory text.

	add_filter('tu_question_options', function($options) {
	  array_push($options['supports'], 'custom-fields');
	  return $options;
	});

	// Override the default answer-sheet template with your own

	add_filter('tu_archived_answers', function($view) {
	  $view = get_template_directory() . '/archived_answers';
	  return $view;
	});

Then, inside your archived_answers.php file you can load the explanatory text and render it wherever you like:

	// Print out why the user was wrong, for this specific question:
	echo get_post_meta($attempt['question_id'], 'explanation', true);

##### Enable comments on Resource pages

	add_filter('tu_resource_options', function($options) {
	  array_push($options['supports'], 'comments');
	  return $options;
	});

##### Let group managers edit groups

	add_filter('tu_group_manager_role', function($config) {
	  $config['capabilities']['edit_others_tu_groups'] = true;
	  return $config;
	});

##### Register a new custom post type (Experimental)

	// Install the 'Courses' Custom Post Type when Train-Up! is installed

	add_action('init', function() {
	  $courses = new TU\Course_post_type;
	  $courses->cache();
	});

	// Uninstall the 'Courses' Custom Post Type when Train-Up! is uninstalled

	add_action('tu_uninstall_cpt', function() {
	  $courses = new TU\Course_post_type;
	  $courses->forget();
	});

	// Give WordPress a 'Courses' management area in the Backend

	add_action('init', function() {
	  if (current_user_can('tu_backend')) {
	    new TU\Course_admin;
	  }
	});

	// Tell Train-Up! about the CPT for auto role generation

	add_filter('tu_known_post_type_names', function($post_types) {
	  $post_types[] = 'Courses';
	  return $post_types;
	});