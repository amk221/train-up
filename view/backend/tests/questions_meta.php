<?php if ($is_editing) { ?>

  <?php if ($can_edit) { ?>
    <p class="tu-add">
      <a href="post-new.php?post_type=tu_question_<?php echo $test_id; ?>"
        class="tu-add-new-question">
        <?php _e('Add a new question', 'trainup'); ?></a>
        |
        <a href="#" class="tu-importer-link">
          <?php _e('Import', 'trainup'); ?>
        </a>
    </p>
  <?php } ?>

  <table class="tu-questions form-table<?php echo $can_edit ? '' : ' tu-questions-uneditable'; ?>">
    <?php if (count($questions) > 0) { ?>
      <tr>
        <th class="tu-index">#</th>
        <th class="tu-question"><?php _e('Question', 'trainup'); ?></th>
        <?php if ($can_edit) { ?>
          <th class="tu-question-remove">&nbsp;</th>
        <?php } ?>
      </tr>
      <?php foreach ($questions as $question) { ?>
        <tr class="tu-question">
          <td class="tu-index"><?php echo $question->menu_order; ?></td>
          <td class="tu-question">
            <a href="post.php?post=<?php echo $question->ID; ?>&amp;action=edit">
              <?php echo $question->get_title(true, 100) ?: __('Untitled', 'trainup'); ?>
            </a>
          </td>
          <?php if ($can_edit) { ?>
            <td class="tu-question-remove">
              <a href="post.php?post=<?php echo $_GET['post']; ?>&amp;action=edit&amp;tu_remove_question=<?php echo $question->ID; ?>" class="tu-remove">
                &times;
              </a>
            </td>
          <?php } ?>
        </tr>
      <?php } ?>
    <?php } else { ?>
      <td><?php _e('No questions added yet', 'trainup'); ?></td>
    <?php } ?>
  </table>

<?php } else { ?>
  <div class="tu-unpublished-test">
    <p>
      <?php printf(__('Please save this %1$s before adding questions.', 'trainup'), $_test); ?>
    </p>
  </div>
<?php } ?>