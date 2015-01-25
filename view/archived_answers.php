<?php

  /**
   * Note to developers:
   *
   * You are encouraged to override this file so you can display a Trainee's
   * answers in a format to your liking.
   *
   * To do so is simple:
   *
   * add_filter('tu_archived_answers', function($view) {
   *   $view = get_template_directory() . '/archived_answers';
   *   return $view;
   * });
   *
   * Train-Up! will now use your archived_answers.php file instead of this one.
   */

?>
<article class="tu-archived-answers">
  <h1><?php echo $archive['test_title']; ?></h1>
  <?php foreach ($archive['answers'] as $i => $attempt) { ?>
    <table class="tu-table tu-archived-answer">
      <tr class="tu-archived-question">
        <th><?php printf(__('Question %1$s', 'trainup'), $i + 1); ?></th>
        <td><?php echo $attempt['question']; ?></td>
      </tr>
      <tr class="tu-archived-attempt">
        <th><?php _e('Attempt', 'trainup'); ?></th>
        <td>
          <?php if ($attempt['type'] === 'files') { ?>
            <ul class="tu-answer-parts">
              <?php foreach ($attempt['files'] as $file) { ?>
                <li>
                  <a href="<?php echo $file['url']; ?>">
                    <?php echo $file['name']; ?>
                  </a>
                </li>
              <?php } ?>
            </ul>
          <?php } if ($attempt['type'] === 'string') {
              echo $attempt['answer'];
            } else if ($attempt['type'] === 'array') { ?>
            <ul class="tu-answer-parts">
              <?php foreach ($attempt['answer'] as $part) { ?>
                <li><?php echo $part; ?></li>
              <?php } ?>
            </ul>
          <?php } else if ($attempt['type'] === 'hash') { ?>
            <dl>
              <?php foreach ($attempt['answer'] as $key => $value) { ?>
                <dt><?php echo $key; ?></dt>
                <dd><?php echo $value; ?></dd>
              <?php } ?>
            </dl>
          <?php } ?>
        </td>
      </tr>
      <?php if (!is_null($attempt['correct'])) { ?>
        <tr class="tu-archived-correctness">
          <th><?php _e('Result', 'trainup'); ?></th>
          <td class="tu-correct-<?php echo (int)$attempt['correct']; ?>">
            <span>
              <?php
              echo $attempt['correct']
                ? __('Correct', 'trainup')
                : __('Incorrect', 'trainup');
              ?>
            </span>
            <div>
              <?php
              echo $attempt['correct']
                ? '&#010003;'
                : '&#010007;';
              ?>
            </div>
          </td>
        </tr>
      <?php }
      if (!empty($attempt['response']) and tu()->in_frontend()) { ?>
        <tr class="tu-archived-response">
          <th>
            <?php
            echo apply_filters(
              'tu_archived_answer_response_header',
              __('Response', 'trainup')
            );
            ?>
          </th>
          <td>
            <?php
              echo stripslashes(apply_filters(
                'tu_archived_answer_response_text',
                $attempt['response'],
                $attempt,
                $archive
              ));
            ?>
          </td>
        </tr>
      <?php }
      do_action('tu_archived_answer_row', $attempt);
      ?>
    </table>
  <?php } ?>
</article>