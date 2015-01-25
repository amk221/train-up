<ul>
  <?php foreach ($shortcodes as $name => $details) { ?>

    <?php
    if (preg_match('/^\!/', $details['shortcode'])) {
      continue;
    }
    ?>

    <li>[<?php

    echo $details['shortcode'];

    $str = '';
    foreach ($details['attributes'] as $attribute => $defaults) {
      $str .= $attribute.'="'.$defaults.'" ';
    }

    $str = trim($str);
    echo $str ? " {$str}" : '';

    ?>]</li>
  <?php } ?>
</ul>

