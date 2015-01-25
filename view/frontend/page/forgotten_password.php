<form action="<?php echo $action_url; ?>" method="POST">

  <div class="tu-form-row">
    <div class="tu-form-label">
      <?php _e('Email address', 'trainup'); ?>
    </div>
    <div class="tu-form-inputs">
      <?php $validator->error('user_email'); ?>
      <div class="tu-form-input tu-form-text">
        <input type="text" name="user[user_email]" value="<?php
          echo isset($form['user_email']) ? $form['user_email'] : '';
        ?>">
      </div>
    </div>
  </div>

  <div class="tu-form-row">
    <div class="tu-form-label"></div>
    <div class="tu-form-inputs">
      <div class="tu-form-input tu-form-button">
        <?php wp_nonce_field('tu_forgotten_password', 'tu_nonce'); ?>
        <button type="submit">
          <?php _e('Get new password', 'trainup'); ?>
        </button>
      </div>
    </div>
  </div>

  <p class="tu-back-to-login">
    <a href="<?php echo TU\Pages::factory('Login')->url; ?>">
      &laquo; <?php _e('Back to Login', 'trainup'); ?>
    </a>
  </p>

</form>