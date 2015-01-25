<div class="wrap">
  <div id="icon-tu_plugin" class="icon32 icon32-posts-tu_level"><br></div>
  <h2><?php _e('Results', 'trainup'); ?></h2>

  <p>
    <?php
    printf(
      __('Please select which %1$s you would like to see the results for:', 'trainup'),
      "<a href='edit.php?post_type=tu_test'>{$_test}</a>"
    );
    ?>
  </p>

  <form action="edit.php" method="GET">
    <select name="post_type">
      <option value="">...</option>
      <?php foreach ($levels as $level) { ?>
        <option <?php
        if ($level->test) {
          echo "value='tu_result_{$level->test->ID}'";
        } else {
          echo 'disabled';
        }
        ?>>
          <?php echo str_repeat('&nbsp;', $level->depth * 2), $level->post_title; ?>
        </option>
      <?php } ?>
    </select>
    <button class="button button-primary">
      <?php _e('View results', 'trainup'); ?>&nbsp;&raquo;
    </button>
  </form>

</div>