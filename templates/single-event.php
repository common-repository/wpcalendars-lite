<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header();

do_action( 'wpcalendars_before_main_content' );

while ( have_posts() ) : the_post();

if ( post_password_required() ) {
    echo get_the_password_form();
    return;
} ?>

<?php do_action( 'wpcalendars_before_single_event' ); ?>

<article <?php post_class() ?> itemscope="" itemtype="https://schema.org/CreativeWork">
    <?php do_action( 'wpcalendars_single_event' ); ?>
</article>

<?php do_action( 'wpcalendars_after_single_event' );

endwhile;

do_action( 'wpcalendars_after_main_content' );

do_action( 'wpcalendars_sidebar' );

get_footer();