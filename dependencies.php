<?php

include('class/base/admin.php');
include('class/base/helper_misc.php');
include('class/base/helper_ajax.php');
include('class/base/helper_csv.php');
include('class/base/helper_comments.php');
include('class/base/helper_installer.php');
include('class/base/helper_message.php');
include('class/base/helper_roles.php');
include('class/base/helper_theme.php');
include('class/base/helper_tin_can.php');
include('class/base/helper_upgrader.php');
include('class/base/helper_uninstaller.php');
include('class/base/post_admin.php');
include('class/base/post_type.php');
include('class/base/post.php');
include('class/base/user_admin.php');
include('class/base/user.php');
include('class/base/validation.php');
include('class/base/view.php');
include('class/base/walkers.php');

include('class/widgets/dashboard.php');

include('class/levels/helpers.php');
include('class/levels/post.php');
include('class/levels/post_type.php');
include('class/levels/post_admin.php');

include('class/resources/admin.php');
include('class/resources/helpers.php');
include('class/resources/post.php');
include('class/resources/post_type.php');
include('class/resources/post_admin.php');

include('class/tests/helpers.php');
include('class/tests/post.php');
include('class/tests/post_type.php');
include('class/tests/post_admin.php');

include('class/groups/helpers.php');
include('class/groups/post.php');
include('class/groups/post_type.php');
include('class/groups/post_admin.php');

include('class/questions/admin.php');
include('class/questions/helpers.php');
include('class/questions/post.php');
include('class/questions/post_type.php');
include('class/questions/post_admin.php');

include('class/results/admin.php');
include('class/results/helpers.php');
include('class/results/post.php');
include('class/results/post_type.php');
include('class/results/post_admin.php');

include('class/pages/helpers.php');
include('class/pages/post.php');
include('class/pages/post_type.php');
include('class/pages/post_admin.php');
include('class/pages/page_login.php');
include('class/pages/page_logout.php');
include('class/pages/page_sign_up.php');
include('class/pages/page_forgotten_password.php');
include('class/pages/page_reset_password.php');
include('class/pages/page_my_account.php');
include('class/pages/page_edit_my_details.php');
include('class/pages/page_my_results.php');

include('class/users/helpers.php');

include('class/trainees/helpers.php');
include('class/trainees/user.php');
include('class/trainees/user_admin.php');

include('class/group_managers/helpers.php');
include('class/group_managers/user.php');
include('class/group_managers/user_admin.php');

include('class/administrators/helpers.php');
include('class/administrators/user.php');
include('class/administrators/user_admin.php');

include('class/settings/helpers.php');
include('class/settings/admin.php');

include('class/emailer/helpers.php');
include('class/emailer/admin.php');

include('class/importer/helpers.php');

include('class/debug/helpers.php');
include('class/debug/fixtures.php');
include('class/debug/test_suite.php');
include('class/debug/admin.php');

include('class/tin_can/actor.php');
include('class/tin_can/activity.php');
include('class/tin_can/result.php');
include('class/tin_can/score.php');
include('class/tin_can/verbs.php');
include('class/tin_can/statement.php');

// Allow developers to bootstrap the plugin
@include(get_stylesheet_directory() . '/tu_bootstrap.php');

include('class/plugin.php');

function tu() {
  return TU\Plugin::instance();
}

