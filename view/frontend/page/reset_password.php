<form action="<?php echo $action_url; ?>" method="POST">

  <div class="tu-form-row">
    <div class="tu-form-label">
      <?php _e('New password', 'trainup'); ?>
    </div>
    <div class="tu-form-inputs">
      <?php $validator->error('user_pass'); ?>
      <div class="tu-form-input tu-form-text">
        <input type="password" name="user[user_pass]">
      </div>
    </div>
  </div>

  <div class="tu-form-row">
    <div class="tu-form-label">
      <?php _e('Confirm new password', 'trainup'); ?>
    </div>
    <div class="tu-form-inputs">
      <?php $validator->error('confirm_user_pass'); ?>
      <div class="tu-form-input tu-form-text">
        <input type="password" name="user[confirm_user_pass]">
      </div>
    </div>
  </div>

  <div class="tu-form-row">
    <div class="tu-form-label"></div>
    <div class="tu-form-inputs">
      <div class="tu-form-input tu-form-button">
        <?php wp_nonce_field('tu_reset_password', 'tu_nonce'); ?>
        <input type="hidden" name="user[user_activation_key]" value="<?php
          echo isset($form['user_activation_key'])
            ? $form['user_activation_key'] : '';
        ?>">
        <button type="submit">
          <?php _e('Save', 'trainup'); ?>
        </button>
      </div>
    </div>
  </div>

</form>