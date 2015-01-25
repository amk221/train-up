<div class="wrap">
  <div id="icon-tu_plugin" class="icon32 icon32-posts-tu_level"><br></div>
  <h2><?php echo $_resources; ?></h2>

  <p>
    <?php
    printf(
      __('Please select which %1$s you would like to manage the %2$s for:', 'trainup'),
      '<a href="edit.php?post_type=tu_level">'.strtolower($_level).'</a>',
      strtolower($_resources)
    );
    ?>
  </p>

  <form action="edit.php" method="GET">
    <select name="post_type">
      <option value="">...</option>
      <?php foreach ($levels as $level) { ?>
        <option value="tu_resource_<?php echo $level->ID; ?>">
          <?php echo str_repeat('&nbsp;', $level->depth * 2), $level->post_title; ?>
        </option>
      <?php } ?>
    </select>
    <button class="button button-primary">
      <?php _e('Manage', 'trainup'); ?> <?php echo strtolower($_resources); ?>&nbsp;&raquo;
    </button>
  </form>

</div>