<tr class="tu-archived-response">
  <th><?php _e('Response', 'trainup'); ?></th>
  <td>
    <textarea name="tu_answer_response[<?php echo $attempt['question_id']; ?>]"><?php
  echo isset($attempt['response']) ? $attempt['response'] : '';
?></textarea>
  </td>
</tr>