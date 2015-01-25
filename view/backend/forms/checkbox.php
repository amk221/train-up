<label>
  <input type="checkbox" name="<?php echo $name; ?>" value="1"<?php
    echo $value == 1 ? ' checked' : ''
  ?>>
  <?php echo $description; ?>
</label>
<?php if (isset($help)) { ?>
  <span class="description"><?php echo $help; ?></span>
<?php } ?>