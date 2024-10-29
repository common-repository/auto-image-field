<?php
/*
Plugin Name: Auto Image Field
Description: Make custom image field for Wordpress post and page editings. <strong>REMEMBER!: </strong>Unistalling this plugin <strong>WILL DELETE</strong> al the custom fields for future post. <strong>WILL NOT DELETE</strong> the fields defined in the alrready published ones.
Version: 2.0
Author: Arturo Emilio
Author URI: http://www.arturoemilio.es

*/
/*  Copyright 2009  Arturo Emilio  (email : anduriell@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
register_activation_hook(__FILE__, 'up');
register_deactivation_hook(__FILE__, 'down');
add_action('admin_menu', 'menuadm');
add_action('save_post', 'sv');
add_action('publish_post', 'sv');
add_action('private_to_published', 'sv');
add_action('submitpost_box', 'field_hook');
add_action('media_upload_andimage', 'media_upload_andimage');
add_action('media_upload_andlibrary','media_upload_andlibrary');

function my_enqueue($hook) {
	global $wpdb;
	$field_table_name = $wpdb->prefix . 'and_imagefields';
	$fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $field_table_name WHERE 1",$field_table_name), ARRAY_A);
	$fields = &$fields;
	foreach($fields as $field) {
				if(!$field['field_id'])continue;
				elseif ($old) $ids .= '='.$old.'&';
				$ids .= $field['field_id']; 
				$old =  $field['field_id'];
	}
	if ($old){ 
				$ids .= '='.$old;
				wp_enqueue_script( 'my_custom',plugin_dir_url( __FILE__ ) . 'custom-header.js.php'.$ids );
	}
}
//add_action( 'admin_enqueue_scripts', 'my_enqueue' );

function media_upload_andmedia($type) {
			add_filter('media_upload_tabs', 'and_media_upload_tabs');
			call_user_func('media_upload_' . $type);
			$ret = ob_get_clean();
			ob_end_flush();
			if (isset($_POST['andsend'])) {
				return andmedia_insert_handler();}
			$field_id = urlencode(stripslashes($_GET['andfield']));
			$ret = str_replace('tab=gallery', 'tab=andgallery', $ret);
			$ret = str_replace('tab=library', 'tab=andlibrary', $ret);
			$ret = str_replace('tab=type', 'tab=andimage', $ret);
			$ret = str_replace('&#038;', '&amp;', $ret);
			$ret = preg_replace("/(andfield=$field_id(\&(amp;)?)?)/", '', $ret);
			$ret = str_replace('upload.php?', "upload.php?andfield=$field_id&", $ret);
			$ret = str_replace('name=\'send[', 'name=\'andsend[', $ret);
			echo $ret;}

function andmedia_insert_handler() {
			$field_name = 'andfield_'.intval($_GET['andfield']);
			$keys = array_keys($_POST['andsend']);
			$send_id = (int) array_shift($keys);
			list($html) = image_downsize($send_id, 'full');
			jstododos($field_name, $html);}

function and_media_upload_tabs() {return array('type' => __('Choose File'), 'andlibrary' => __('Media Library'),);}
function media_upload_andimage() {media_upload_andmedia('image');}
function media_upload_andlibrary() {media_upload_andmedia('library');}



function field_hook() {
			global $wpdb;
			$field_table_name = $wpdb->prefix . 'and_imagefields';
			$fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $field_table_name WHERE 1",$field_table_name), ARRAY_A);
	   		$fields = &$fields;
	   		if (!$fields){return;}
				add_meta_box('and-imagefielddiv-'.$field['field_id'], 'Choose a Picture', 'metabox', 'post', 'normal', 'high');

		}
function custom($fields) {

?>
	<script type="text/javascript">
						(function($) {
									var frame;
								//arturo
									$( function() {
										// Fetch available headers and apply jQuery.masonry
										// once the images have loaded.
										var $headers = $('.available-headers');
								<?php
								foreach($fields as $field){
								?>
										// Build the choose from library frame.
										$('#choose-from-library-link-<?php echo $field['field_id'];?>').click( function( event ) {
											var $el = $(this);
											event.preventDefault();
								
											// If the media frame already exists, reopen it.
											if ( frame ) {
												frame.open();
												return;
											}
								
											// Create the media frame.
											frame = wp.media.frames.customHeader = wp.media({
												// Set the title of the modal.
												title: $el.data('choose'),
								
												// Tell the modal to show only images.
												library: {
													type: 'image'
												},
								
												// Customize the submit button.
												button: {
													// Set the text of the button.
													text: $el.data('update'),
													// Tell the button not to close the modal, since we're
													// going to refresh the page when the image is selected.
													close: false
												}
											});
								
											// When an image is selected, run a callback.
											frame.on( 'select', function() {
												// Grab the selected attachment.
												var attachment = frame.state().get('selection').first(),
													link = $el.data('updateLink');
								
												// Tell the browser to navigate to the crop step.
												window.location = link + '&file=' + attachment.id;
											});
								
											frame.open();
										});
								<?php } ?>
									});
								}(jQuery));
	</script>
<?php

}
function metabox($object, $box) { 
			global $post_ID, $temp_ID, $wpdb;
				
			$val = '';
			$field_table_name = $wpdb->prefix . 'and_imagefields';
			$fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $field_table_name WHERE 1",$field_table_name), ARRAY_A);
	   		$fields = &$fields;
	   		if(is_array($fields))
	   				custom($fields);			
			
			$field_id = str_replace('and-imagefielddiv-', '', $box['id']);	
			
			// Include in admin_enqueue_scripts action hook
			wp_enqueue_media();
			
			foreach($fields as $field) {
					if (!$field['field_id'])continue;
					$value = $_REQUEST['field'];
					if ($value == 'andfield_'.$field['field_id']){
					 	$value = wp_get_attachment_url(absint($_REQUEST['file']));
					 	update_post_meta ($post_ID, $field['field_name'], $value) ;
					}
					
					$val = get_post_meta($post_ID, $field['field_name'], true);
					$parts = parse_url($_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
						parse_str($parts['query'], $query);
						unset ($query['field']);
						unset ($query['file']);
					   $modal_update_href = 'post.php?'. http_build_query($query).'&field=andfield_'.$field['field_id'];
					?>	
					<p><input name="andfield_<?php echo $field['field_id']; ?>" type="text" id="andfield_<?php echo $field['field_id']; ?>" value="<?php echo $val; ?>"
					style="width: 90%;"  />
					<a id="choose-from-library-link-<?php echo $field['field_id'];?>" href="#"
								data-update-link="<?php echo esc_attr( $modal_update_href ); ?>"
								data-choose="<?php esc_attr_e( 'Choose the Field Image' ); ?>"
								data-update="<?php esc_attr_e( 'Set as Field Image' ); ?>">
					<img src='images/media-button-image.gif' alt='<?php echo $image_title; ?>' />
					</a>
					</p>
					<p><?php echo htmlspecialchars($field['field_description']); ?></p>
					<?php if(!$val) ?>
						<p><b><u>Actual Image</u></b></p>
						<p><img src='<?php echo $val; ?>' alt='<?php echo $image_title; ?>' style="width: 100%;"  />
						<hr>
					<?php ; ?>
			
			<?php }		
				
		}
			
			
			add_action('admin_head','jstodo');

function sv() {
			global $wpdb,$post_ID;  
			/*
            if( wp_is_post_revision( $post_ID ) || wp_is_post_autosave( $post_ID ) ) { return; }
			if(get_option('and_is_future_' . $post_ID) === 'TRUE' 
				&& (get_post_field('post_status', $post_ID) == 'publish')) { 
					delete_option('and_is_future_' . $post_ID, FALSE);
					return;}
			*/
			
			$field_table_name = $wpdb->prefix . 'and_imagefields';
			$fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $field_table_name WHERE 1",$field_table_name), ARRAY_A);
	   		$fields = &$fields;
	   		if (!$fields){return;}
	   					
			foreach($fields as $field) {
				$field_id = $field['field_id']; 
				//$value = $_POST['andfield_'.$field['field_id']];
				$value = $_REQUEST['field'];
				if ($value != 'andfield_'.$field['field_id']) unset($value);
				else $value = wp_get_attachment_url( absint($_REQUEST['file']));

	   			
	   			if ((!$value) or ($value  == '') AND (!is_page())){ 
			        	$query =
					      " SELECT ID, post_title, post_excerpt, post_content, post_content_filtered "
						."FROM $wpdb->posts "
						."WHERE ID = '$post_ID'";
					$cont = $wpdb->get_results($query);
					preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $cont[0]->post_content, $matches );
	                                if (isset( $matches[1][0] ) ){ $value = $matches[1][0];}
   	                        	else{ $value = $field['field_default']; } 
   	            }
   	                        	
				$value = $wpdb->escape(  maybe_serialize($value) );
 				$sql = "REPLACE INTO $vt (post_id, field_id, value) VALUES (%d, %d, '%s')";
				$result = $wpdb->query($wpdb->prepare($sql, $post_id, $field_id, $value));
				  add_post_meta($post_id, $field['field_name'], $value, true) or update_post_meta ($post_ID, $field['field_name'], $value) ; } }
				function up(){
				global $wpdb;
				$field_table_name = $wpdb->prefix . 'and_imagefields';
				if($wpdb->get_var("SHOW TABLES LIKE '$field_table_name'") != $field_table_name) {
	$sql = "CREATE TABLE $field_table_name (
					field_id INT NOT NULL AUTO_INCREMENT  PRIMARY KEY ,
					field_name VARCHAR( 32 ) NOT NULL ,
					field_description VARCHAR( 255 ) NOT NULL ,
					field_default VARCHAR( 255 ) NOT NULL,
					KEY ( field_name ));";
	$wpdb->query($sql);}}
	
function down(){
global $wpdb;
$field_table_name = $wpdb->prefix . 'and_imagefields';
if($wpdb->get_var("SHOW TABLES LIKE '$field_table_name'") == $field_table_name) {
	$sql = "DROP TABLE $field_table_name";
	$wpdb->query($sql);}}
	
function menuadm() {add_options_page('AutoImageField', 'AutoImageField', 10, __FILE__, 'option');}

function jstododos($field_name, $html) {	?>
	<script type="text/javascript">
	/* <![CDATA[ */
	var win = window.dialogArguments || opener || parent || top;
	win.send_to_field('<?php echo $field_name; ?>', '<?php echo addslashes($html); ?>');
	/* ]]> */
	</script> <?php exit;}


function option(){		
		global $wpdb;
		$field_table_name = $wpdb->prefix . 'and_imagefields';
		if(isset($_POST['add_field_button'])) {
			$name = remove_accents(trim(stripslashes($_POST['new_field_name'])));
			if(strlen(preg_replace('/[a-zA-Z0-9_\-]/', '', $name))) {
			$messages[] = 'Error: Name may include only alphanumeric characters, dashes, and underscores.';
			$edit_error = TRUE;}
			$name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
			if(!strlen(preg_replace('/[^a-zA-Z0-9]/', '', $name))) {
			$messages[] = 'Error: Name must include at least one alphanumeric character.';
			$edit_error = TRUE;}
			$description = trim(stripslashes($_POST['new_field_desc']));
		        $default = $_POST['new_field_def'];
			$dupes = $wpdb->get_results($wpdb->prepare("SELECT * FROM  $field_table_name WHERE field_name='%s' AND field_id != %d", $name, intval($id)));
			if(count($dupes)) {
			$messages[] = 'Cannot create duplicate field name';
			$edit_error = TRUE;}
		        if(!$edit_error){
		        $sql = $wpdb->prepare("INSERT INTO $field_table_name (field_name, field_description, field_default) VALUES ('%s', '%s', '%s')", $name, $description, $default);
			if($wpdb->query($sql)) {
				$messages[] = 'New field added successfully.';}}}
else if(isset($_POST['edit_field_button'])) {
		        $id = intval($_POST['andfield']);
			$name = remove_accents(trim(stripslashes($_POST['new_field_name'])));
			if(strlen(preg_replace('/[a-zA-Z0-9_\-]/', '', $name))) {
			$messages[] = 'Error: Name may include only alphanumeric characters, dashes, and underscores.';
			$edit_error = TRUE;}
			$name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
			if(!strlen(preg_replace('/[^a-zA-Z0-9]/', '', $name))) {
			$messages[] = 'Error: Name must include at least one alphanumeric character.';
			$edit_error = TRUE;}
			$description = trim(stripslashes($_POST['new_field_desc']));
		        $default = $_POST['new_field_def'];
			if(!$edit_error){
			if($wpdb->query($wpdb->prepare("REPLACE INTO $field_table_name (field_id, field_name, field_description, field_default) VALUES (%d, '%s', '%s', '%s')", $id, $name, $description, $default))) {
				$messages[] =  'Field updated successfully.';}}
			if($edit_error) { 
				$editing = TRUE; 
				$field_id = intval($_POST['andfield']);}}
		else if(isset($_POST['delete_selected'])) {
			$to_delete = $_POST['delete_andfield'];
			$ids = array();
			foreach($to_delete as $id) { $ids[] = intval($id);}
			$has_fields = $wpdb->get_var("SELECT count(*) FROM $field_table_name WHERE field_id IN (" . join(', ', $ids) . ")");
			if($has_fields) {
				$result = $wpdb->query("DELETE FROM $field_table_name WHERE field_id IN (" . join(', ', $ids) . ")");
			if(!$result) {
				$messages[] = 'Unknown error deleting fields';
				$other_error = TRUE;				
				return;}
					$messages[] = 'Fields deleted successfully.';} }
		else if($_GET['mode'] == 'edit') {
			$field_id = intval($_GET['andfield']);
			$editing = TRUE;} ?>
		<div class="wrap">
			<?php if(count($messages)) { ?>
				<br class="clear" />
				<div class="<?php if($edit_error || $other_error) { echo ' error'; } else { echo 'updated fade'; } ?>" id="message">
					<?php foreach($messages as $message) { ?><p><?php echo $message; ?></p><?php } ?>
				</div>
			<?php }
			$fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $field_table_name WHERE 1",$field_table_name), ARRAY_A);
	   		$fields = &$fields;
			if(count($fields)) {
			?>
			<h1>AutoImage Field by Arturo Emilio</h1>	
			<h2><a href="http://arturoemilo.es/">Manage Custom Image Fields</a></h2>
			<form action="" method="post">
				<div class="tablenav">
					<div class="alignleft">
						<input class="button-secondary delete" type="submit" name="delete_selected" value="Delete" />
					</div>
					<div class="alignright">
						<input class="button-secondary edit" type="submit" name="edit_selected" value="Edit" />
					</div>
					<br class="clear" />
				</div>
				<br class="clear" />
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col" class="check-column"><input type="checkbox" /></th>
							<th scope="col">ID</th>
							<th scope="col">Field Name</th>
							<th scope="col">Description</th>
							<th scope="col">Default Value</th>
							<th scope="col">Edit</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($fields as $field) { ?>
						<tr>
							<td class="check-column"><input type="checkbox" name="delete_andfield[]" value="<?php echo $field['field_id']; ?>" /></td>
							<td><?php echo $field['field_id']; ?></td>
							<td><?php echo $field['field_name']; ?></td>
							<td><?php echo $field['field_description']; ?></td>
							<td><?php echo $field['field_default']; ?></td>
							<td class="check-column"><input type="radio" name="edit_andfield" value="<?php echo $field['field_id']; ?>" /></td>
							
						</tr>
					<?php	}  ?>
					</tbody>
				</table>
				<p><strong>Note:</strong><br />Deleting a field will also delete values for that field for all posts and pages.</p>
				<strong><p> Unistalling this plugin WILL DELETE the custom fields in future post. Not the Custom fields already included in post published</p></strong>
			</form><?php	
		} 
		
		
		
		
		if(isset($_POST['edit_selected'])){	
			$id = $_POST['edit_andfield'];
			$field = $wpdb->get_results($wpdb->prepare("SELECT * FROM $field_table_name WHERE `field_id` = $id "), ARRAY_A);
			$mode = 'edit';
			$title = 'Edit Custom Image Field';
			$button = 'Save Changes';
			}else{ 
			$mode = 'add'; 
			$title = 'Add a New Custom Image Field';
			$button = 'Add Field';}
			
		?>
			<h2><a name="add"></a><a href="http://arturoemilio.es"><?php echo $title; ?></a></h2>
			<form action="" method="post">
				<input type="hidden" name="mode" value="<?php echo $mode; ?>" />
				<?php if($id) { ?><input type="hidden" name="andfield" value="<?php echo $id; ?>" /><?php } ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="new_field_name">Field Name</label></th>
						<td>
							<input type="text" id="new_field_name" name="new_field_name" size="20" value="<?php if($id) { echo $field[0]['field_name']; }?>" /><br />
							Use only alphanumeric characters, dashes, and underscores.  Name must be unique.
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="new_field_desc">Field Description (optional)</label></th>
						<td>
							<textarea type="text" id="new_field_desc" name="new_field_desc" rows="3" cols="50"><?php if($id) { echo $field[0]['field_description']; }?></textarea>					
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="new_field_def">Field Default Value(optional)</label></th>
						<td>
							<textarea type="text" id="new_field_def" name="new_field_def" rows="3" cols="50"><?php if($id) { echo $field[0]['field_default']; }?></textarea><br />
							<p>Use to set default value in case none is set. This value will be used in case that the field is left empty and no image is found in the post text.</p>
						</td>
					</tr>
					
				</table>
				<p class="submit">
					<input name="<?php echo $mode; ?>_field_button" value="<?php echo $button; ?>" type="submit">
				</p>
				<p> Have you found any bug? Do you have new ideas? Just let me know so i could fix it for you!! Leave a comment in the blog please <a href="http://arturoemilio.es"> Click Here to go</a></p>
			</form><?php
			
			
}
function jstodo() { ?>
			<script type="text/javascript">
			/* <![CDATA[ */
			function send_to_field(fieldname, the_html) {
				var the_field = document.getElementById(fieldname);
				the_field.value = the_html;
				tb_remove();
			}
			/* ]]> */
			</script><?php }