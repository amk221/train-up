<form class="tu-answers" action="" method="POST">
  <?php if ($question_type === 'multiple') {
    if (isset($multiple_answers)) { ?>
      <div class="tu-multiple-answers">
        <?php foreach ($multiple_answers as $i => $answer) { ?>
          <div class="tu-form-row">
            <div class="tu-form-label">
              <?php echo apply_filters('tu_form_label', __('Your answer:', 'trainup'), 'your_answer'); ?>
            </div>
            <div class="tu-form-inputs">
              <label class="tu-form-input tu-form-radio">
                <input type="radio" name="tu_answer" value="<?php
                  echo $answer;
                ?>"<?php
                  echo ($answer == $users_answer) ? ' checked' : '';
                ?>>
                <?php echo $answer; ?>
              </label>
            </div>
          </div>
        <?php } ?>

        <div class="tu-form-row">
          <div class="tu-form-label"></div>
          <div class="tu-form-inputs">
            <div class="tu-form-input tu-form-button">
              <button type="submit">
                <?php echo apply_filters('tu_form_button', __('Save my answer', 'trainup'), 'save_answer'); ?>
              </button>
            </div>
          </div>
        </div>
      </div>
    <?php } else {
      tu()->message->render('error', __('No answers set for this Question', 'trainup'));
    }
  } else if ($question_type === 'single') { ?>
    <div class="tu-single-answer">
      <div class="tu-form-row">
        <div class="tu-form-label">
          <?php echo apply_filters('tu_form_label', __('Your answer:', 'trainup'), 'your_answer'); ?>
        </div>
        <div class="tu-form-inputs">
          <div class="tu-form-input tu-form-text">
            <textarea name="tu_answer" rows="5" cols="60"><?php echo $users_answer; ?></textarea>
          </div>
        </div>
      </div>

      <div class="tu-form-row">
        <div class="tu-form-label"></div>
        <div class="tu-form-inputs">
          <div class="tu-form-input tu-form-button">
            <button type="submit">
              <?php echo apply_filters('tu_form_button', __('Save my answer', 'trainup'), 'save_answer'); ?>
            </button>
          </div>
        </div>
      </div>
    </div>
  <?php } ?>
</form>