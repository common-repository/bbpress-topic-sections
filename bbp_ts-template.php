<?php
/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the plugin templates dir, then STYLESHEETPATH and TEMPLATEPATH.
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true. Has no effect if $load is false.
 * @return string The template filename if one is located.
 */
function bbp_ts_locate_template( $template_names, $load = false, $require_once = false ) {
    

	$located = '';
	foreach ( (array) $template_names as $template_name ) {
		if ( ! $template_name )
			continue;
                
                
                
                

		if ( file_exists( STYLESHEETPATH . '/' . $template_name ) ) {
			$located = STYLESHEETPATH . '/' . $template_name;
			break;
		} else if ( file_exists( TEMPLATEPATH . '/' . $template_name ) ) {
			$located = TEMPLATEPATH . '/' . $template_name;
			break;
		} else if ( file_exists( bbp_ts()->templates_dir . $template_name ) ) {
                        $located = bbp_ts()->templates_dir . $template_name;
			break;
		}
	}

	if ( $load && '' != $located ){
            load_template( $located, $require_once );
        }
		

	return $located;
}




function bbp_ts_get_forum_topic_sections( $forum_id = false,$args=false ){
        
    //TO FIX should be just this line, but bbpress bug ?
    if (!$forum_id) $forum_id = bbp_get_forum_id();


    $post_type=get_post_type($forum_id);

    if($post_type==bbpress()->forum_post_type){
        $forum_id = bbp_get_forum_id();
    }elseif($post_type==bbpress()->topic_post_type){
        $forum_id = bbp_get_topic_forum_id($forum_id);
    }


    $topic_sections = wp_get_post_terms( $forum_id, bbp_ts()->taxonomy_slug, $args );

    foreach ($topic_sections as $topic_section){
        $keyed_topic_sections[]=$topic_section;
    }

    return apply_filters('bbp_ts_get_forum_topic_sections',$keyed_topic_sections,$forum_id,$args);
}


function bbp_ts_forum_has_topic_sections( $forum_id = false ){
    return (bool)bbp_ts_get_forum_topic_sections($forum_id);
}

function bbp_ts_section_title(){
    echo bbp_ts_get_section_title();
}
    function bbp_ts_get_section_title(){
        $title = bbp_ts()->section->name;
        
        return apply_filters('bbp_ts_get_section_title',$title);
    }
    
function bbp_ts_form_section_title(){
    echo bbp_ts_get_form_section_title();
}
    function bbp_ts_get_form_section_title(){
            $title = bbp_ts_get_section_title();
            
            if(bbp_ts_is_section_required())
                $title.='*';
            
            $words_limit = bbp_ts_has_section_chars_limit();
            if($words_limit){
                $title.= ' '.sprintf(__('(Maximum Length: %d)','bpp_ts'),$words_limit);
            }
        
        return apply_filters('bbp_ts_get_form_section_title',$title);
    }
    
function bbp_ts_section_description(){
    echo bbp_ts_get_section_description();
}
    function bbp_ts_get_section_description(){
        $desc = bbp_ts()->section->description;
        return apply_filters('bbp_ts_get_section_description',$desc);
    }



function bbp_ts_the_topic_content_section( $topic_id = 0 ) {
	echo bbp_ts_get_topic_content_section( $topic_id );
}


    function bbp_ts_get_topic_content_section( $topic_id = 0 ) {
            $topic_id = bbp_get_topic_id( $topic_id );
            if(!$topic_id) return false;

            // Check if password is required
            if ( post_password_required( $topic_id ) )
                    return get_the_password_form();
            
            
            if(bbp_ts_forum_has_topic_sections( $topic_id )) {
                $content = get_post_field( 'post_content', $topic_id );
                $single_section_content = bbp_ts()->dom_parser_get_section_content(bbp_ts()->current_section,$content);
            }
            return apply_filters('bbp_get_ts_single_section_content',$single_section_content,$topic_id);
            
    }
    
    function bbp_ts_section_has_content(){
        return (bool)bbp_ts_get_topic_content_section();
    }


function bbp_ts_form_section_content(){
       
    echo bbp_ts_get_form_section_content();
}

function bbp_ts_get_form_section_content(){

    // Get _POST data
    if ( 'post' == strtolower( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST[bbp_ts_get_section_field_name()] ) ){

        $section_content = $_POST[bbp_ts_get_section_field_name()];
    
    }elseif ( bbp_is_topic_edit() ){// Get edit data
        
        $topic_content = bbp_get_global_post_field( 'post_content', 'raw' );
        $section_content = bbp_ts()->dom_parser_get_section_content(bbp_ts()->current_section,$topic_content);
        
    }else{
        
        $section_content = '';
    }
    
    return apply_filters( 'bbp_ts_get_form_section_content', esc_textarea( $section_content ));
}


function bbp_ts_section_classes(){
    return bbp_ts_get_section_classes();
}

    function bbp_ts_get_section_classes(){

        $classes[]=bbp_ts()->section_block_class;
        $classes[]=bbp_ts()->section_block_class.'-'.bbp_ts()->current_section;
        $classes[]=bbp_ts()->section_block_class.'-term-'.bbp_ts()->section->term_id;
        
        if(bbp_ts_is_section_required())
            $classes[]='required';
        
        if(bbp_ts_has_section_chars_limit())
            $classes[]='words-limitation';

        $classes = apply_filters('bbp_ts_get_section_classes',$classes);

        echo bbp_ts()->classes_attr($classes);
    }

function bbp_ts_get_section_option($term_id,$option_name){

    $option = get_metadata(bbp_ts()->taxonomy_slug,$term_id,'_'.bbp_ts()->prefix.'_'.$option_name, true);
    return apply_filters('bbp_ts_get_section_option',$option,$term_id);
}

function bbp_ts_get_section_options($term_id){
    foreach(bbp_ts()->term_options_default as $option_name=>$default_value){
        $options[$option_name] = bbp_ts_get_section_option($term_id,$option_name);
    }
    return $options;
}

function bbp_ts_is_section_required(){        
        $val = bbp_ts_get_section_option(bbp_ts()->section->term_id,'required');
        return apply_filters('bbp_ts_is_section_required',$val);
}

function bbp_ts_has_section_chars_limit(){
        $val = bbp_ts_get_section_option(bbp_ts()->section->term_id,'max_chars');
        return apply_filters('bbp_ts_has_section_chars_limit',$val);
}

function bbp_ts_is_section_wysiwyg(){
        $val = bbp_ts_get_section_option(bbp_ts()->section->term_id,'wysiwyg');
        return apply_filters('bbp_ts_is_section_chars_wysiwyg',$val);
}

function bbp_ts_the_section_field_name(){
    echo bbp_ts_get_section_field_name();
}

function bbp_ts_get_section_field_name(){
    return 'bbp_ts_topic_section_'.bbp_ts()->current_section.'_content';
}

/**
 * Output a textarea or TinyMCE if enabled
 *
 * inspired by  bbPress bbp_the_content()
 *
 * @param array $args
 * @uses bbp_get_the_content() To return the content to output
 */
function bbp_ts_the_section_content( $args = array() ) {
	echo bbp_ts_get_section_content( $args );
}
	/**
	 * Return a textarea or TinyMCE if enabled
	 *
	 * inspired by  bbPress bbp_the_content()
	 *
	 * @param array $args
	 *
	 * @uses apply_filter() To filter args and output
	 * @uses wp_parse_pargs() To compare args
	 * @uses bbp_use_wp_editor() To see if WP editor is in use
	 * @uses bbp_is_edit() To see if we are editing something
	 * @uses wp_editor() To output the WordPress editor
	 *
	 * @return string HTML from output buffer 
	 */
	function bbp_ts_get_section_content( $args = array() ) {

		// Default arguments
		$defaults = array(
                        'section_id'    => false,
			'before'        => '<div class="bbp--ts-the-section-wrapper">',
			'after'         => '</div>',
			'wpautop'       => true,
			'media_buttons' => false,
			'textarea_rows' => '12',
			'tabindex'      => bbp_get_tab_index(),
			'editor_class'  => 'bbp-ts-the-section-content',
			'tinymce'       => true,
			'teeny'         => true,
			'quicktags'     => true
		);
                
                
                
                
		$r = bbp_parse_args( $args, $defaults, 'get_the_section_content' );
		extract( $r );

		// Assume we are not editing
		$post_content = '';

		// Start an output buffor
		ob_start();

		// Output something before the editor
		if ( !empty( $before ) )
			echo $before;

		// Get sanitized content
		if ( bbp_is_edit() )
			$post_content = bbp_ts_get_form_section_content($section_id);
                
                
                //item id
                $input_id = bbp_ts_get_section_field_name($section_id);

		// Use TinyMCE if available
		if ( bbp_use_wp_editor() ) :
			wp_editor( htmlspecialchars_decode( $post_content, ENT_QUOTES ), $input_id, array(
				'wpautop'       => $wpautop,
				'media_buttons' => $media_buttons,
				'textarea_rows' => $textarea_rows,
				'tabindex'      => $tabindex,
				'editor_class'  => $editor_class,
				'tinymce'       => $tinymce,
				'teeny'         => $teeny,
				'quicktags'     => $quicktags
			) );

		/**
		 * Fallback to normal textarea.
		 *
		 * Note that we do not use esc_textarea() here to prevent double
		 * escaping the editable output, mucking up existing content.
		 */
		else : ?>

			<textarea id="<?php echo $input_id;?>" class="<?php echo esc_attr( $editor_class ); ?>" name="<?php echo $input_id;?>" cols="60" rows="<?php echo esc_attr( $textarea_rows ); ?>" tabindex="<?php echo esc_attr( $tabindex ); ?>"><?php echo $post_content; ?></textarea>

		<?php endif;

		// Output something after the editor
		if ( !empty( $after ) )
			echo $after;

		// Put the output into a usable variable
		$output = ob_get_contents();

		// Flush the output buffer
		ob_end_clean();

		return apply_filters( 'bbp_ts_get_the_section_content', $output, $args, $post_content );
	}
			

/**
 * Whether there are more sections available in the loop
 * @return type 
 */

function bbp_ts_sections($forum_id=false) {

	// Put into variable to check against next
	$have_sections = bbp_ts()->have_sections($forum_id);

	return $have_sections;
}

/**
 *Loads up the current section in the loop
 * @return type 
 */

function bbp_ts_the_section() {
	return bbp_ts()->the_section();
}

?>
