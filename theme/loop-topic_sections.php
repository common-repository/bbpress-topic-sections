<?php do_action( 'bbp_ts_template_before_topic_sections_loop' ); ?>

<?php
while ( bbp_ts_sections() ) : bbp_ts_the_section();
    if (!bbp_ts_section_has_content()) continue;
    bbp_ts_locate_template('content-single-topic_section.php',true);
endwhile;
?>

<?php do_action( 'bbp_ts_template_after_topic_sections_loop' ); ?>