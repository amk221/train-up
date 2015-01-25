<?php if (count($crumbs) > 1) { ?>
  <div class="tu-breadcrumb-trail">
    <span class="tu-you-are-here">
      <?php _e('You are here:', 'trainup'); ?>
    </span>
    <ol>
      <?php foreach ($crumbs as $crumb) { ?>
        <li>
          <?php if ($crumb['url'] === '#') { ?>
            <span><?php echo $crumb['title']; ?></span>
          <?php } else { ?>
            <a href="<?php echo $crumb['url']; ?>"><?php echo $crumb['title']; ?></a>
          <?php } ?>
        </li>
      <?php } ?>
    </ol>
  </div>
<?php } ?>