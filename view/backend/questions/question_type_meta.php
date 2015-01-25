<?php foreach ($question_types as $name => $title) { ?>
  <p>
    <label class="tu-question-type-choice">
      <input type="radio" name="question_type" value="<?php echo $name; ?>"<?php
        echo $question_type === $name ? ' checked' : '';
      ?>>
      <?php echo $title; ?>
    </label>
  </p>
<?php } ?>
