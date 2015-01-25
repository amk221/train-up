<ul class="tu-bullet-list">
  <li>
    <a href="users.php?role=tu_trainee&amp;s=<?php echo $_group, ': ', $group->ID; ?>">
      <?php printf(__('View %1$s', 'trainup'), $_trainees); ?>&nbsp;&raquo;
    </a>
  </li>
  <li>
    <a href="users.php?role=tu_group_manager&amp;s=<?php echo $_group, ': ', $group->ID; ?>">
      <?php printf(__('View %1$s', 'trainup'), $_group_managers); ?>&nbsp;&raquo;
    </a>
  </li>
</ul>
