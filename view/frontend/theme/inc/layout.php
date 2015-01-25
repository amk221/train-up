<?php
namespace TU;
$title = tu()->config['general']['title'];
?>
<!doctype html>
<html>
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width">

<title><?php echo $title, ' &rsaquo; ', tu()->post->post_title; ?></title>

<!--[if lt IE 9]>
  <script src="http://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
  <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

<?php wp_head(); ?>

</head>
<body <?php body_class(get_post_class()); ?>>

<header class="header-main">
  <div class="wrapper">
    <h1>
      <a href="<?php echo get_bloginfo('url'), '/', tu()->config['general']['main_slug']; ?>">
        <?php      
        if (tu()->post->featured_image) {
          ?><img src="<?php echo tu()->post->featured_image; ?>"><?php
        } else if (tu()->config['general']['header'] === 'image') {
          ?><img src="<?php echo tu()->config['general']['header_image'] ?>"><?php
        } else { 
          echo $title;
        }
        ?>
      </a>
    </h1>
    <?php if (is_user_logged_in()) { ?>
      <nav class="links-header">
        <?php do_action('tu_theme_header_links'); ?>

        <a href="<?php echo Pages::factory('My_account')->url; ?>" class="button">
          <?php echo tu()->user->display_name; ?>
        </a>
      </nav>
    <?php } ?>
  </div>
</header>

<?php
$crumbs = tu()->post->breadcrumb_trail;
if (count($crumbs) >=1 ) { ?>
  <nav class="breadcrumb-trail">
    <div class="wrapper">
      <span class="you-are-here">
        <?php _e('You are here:', 'trainup'); ?>
      </span>
      <ol>
        <?php foreach ($crumbs as $crumb) { ?>
          <li>
            <?php if (isset($crumb['url'])) { ?>
              <a href="<?php echo $crumb['url']; ?>"><?php echo $crumb['title']; ?></a>
            <?php } else { ?>
              <span><?php echo $crumb['title']; ?></span>
            <?php } ?>
          </li>
        <?php } ?>
      </ol>
    </div>
  </nav>
<?php } ?>

<?php the_post(); ?>
<article class="article-main">
  <div class="wrapper">
    <?php tu()->theme_helper->render_test_progress_bar(); ?>

    <h1 class="heading-main">
      <?php the_title(); ?>
    </h1>

    <?php
    the_content();

    if (isset(tu()->result) && tu()->config['tests']['result_comments']) {
      tu()->theme_helper->render_comments();
    }
    ?>
  </div>
</article>

<footer class="footer-main">
  <div class="wrapper">
    <?php
    echo apply_filters(
      'tu_theme_footer_links',
      '<a href="' . tu()->get_homepage() . '">' .
        sprintf(__('Powered by %1$s', 'trainup'), tu()->get_name()) .
      '</a>'
    );
    ?>
  </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>