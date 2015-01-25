<div class="tu-trainees-box">
  <div class="tu-add-trainee">
    <p>
      <input type="text" class="tu-autocompleter" data-autocomplete="Trainees" data-name="trainee_id" placeholder="<?php _e('Search', 'trainup'); ?>...">
      <a href="#" class="button"><?php _e('Add', 'trainup'); ?>&nbsp;&raquo;</a>
    </p>
  </div>

  <select name="trainee_ids[]" size="10" multiple>
    <?php foreach ($trainees_in_group as $trainee) { ?>
      <option value="<?php echo $trainee->ID; ?>">
        <?php echo $trainee->display_name; ?>
      </option>
    <?php } ?>
  </select>

  <div class="tu-remove-trainee">
    <p>
      <a href="#" class="button"><?php _e('Remove selected', 'trainup'); ?></a>
    </p>
  </div>
</div>