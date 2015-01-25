<?php if ($message) { ?>
  <div id="message" class="tu-message <?php echo ($type === 'success') ? 'updated' : 'error'; ?>">
    <p><?php echo $message; ?></p>
  </div>
<?php } ?>