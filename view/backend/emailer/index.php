<div class="wrap">
  <div id="icon-tu_plugin" class="icon32 icon32-posts-tu_level"><br></div>
  <h2><?php _e('Email', 'trainup') ?></h2>
  
  <form action="" method="POST" class="tu-emailer">

    <div class="tu-emailer-response"></div>

    <h3><?php _e('From', 'trainup'); ?>:</h3>
    <p>
      <select name="from">
        <?php foreach ($from_addresses as $from) { ?>
          <option>
            <?php
            if ($from['name']) {
              echo "{$from['name']} &lt;{$from['address']}&gt;";
            } else {
              echo $from['address'];
            }
            ?>
          </option>
        <?php } ?>
      </select>
    </p>

    <h3><?php _e('To', 'trainup'); ?>:</h3>
    <p>
      <input type="text" class="tu-autocompleter" data-autocomplete="Emailer" data-name="user_id" placeholder="<?php _e('Search', 'trainup'); ?>...">
      <button class="button tu-add-recipient">
        <?php _e('Add recipient', 'trainup'); ?>&nbsp;&raquo;
      </button>
    </p>

    <p>
      <select name="recipients[]" class="tu-recipients" multiple>
        <?php foreach ($users as $user) { ?>
          <option>
            <?php
            if ($user->display_name) {
              echo "{$user->display_name} &lt;{$user->user_email}&gt;";
            } else {
              echo $user->display_name;
            }
            ?>
          </option>
        <?php } ?>
      </select>
    </p>

    <p>
      <button class="button tu-remove-recipient">
        <?php _e('Remove selected', 'trainup'); ?>
      </button>
    </p>

    <br>

    <h3><?php _e('Subject', 'trainup'); ?>:</h3>
    <p>
      <input type="text" name="subject" value="" size="40">
    </p>

    <h3><?php _e('Body', 'trainup'); ?>:</h3>
    <?php
    wp_editor($default_template, 'tuemailer', array(
      'editor_class'  => 'tu-email-editor',
      'textarea_name' => 'body'
    ));
    ?>

    <p>
      <button class="button button-primary button-large">
        <?php _e('Send', 'trainup'); ?>&nbsp;&raquo;
      </button>
    </p>
  </form>
</div>