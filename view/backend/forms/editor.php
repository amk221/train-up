<?php
$id = preg_replace('/[^a-z]/', '', $name);
?>
<div class="tu-template-editor-container">
  <button class="tu-template-editor-button button"><?php _e('Edit', 'trainup'); ?></button>

  <?php
  wp_editor(
    $value,
    $id,
    array(
      'editor_class'  => 'tu-template-editor',
      'textarea_name' => $name
    )
  );
  ?>
  <script>
  jQuery('#wp-<?php echo $id ?>-wrap').hide();
  </script>
</div>
