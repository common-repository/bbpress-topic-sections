<?php
class bbP_Topic_Sections_Admin {

        /**
	 * @var Instance
	 */
	private static $instance;
        
        public $term_options_default=array();
        
        private static $db_version_option_name;


	/**
	 * Main Instance
	 *
	 * Insures that only one instance of the plugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
         * 
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new bbP_Topic_Sections_Admin;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}
        
	/**
	 * A dummy constructor to prevent bbPress from being loaded more than once.
	 *
	 * @since bbPress (r2464)
	 * @see bbPress::instance()
	 * @see bbpress();
	 */
	private function __construct() { /* Do nothing here */ }
        
	function setup_globals() {
            $this->db_version_option_name='_'.bbp_ts()->prefix.'_db_version';

	}
        
	function includes(){

	}
	
	function setup_actions(){
            
            add_action('plugins_loaded', array($this, 'update_db_check'));
            
            add_action( bbp_ts()->taxonomy_slug . '_edit_form', array($this, 'topic_section_term_edit_form'));
            add_action("created_term", array($this, 'topic_section_term_created'),10,3);
            add_action("edited_term", array($this, 'topic_section_term_edited'),10,3);
            
	}
        
        function update_db_check(){
            if ( get_site_option( $this->db_version_option_name ) != bbp_ts()->db_version) {
                    $this->install();
            }
        }
        
        function install(){
            //install routine
            
            $this->create_topic_section_metatable(bbp_ts()->meta_table_name,bbp_ts()->taxonomy_slug);
            
            update_option( $this->db_version_option_name , bbp_ts()->db_version );
        }
        
        function create_topic_section_metatable($table_name,$type){
            global $wpdb;

            $table_name = $wpdb->prefix . $table_name;
            
            if (!empty ($wpdb->charset))
                $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
            if (!empty ($wpdb->collate))
                $charset_collate .= " COLLATE {$wpdb->collate}";

            $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
                meta_id bigint(20) NOT NULL AUTO_INCREMENT,
                {$type}_id bigint(20) NOT NULL default 0,

                meta_key varchar(255) DEFAULT NULL,
                meta_value longtext DEFAULT NULL,

                UNIQUE KEY meta_id (meta_id)
            ) {$charset_collate};";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
  
        function topic_section_term_edit_form($tag, $taxonomy){
            
            $options = bbp_ts_get_section_options($tag->term_id);

            
            ?>
            <table class="form-table form-table-bbp-ts">
                <tr class="form-field">
                        <th scope="row" valign="top"><label for="bbp_ts_term_option[required]"><?php _e('Required', 'bbp-ts'); ?></label></th>
                        <td><input  type="checkbox" name="bbp_ts_term_option[required]" id="bbp_ts_term_option_required"<?php checked($options['required']);?>>
                        <p class="description"><?php _e('Is this section required ? (User cannot leave it empty)','bbp-ts'); ?></p></td>
                </tr>
                <tr class="form-field">
                        <th scope="row" valign="top"><label for="bbp_ts_term_option[wysiwyg]"><?php _ex('WYSIWYG Editor', 'bbp-ts'); ?></label></th>
                        <td><input  type="checkbox" name="bbp_ts_term_option[wysiwyg]" id="bbp_ts_term_option_wywiwyg"<?php checked($options['wysiwyg']);?>></td>
                </tr>
                <tr class="form-field">
                        <th scope="row" valign="top"><label for="bbp_ts_term_option[max_chars]"><?php _ex('Maximum characters allowed', 'bbp-ts'); ?></label></th>
                        <td><input style="width:50px" type="text" size="10" name="bbp_ts_term_option[max_chars]" id="bbp_ts_term_option_max_chars" value="<?php echo $options['max_chars'];?>" ><br />
                        <p class="description"><?php _e('Empty = no limitation','bbp-ts'); ?></p></td>
                </tr>
            </table>
            <?php
        }
        
        function topic_section_term_created($term_id, $tt_id, $taxonomy){
            $this->save_custom_term_options($term_id, $tt_id, $taxonomy);
        }
        function topic_section_term_edited($term_id, $tt_id, $taxonomy){
            $this->save_custom_term_options($term_id, $tt_id, $taxonomy);
        }
        
        function save_custom_term_options($term_id, $tt_id, $taxonomy){
            if($taxonomy!=bbp_ts()->taxonomy_slug) return false;
            
            $defaults = bbp_ts()->term_options_default;

            $sent = $_POST['bbp_ts_term_option'];
            
            //required
            $this->update_section_option($term_id,'required',(bool)$sent['required']);
                
            
            //wysiwyg
            $this->update_section_option($term_id,'wysiwyg',(bool)$sent['wysiwyg']);


            //max chars
            if((empty($sent['max_chars'])) || (!is_numeric($sent['max_chars']))) $sent['max_chars'] = false;
            $this->update_section_option($term_id,'max_chars',$sent['max_chars']);

        }
        
        function update_section_option($term_id,$option_name,$value){
            return update_metadata(bbp_ts()->taxonomy_slug,$term_id,'_'.bbp_ts()->prefix.'_'.$option_name, $value);
        }

}