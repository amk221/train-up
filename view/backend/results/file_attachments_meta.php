<?php
namespace TU;

if (count($uploads) < 1) { ?>
  <style>
  #file_attachments {
    display: none;
  }
  </style>
<?php } else { ?>
  <ul class="tu-file-attachments">
    <?php foreach ($uploads as $question_id => $files) {
      $question = Questions::factory($question_id);
      $title = $question->loaded() ? $question->post_title : "#{$question_id}"; ?>
      <li title="<?php echo $question->get_title(true, 100); ?>">
        <?php if ($question->loaded()) { ?>
          <a href="post.php?post=<?php echo $question->ID; ?>&amp;action=edit">
            <?php echo $question->post_title; ?>
          </a>
        <?php } else {
          echo $title;
        }

        if (count($files) > 0) { ?>
          <ul class="tu-bullet-list">
            <?php foreach ($files as $file) { ?>
              <li>
                <a href="<?php echo $file; ?>">
                  <?php echo basename($file); ?>
                </a>
              </li>
            <?php } ?>
          </ul>
        <?php } else {
          echo '&ndash;';
        } ?>
      </li>
    <?php } ?>
  </ul>
<?php } ?>