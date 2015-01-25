<form action="<?php echo $action_url; ?>" method="POST">

  <?php if (tu()->config['general']['login_by'] === 'user_login') { ?>
    <div class="tu-form-row">
      <div class="tu-form-label">
        <?php _e('Username', 'trainup'); ?>
      </div>
      <div class="tu-form-inputs">
        <?php $validator->error('user_login'); ?>
        <div class="tu-form-input tu-form-text">
          <input type="text" name="user[user_login]" value="<?php
            echo isset($form['user_login']) ? $form['user_login'] : '';
          ?>">
        </div>
      </div>
    </div>
  <?php } else { ?>
    <div class="tu-form-row">
      <div class="tu-form-label">
        <?php _e('Email address', 'trainup'); ?>
      </div>
      <div class="tu-form-inputs">
        <?php $validator->error('user_login'); ?>
        <div class="tu-form-input tu-form-text">
          <input type="text" name="user[user_email]" value="<?php
            echo isset($form['user_email']) ? $form['user_email'] : '';
          ?>">
        </div>
      </div>
    </div>
  <?php } ?>

  <div class="tu-form-row">
    <div class="tu-form-label">
      <?php _e('Password', 'trainup'); ?>
    </div>
    <div class="tu-form-inputs">
      <?php $validator->error('user_password'); ?>
      <div class="tu-form-input tu-form-text">
        <input type="password" name="user[user_password]">
      </div>
    </div>
  </div>

  <div class="tu-form-row">
    <div class="tu-form-label"></div>
    <div class="tu-form-inputs">
      <div class="tu-form-input tu-form-button">
        <?php wp_nonce_field('tu_login', 'tu_nonce'); ?>
        <button type="submit">
          <?php _e('Login', 'trainup'); ?>
        </button>
        <?php if (!empty($return_to)) { ?>
          <input type="hidden" name="return_to" value="<?php echo $return_to; ?>">
        <?php } ?>
      </div>
    </div>
  </div>

  <?php
  do_action('tu_login_form_extra_rows', $form, $validator);
  ?>

  <p class="tu-forgotten-password">
    <a href="<?php echo TU\Pages::factory('Forgotten_password')->url; ?>">
      <?php _e('Forgotten password?', 'trainup'); ?>
    </a>
  </p>

</form>
