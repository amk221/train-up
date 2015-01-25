<?php foreach ($options as $key => $label) { ?>
<label>
  <input type="checkbox" name="<?php echo $name; ?>[<?php echo $key; ?>]" value="1"<?php
    echo isset($value[$key]) && $value[$key] == 1 ? ' checked' : ''
  ?>>
  <?php echo $label; ?>
</label>
<br>
<?php } ?>

<?php if (isset($help)) { ?>
  <p class="description"><?php echo $help; ?></p>
<?php } ?>