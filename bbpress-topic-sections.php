<?php
/*
Plugin Name: bbPress Topic Sections
Plugin URI: http://wordpress.org/extend/plugins/bbpress-topic-sections
Description: bbPress Topic Sections allows to split the topic content field into several sections.
Author: G.Breant
Version: 1.0.3
Author URI: http://sandbox.pencil2d.org
License: GPL2
Text Domain: bpp-ts
*/



class bbP_Topic_Sections {
	/** Version ***************************************************************/

	/**
	 * @public string plugin version
	 */
	public $version = '1.0.3';

	/**
	 * @public string plugin DB version
	 */
	public $db_version = '100';
	
	/** Paths *****************************************************************/

	public $file = '';
	
	/**
	 * @public string Basename of the plugin directory
	 */
	public $basename = '';

	/**
	 * @public string Absolute path to the plugin directory
	 */
	public $plugin_dir = '';
        
	/**
	 * @public string Prefix for the plugin
	 */
        public $prefix = '';
        
	/**
	 * @public string Absolute path to the theme directory
	 */
        
        public $templates_dir = '';
        
	/**
	 * @public string Taxonomy slug
	 */
        
        public $taxonomy_slug='topic_section';
        
        public $sections=array();
        public $current_section;
        public $section;
        public $in_the_loop;
        
        
	/**
	 * @var The one true bbPress Unread Posts Instance
	 */
	private static $instance;


	/**
	 * Main bbPress Pencil Unread Instance Instance
	 *
	 * @see bbpress_pencil_unread()
	 * @return The one true bbPress Pencil Unread Instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new bbP_Topic_Sections;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}
        
	/**
	 * A dummy constructor to prevent from being loaded more than once.
	 *
	 */
	private function __construct() { /* Do nothing here */ }
        
	function setup_globals() {
            global $wpdb;

            /** Paths *************************************************************/
            $this->file       = __FILE__;
            $this->basename   = plugin_basename( $this->file );
            $this->plugin_dir = plugin_dir_path( $this->file );
            $this->plugin_url = plugin_dir_url ( $this->file );
            $this->prefix = 'bbp-ts';

            $this->templates_dir = $this->plugin_dir.'theme/';
            $this->taxonomy_slug='topic_section';

            $this->section_block_class='bbp-ts-topic-section';

            $this->section_options_name = '_bbp_ts_'.bbp_ts()->taxonomy_slug;
            $this->term_options_default=array(
                'wysiwyg'=>true,
                'required'=>false,
                'max_chars'=>''
            );

            $this->current_section=-1;
            $this->in_the_loop = false;

            //register topic section metas table
            $meta_table_varname = $this->taxonomy_slug . 'meta'; //topic_sectionmeta
            $this->meta_table_name = $meta_table_varname; //wp_topic_sectionmeta

            $wpdb->$meta_table_varname = $wpdb->prefix . $this->meta_table_name; // $wpdb->topic_sectionmeta = 'wp_topic_sectionmeta';
 

	}
        
	function includes(){
            
            require( $this->plugin_dir . 'bbp_ts-template.php'   );
            require( $this->plugin_dir . '_inc/lib/simplehtmldom_1_5/simple_html_dom.php'   );
            
            if (is_admin()){
                require( $this->plugin_dir . 'bbp_ts-admin.php'   );
                bbP_Topic_Sections_Admin::instance();
            }
	}
	
	function setup_actions(){
            add_action( 'bbp_enqueue_scripts', array( $this, 'scripts_styles' ) );//scripts + styles
            
            add_action('bbp_init', array($this, 'load_plugin_textdomain'));
            add_action( 'bbp_register_taxonomies', array($this, 'register_topic_section_taxonomy'));
            
            
            
            //add form sections
            add_filter( 'bbp_get_form_topic_content',array($this, 'filter_form_topic_content'),10,2); //filter topic content (remove sections) at editing
            add_action( 'bbp_theme_after_topic_form_content', array($this, 'render_sections_form'));
            
            
            //add sections
            add_filter( 'bbp_get_reply_content',array($this, 'filter_topic_content'),3,2); //filter topic content (remove sections) at displaying
            add_action( 'bbp_theme_after_reply_content', array($this, 'render_sections'));
            
            //format sections
            add_filter( 'bbp_ts_new_section_pre_content',array(&$this,'wrap_section_with_tags'),10,3); //when saving new post
            add_filter( 'bbp_ts_edit_section_pre_content',array(&$this,'wrap_section_with_tags'),10,3); //when editing new post
            
            ////SAVE

            //append sections to the topic content
            add_filter( 'bbp_new_topic_pre_content',array(&$this,'new_topic_append_sections'),5);
            add_filter( 'bbp_edit_topic_pre_content',array(&$this,'edit_topic_append_sections'),5);

            
	}
        
        function filter_form_topic_content($content,$reply_id=false){
            
            // Get _POST data
            if ( 'post' == strtolower( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['bbp_topic_content'] ) )
                    $topic_content = $_POST['bbp_topic_content'];

            // Get edit data
            elseif ( bbp_is_topic_edit() )
                    $topic_content = bbp_get_global_post_field( 'post_content', 'raw' );

            // No data
            else
                    $topic_content = '';

            return bbp_ts()->dom_parser_get_section_content('main',$topic_content);

        }
        
        function filter_topic_content($content,$reply_id){
            
            $topic_id = bbp_get_topic_id( $reply_id );
            if(!$topic_id) return false;

            if(bbp_ts_forum_has_topic_sections( $topic_id )) {
                $content = get_post_field( 'post_content', $topic_id );
                $content = bbp_ts()->dom_parser_get_section_content('main',$content);
                $content = apply_filters( 'bbp_get_ts_main_section_content', $content, $topic_id);
            }
            
            return $content;
        }
        
	/**
	 * Set up the next section and iterate current section index.
         * 
	 */
	function next_section() {

		$this->current_section++;

		$this->section = $this->sections[$this->current_section];
		return $this->section;
	}

	/**
	 * Sets up the current section.
	 *
	 * @uses do_action_ref_array() Calls 'bpp_ts_loop_start' if loop has just started
	 */
	function the_section() {

		$this->in_the_loop = true;

		if ( $this->current_section == -1 ) // loop has just started
			do_action_ref_array('bpp_ts_loop_start', array(&$this));

		$this->section = $this->next_section();
	}

	/**
	 * Whether there are more sections available in the loop.
	 * Calls action 'bpp_ts_loop_end', when the loop is complete.
	 * @return bool True if posts are available, false if end of loop.
	 */
	function have_sections() {

		if ( $this->current_section + 1 < count($this->sections) ) {
			return true;
		} elseif ( $this->current_section + 1 == count($this->sections) && count($this->sections) > 0 ) {
			do_action_ref_array('bpp_ts_loop_end', array(&$this));
			// Do some cleaning up after the loop
			$this->rewind_sections();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since 1.5.0
	 * @access public
	 */
	function rewind_sections() {
		$this->current_section = -1;
		if ( count($this->sections) > 0 ) {
			$this->section = $this->sections[0];
		}
	}
        

        function wrap_section_with_tags($content,$section_id,$term_id){
            
            $term = get_term( $term_id, $this->taxonomy_slug );

            $section_header .='<h4 class="bpp_ts_section_title">'.$term->name.'</h4>';
            
            $classes[]=$this->section_block_class;
            $classes[]=$this->section_block_class.'-'.$term->term_id;

            $content = '<div'.self::classes_attr($classes).'>'.$section_header.$content.'</div>';

            return $content;
        }
        
        /**
         * Append sections to the content + hook to filter each section
         * @param type $content
         * @return type 
         */
        
        function new_topic_append_sections($content){
            
            $this->sections = bbp_ts_get_forum_topic_sections();
            
            while ( bbp_ts_sections() ) : bbp_ts_the_section();

                $single_section_content = $_POST[bbp_ts_get_section_field_name($this->current_section)];
                if(!$single_section_content) continue;

                $sections_content.=apply_filters('bbp_ts_new_section_pre_content',$single_section_content,$this->current_section,$this->section->term_id);
                
            endwhile;

            return $content.$sections_content;
        }
        
        /**
         * Append sections to the content + hook to filter each section
         * @param type $content
         * @return type 
         */
        
        function edit_topic_append_sections($content){
            $this->sections = bbp_ts_get_forum_topic_sections();
            
            while ( bbp_ts_sections() ) : bbp_ts_the_section();

                $single_section_content = $_POST[bbp_ts_get_section_field_name($this->current_section)];
                if(!$single_section_content) continue;

                $sections_content.=apply_filters('bbp_ts_edit_section_pre_content',$single_section_content,$this->current_section,$this->section->term_id);
                
            endwhile;


            return $content.$sections_content;
        }
        
        
        public function load_plugin_textdomain(){
            load_plugin_textdomain($this->prefix, FALSE, $this->plugin_dir.'/languages/');
        }
        
        function register_topic_section_taxonomy() {

            $labels = array( 
                'name' => _x( 'Topic Sections', 'bbp-ts' ),
                'singular_name' => _x( 'Topic Section', 'bbp-ts' ),
                'search_items' => _x( 'Search Topic Sections', 'bbp-ts' ),
                'popular_items' => _x( 'Popular Topic Sections', 'bbp-ts' ),
                'all_items' => _x( 'All Topic Sections', 'bbp-ts' ),
                'parent_item' => _x( 'Parent Topic Section', 'bbp-ts' ),
                'parent_item_colon' => _x( 'Parent Topic Section:', 'bbp-ts' ),
                'edit_item' => _x( 'Edit Topic Section', 'bbp-ts' ),
                'update_item' => _x( 'Update Topic Section', 'bbp-ts' ),
                'add_new_item' => _x( 'Add New Topic Section', 'bbp-ts' ),
                'new_item_name' => _x( 'New Topic Section', 'bbp-ts' ),
                'separate_items_with_commas' => _x( 'Separate Topic Sections with commas', 'bbp-ts' ),
                'add_or_remove_items' => _x( 'Add or remove Topic Sections', 'bbp-ts' ),
                'choose_from_most_used' => _x( 'Choose from most used Topic Sections', 'bbp-ts' ),
                'menu_name' => _x( 'Topic Sections', 'bbp-ts' ),
            );

            $args = array( 
                'labels' => $labels,
                'public' => true,
                'show_in_nav_menus' => true,
                'show_ui' => true,
                'show_tagcloud' => false,
                'hierarchical' => false,

                'rewrite' => true,
                'query_var' => true
            );

            register_taxonomy($this->taxonomy_slug, array(bbpress()->forum_post_type), $args );
        }
        
        function scripts_styles(){
            wp_register_style( $this->prefix.'-style', $this->plugin_url . 'style.css' );
            wp_enqueue_style( $this->prefix.'-style' );
        }
        


        function classes_attr($classes=false){
            if (!$classes) return false;
            return ' class="'.implode(" ",(array)$classes).'"';
            
        }
        
        function render_sections(){

            $this->sections = bbp_ts_get_forum_topic_sections();
            
            bbp_ts_locate_template('loop-topic_sections.php',true);
            
        }

        function render_sections_form(){

            $this->sections = bbp_ts_get_forum_topic_sections();
            
            bbp_ts_locate_template('form-topic_sections.php',true);
            
        }
        
        function dom_parser_section_has_class($section,$class){
            return (strpos($section->class, $class) !== false);
        }
        
        function dom_parser_get_section_content($section_id,$content){ 

                //create dom
                $html = new simple_html_dom();

                //load content
                $html->load($content,true,false); //the 'false' is for preserving line breaks
                
                $sections_dom = $html->find('.'.$this->section_block_class);

                foreach((array)$sections_dom as $section_dom) {
                    
                    //get section term ID
                    $section_term_id = bbp_ts()->sections[$section_id]->term_id;

                    if($section_id==='main'){
                       
                        //TO FIX
                        //get block class and check if section is valid for this forum.  If not, don't delete it.
                        //if(!in_array($section_term_id,$valid_sections_ids)) continue;

                        //delete section
                        $section_dom->outertext='';

                    }else{

                        $is_requested_section = self::dom_parser_section_has_class($section_dom,$this->section_block_class.'-'.$section_term_id);

                        if ($is_requested_section) {

                            //create dom
                            $section_html = new simple_html_dom();
                            //load section
                            $section_html->load($section_dom->innertext);
                            //get title
                            $section_title = $section_html->find('.bpp_ts_section_title',0);
                            //delete title
                            $section_title->outertext='';

                            break;
                        }

                    }
                }


                //end DOM process
                if($section_id==='main'){

                    //save html
                    $output = $html->save();

                    //clear (IMPORTANT !)
                    $html->clear();

                }elseif($section_html){

                    //save html
                    
                    $output = $section_html->save();

                    //clear (IMPORTANT !)
                    $section_html->clear();

                }
                return trim($output);

        }

        
}


/**
 * The main function responsible for returning the one true Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return The one true Instance
 */

function bbp_ts() {
	return bbP_Topic_Sections::instance();
}

bbp_ts();

?>