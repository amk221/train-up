<table class="tu-group-choice form-table">
  <tr>
    <th>
      <label for="tu-groups">
        <?php echo $_groups; ?>
      </label>
      <p class="description">
        <?php echo $description; ?>
      </p>
    </th>
    <td>
      <select name="tu_groups[]" id="tu-groups" multiple>
        <?php foreach ($groups as $group) { ?>
          <option value="<?php echo $group->ID ?>"<?php
            echo $user->has_group($group->ID) ? ' selected' : '';
          ?>>
            <?php echo $group->post_title; ?>
          </option>
        <?php } ?>
      </select>
    </td>
  </tr>
</table>