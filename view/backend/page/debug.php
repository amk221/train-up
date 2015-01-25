<div class="wrap">
  <div id="icon-tu_plugin" class="icon32 icon32-posts-tu_level"><br></div>
  <h2 class="tu-settings-title"><?php _e('Debug', 'trainup'); ?></h2>

  <p>
    <?php if ($has_fixtures) { ?>
      <a class="button" href="admin.php?page=tu_debug&amp;tu_action=uninstall_fixtures">Uninstall fixtures</a>
    <?php } else { ?>
      <a class="button" href="admin.php?page=tu_debug&amp;tu_action=install_fixtures">Install fixtures</a>
    <?php } ?>

    &nbsp;

    <a class="button" href="admin.php?page=tu_debug&amp;tu_action=run_tests">Run tests</a>

    &nbsp;

    <a class="button" href="admin.php?page=tu_debug&amp;tu_action=delete_redundant_files">Delete redundant files</a>

  </p>

  <?php if ($test_suite_output) { ?>
    <pre>
<?php
print_r($test_suite_output);
?>
    </pre>
  <?php } ?>

</div>