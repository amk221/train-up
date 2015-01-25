<p class="tu-add">
  <span class="tu-plus">+</span>
  <a href="#" class="tu-add-new-answer">
    <?php _e('Add another answer', 'trainup'); ?>
  </a>
</p>

<script type="text/html" class="tu-answer-template">
  <tr class="tu-answer">
    <td class="tu-index"><%=(number + 1)%></td>
    <?php if (isset($correct_answer)) { ?>
      <td class="tu-answer-correct">
        <input type="radio" name="correct_answer" value="<%=number%>">
      </td>
    <?php } ?>
    <td class="tu-answer">
      <input type="text" name="multiple_answer[]" value="" size="40" autocomplete="off" tabindex="<%=number%>">
    </td>
    <td class="tu-answer-remove">
      <a href="#" class="tu-remove">&times;</a>
    </td>
  </tr>
</script>

<table class="tu-answers form-table">
  <thead>
    <tr>
      <th class="tu-index">#</th>
      <?php if (isset($correct_answer)) { ?>
        <th class="tu-answer-correct"><?php _e('Correct?', 'trainup'); ?></th>
      <?php } ?>
      <th class="tu-answer"><?php _e('Answer', 'trainup'); ?></th>
      <th class="tu-answer-remove">&nbsp;</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($answers as $i => $answer) { ?>
      <tr class="tu-answer">
        <td class="tu-index"><?php echo $i + 1; ?></td>
        <?php if (isset($correct_answer)) { ?>
          <td class="tu-answer-correct">
            <input type="radio" name="correct_answer" value="<?php echo $i; ?>"<?php
              echo $answer == $correct_answer ? ' checked' : '';
            ?>>
          </td>
        <?php } ?>
        <td class="tu-answer">
          <input type="text" name="multiple_answer[]" value='<?php echo $answer; ?>' size="40" autocomplete="off" tabindex="<?php echo $i; ?>">
        </td>
        <td class="tu-answer-remove">
          <a href="#" class="tu-remove">&times;</a>
        </td>
      </tr>
    <?php } ?>
  </tbody>
</table>
