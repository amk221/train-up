<p class="tu-single-answer-config">
  <span>
    <?php _e('The correct answer', 'trainup'); ?>
  </span>
  <select name="single_answer_comparison">
    <?php foreach ($comparisons as $name => $comparison) { ?>
      <option
        value="<?php echo $name; ?>"
        class="<?php echo $comparison['cmi']; ?>"<?php
          echo $comparison_name === $name
            ? ' selected' : ''; ?>>
        <?php echo $comparison['title']; ?>
      </option>
    <?php } ?>
  </select>
  <input type="text" name="single_answer" value="<?php echo $correct_answer; ?>" size="35" autocomplete="off">
  <input type="text" name="pattern_modifier" value="<?php echo $pattern_modifier; ?>" size="3" autocomplete="off">
</p>

<div class="tu-answer-checker">
  <p class="tu-answer-checker-intro">
    <?php printf(__('If a %1$s entered...', 'trainup'), $_trainee); ?>
  </p>
  <p class="tu-answer-checker-answer">
    <textarea></textarea>
  </p>
  <p class="tu-answer-checker-result">
    <?php _e('...they would be:', 'trainup'); ?>
    <span class="incorrect"><?php _e('incorrect', 'trainup'); ?></span>
  </p>
</div>
