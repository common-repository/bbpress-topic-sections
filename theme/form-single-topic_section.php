<div<?php bbp_ts_section_classes();?>>
    <?php do_action( 'bbp_ts_theme_before_single_section_form' ); ?>

    <p>
    <div class="bpp_ts_section_header">
        <label class="bpp_ts_section_title" for="<?php bbp_ts_the_section_field_name();?>">
            <span><?php bbp_ts_form_section_title();?>:</span>
        </label>
        <?php if(bbp_ts_get_section_description()){?>
        <div class="bpp_ts_section_description"><?php bbp_ts_section_description(); ?></div>
        <?php }?>
        
    </div>


        <?php if (( function_exists( 'wp_editor' ) ) && (bbp_ts_is_section_wysiwyg())) : ?>
            <?php bbp_ts_the_section_content( array( 'context' => 'topic_section', 'section_id' => bbp_ts()->current_section ) ); ?>
        <?php else : ?>
            <textarea id="<?php bbp_ts_the_section_field_name();?>" tabindex="<?php bbp_tab_index(); ?>" name="<?php bbp_ts_the_section_field_name();?>" cols="60" rows="4"><?php bbp_ts_form_section_content(); ?></textarea>
            

        <?php endif; ?>

    </p>

    <?php do_action( 'bbp_ts_theme_after_single_section_form' ); ?>
</div>