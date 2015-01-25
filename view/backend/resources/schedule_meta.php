<script type="text/html" class="tu-schedule-template">
  <tr class="tu-schedule">
    <td class="tu-schedule-group"><%=groupName%></td>
    <td class="tu-schedule-date"><%=dateTimeStr%></td>
    <td class="tu-schedule-action">
      <input type="hidden" name="tu_schedule[<%=groupID%>]" value="<%=dateTime%>">
      <a href="#" class="tu-remove">&times;</a>
    </td>
  </tr>
</script>

<table class="tu-schedules form-table">
  <thead>
    <tr>
      <th class="tu-schedule-group"><?php _e('Group', 'trainup'); ?></th>
      <th class="tu-schedule-date"><?php _e('Date', 'trainup'); ?></th>
      <th class="tu-schedule-action">&nbsp;</th>
    </tr>
  </thead>
  <tbody>
    <tr class="tu-add-schedule">
      <td class="tu-schedule-group">
        <select name="tu_group" class="tu-add-schedule-groups">
          <option value="all">
            <?php _e('All', 'trainup'); ?>
          </option>
          <option value="" disabled>
            &mdash;
          </option>
          <?php foreach ($groups as $group) { ?>
            <option value="<?php echo $group->ID ?>">
              <?php echo $group->post_title; ?>
            </option>
          <?php } ?>
        </select>
      </td>
      <td class="tu-schedule-date">
        <input type="datetime-local" class="tu-add-schedule-datetime" placeholder="DD/MM/YYYY HH:MM">
      </td>
      <td class="tu-schedule-action">
        <button class="tu-add-schdule-button button">
          <?php _e('Add', 'trainup'); ?>
        </button>
      </td>
    </tr>
    <?php foreach ($schedules as $group_id => $schedule) { ?>
      <tr class="tu-schedule tu-schedule-<?php echo (int)$schedule['ok']; ?>">
        <td class="tu-schedule-group"><?php
          if ($group_id === 'all') {
            printf(__('All %1$s', 'trainup'), $_groups);
          } else {
            echo $schedule['group']->post_title;
          }
        ?></td>
        <td class="tu-schedule-date"><?php
          echo date_i18n('D M m Y \@ h:i:s a', $schedule['timestamp']);
        ?></td>
        <td class="tu-schedule-action">
          <input type="hidden" name="tu_schedule[<?php echo $group_id ?>]" value="<?php echo $schedule['datetime']; ?>">
          <a href="#" class="tu-remove">&times;</a>
        </td>
      </tr>
    <?php } ?>
  </tbody>
</table>
