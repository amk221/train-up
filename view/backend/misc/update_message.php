<?php

printf(
  __('%1$s is out of date. Please consider updating the plugin by visiting:', 'trainup'),
  tu()->get_name()
);

?>

<a href="<?php echo $versions_url; ?>"><?php echo $versions_url; ?></a>