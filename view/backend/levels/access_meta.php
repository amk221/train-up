<p>
  <select name="tu_groups[]" multiple>
    <?php foreach ($groups as $group) { ?>
      <option value="<?php echo $group->ID ?>"<?php
        echo $level->has_group($group->ID) ? ' selected' : '';
      ?>>
        <?php echo $group->post_title; ?>
      </option>
    <?php } ?>
  </select>
</p>
