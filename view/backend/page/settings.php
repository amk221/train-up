<?php
if ($active_section_slug === 'tin_can') {
  tu()->message->render('notice', sprintf(
    __('The Tin-Can API functionality is in beta. Please visit %1$s to submit suggestions.', 'trainup'),
    '<a href="'.tu()->get_homepage() . '/contact">'.tu()->get_homepage() . '/contact</a>'
  ));
}
?>

<form class="wrap tu-settings-form" method="POST" action="options.php">
  <div id="icon-tu_plugin" class="icon32 icon32-posts-tu_level"><br></div>
  <h2 class="tu-settings-title"><?php echo $plugin_name, ' ', __('Settings', 'trainup'); ?></h2>

  <?php settings_errors(); ?>

  <h3 class="nav-tab-wrapper">
    <?php foreach ($structure as $section_slug => $section) { ?>
      <a href="?page=tu_settings&amp;section=<?php echo $section_slug; ?>" class="nav-tab<?php
        echo $active_section_slug === $section_slug ? ' nav-tab-active' : '';
      ?>">
        <?php
        if (isset($config[$section_slug]['plural'])) {
          echo $config[$section_slug]['plural'];
        } else {
          echo $section['title'];
        }
        ?>
      </a>
    <?php } ?>
  </h3>
    
  <?php
  settings_fields($active_section_id);
  do_settings_sections($active_section_id);
  
  submit_button();
  ?>
</form>