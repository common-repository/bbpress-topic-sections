

<?php do_action( 'bbp_ts_template_before_single_topic_section' ); ?>


<div<?php bbp_ts_section_classes();?>>
    <h4 class="bpp_ts_section_title"><?php bbp_ts_section_title();?></h4>
    <p>
    <?php
            bbp_ts_the_topic_content_section();
    ?>
    </p>
</div>

<?php do_action( 'bbp_ts_template_after_single_topic_section' ); ?>