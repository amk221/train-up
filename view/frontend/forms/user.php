<form action="" method="POST" autocomplete="off">

  <div class="tu-form-row">
    <div class="tu-form-label">
      <?php _e('First name', 'trainup'); ?>
    </div>
    <div class="tu-form-inputs">
      <?php $validator->error('first_name'); ?>
      <div class="tu-form-input tu-form-text">
        <input type="text" name="user[first_name]" value="<?php
          echo isset($form['first_name']) ? $form['first_name'] : '';
        ?>">
      </div>
    </div>
  </div>

  <div class="tu-form-row">
    <div class="tu-form-label">
      <?php _e('Last name', 'trainup'); ?>
    </div>
    <div class="tu-form-inputs">
      <?php $validator->error('last_name'); ?>
      <div class="tu-form-input tu-form-text">
        <input type="text" name="user[last_name]" value="<?php
          echo isset($form['last_name']) ? $form['last_name'] : '';
        ?>">
      </div>
    </div>
  </div>

  <?php if ($type === 'sign-up' && $config['general']['login_by'] === 'user_login') { ?>
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
  <?php } ?>

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
    <div class="tu-form-label">
      <?php
      _e('Password', 'trainup');

      if ($type === 'edit') { ?>
        <div class="tu-form-hint">
          <?php _e('Leave blank to keep the same', 'trainup'); ?>
        </div>
      <?php } ?>
    </div>
    <div class="tu-form-inputs">
      <?php $validator->error('user_pass'); ?>
      <div class="tu-form-input tu-form-text">
        <input type="password" name="user[user_pass]" value="">
      </div>
    </div>
  </div>

  <?php
  if (
    $config['trainees']['can_choose_groups'] !== 'disabled' &&
    $type === 'edit' || (
      !empty($config['groups']['show_groups_on_sign_up']) &&
      $type === 'sign-up'
    )
  ) { ?>
    <div class="tu-form-row">
      <div class="tu-form-label">
        <?php
        echo $config['trainees']['can_choose_groups'] === 'multiple' ?
          $config['groups']['plural'] : $config['groups']['single']
        ?>
      </div>
      <div class="tu-form-inputs">
        <?php $validator->error('groups'); ?>

        <?php if ($config['trainees']['can_choose_groups'] === 'single') { ?>
          <div class="tu-form-input tu-form-text">
            <input type="hidden" name="user[groups]" value="<?php
              echo count($trainee_groups) > 0 ? $trainee_groups[0]->ID : '';
            ?>">
            <input type="text" class="tu-autocompleter" data-autocomplete="Groups" data-name="user[groups]" value="<?php
              echo count($trainee_groups) > 0 ? $trainee_groups[0]->post_title : '';
            ?>">
          </div>
        <?php } else { ?>
          <div class="tu-form-input tu-form-select">
            <select name="user[groups][]" multiple>
              <?php foreach ($all_groups as $group) { ?>
                <option value="<?php echo $group->ID ?>"<?php
                  if (isset($form['groups'])) {
                    foreach ((array)$form['groups'] as $group_id) {
                      if ($group->ID == $group_id) {
                        echo ' selected';
                      }
                    }
                  }
                ?>>
                  <?php echo $group->post_title; ?>
                </option>
              <?php } ?>
            </select>
          </div>
        <?php } ?>
      </div>
    </div>
  <?php } ?>

  <?php
  do_action('tu_user_form_extra_rows', isset($form) ? $form : array(), $validator);
  ?>

  <div class="tu-form-row">
    <div class="tu-form-label"></div>
    <div class="tu-form-inputs">
      <div class="tu-form-input tu-form-button">
        <?php wp_nonce_field('tu_user_form', 'tu_nonce'); ?>
        <button type="submit">
          <?php echo $submit_text ?>
        </button>
      </div>
    </div>
  </div>

</form>