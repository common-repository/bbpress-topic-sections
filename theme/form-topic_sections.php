<?php do_action( 'bbp_ts_theme_before_sections_form' ); ?>
<?php
while ( bbp_ts_sections() ) : bbp_ts_the_section();
    bbp_ts_locate_template('form-single-topic_section.php',true);
endwhile;
?>
<?php do_action( 'bbp_ts_theme_after_sections_form' ); ?>