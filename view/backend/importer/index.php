<div class="tu-importer-dialog">
  <form class="tu-importer" action="" method="POST" enctype="multipart/form-data">

    <div class="tu-import-result">

    </div>

    <h4 class="tu-dialog-title">
      <?php printf(
        __('Import questions into the %1$s', 'trainup'),
        strtolower($_test)
      ); ?>
    </h4>
    <p>
      <input type="hidden" value="<?php echo $test_id; ?>" id="tu-importer-test-id">
      <input type="file" id="tu-import-file">
    </p>

    <progress value="0" max="100" class="tu-import-progress-bar"></progress>

    <div class="tu-importer-supports">
      <p>
        <?php _e('Supported formats include:', 'trainup'); ?>
      </p>
      <ul class="tu-bullet-list">
        <li>
          <a href="http://docs.moodle.org/24/en/Moodle_XML_format" target="_blank">
            <?php _e('Moodle XML', 'trainup'); ?>
          </a>
        </li>
        <li>
          <a href="<?php echo tu()->get_homepage();?>" target="_blank">
            <abbr title="<?php _e('Application Programming Interface', 'trainup'); ?>">
              <?php _e('API', 'trainup'); ?>
            </abbr>
          </a>
        </li>
      </ul>
    </div>

  </form>
</div>