<?php

/**
 * Get the value of an input field.
 *
 * @param string $field_name
 * @param integer $group_index
 * @param integer $field_index
 * @param integer $post_id
 * @return a string or array based on field type
 */
function get ($field_name, $group_index=1, $field_index=1 ,$post_id=NULL) {
  global $post;

  if(!$post_id){ $post_id = $post->ID; }
  
  $field = get_data($field_name,$group_index,$field_index,$post_id);
  if(!$field) return FALSE;

  $type    = $field['type'];
  $options = $field['options'];
  $value   = $field['meta_value'];

  $result = _processed_value($value, $type, $options);
  return $result;

}

/**
 * Return a number of duplicate field 
 * 
 */
function get_count_field( $field_name , $group_index = 1 , $post_id = NULL){
  global $post;

  if(!$post_id){ $post_id = $post->ID; }
  $total = get_order_field($field_name,$group_index,$post_id);
  return count($total);

}

function get_count_group( $field_name , $post_id = NULL){
  global $post;

  if(!$post_id){ $post_id = $post->ID; }

  $total = get_order_group($field_name,$post_id);
  return count($total);

}

function get_audio( $field_name, $group_index=1, $field_index=1 ,$post_id=NULL ){
  global $post;

  if(!$post_id){ $post_id = $post->ID; }
  $audio = get($field_name,$group_index,$field_index,$post_id);
  
  if( empty($audio) ) return FALSE;
  
  $player = stripslashes(trim("\<div style=\'padding-top:3px;\'\>\<object classid=\'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\' codebase='\http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0\' width=\'95%\' height=\'20\' wmode=\'transparent\' \>\<param name=\'movie\' value=\'".MF_URL."js/singlemp3player.swf?file=".urlencode($audio)."\' wmode=\'transparent\' /\>\<param name=\'quality\' value=\'high\' wmode=\'transparent\' /\>\<embed src=\'".MF_URL."js/singlemp3player.swf?file=".urlencode($audio)."' width=\'50\%\' height=\'20\' quality=\'high\' pluginspage=\'http://www.macromedia.com/go/getflashplayer\' type=\'application/x-shockwave-flash\' wmode=\'transparent\' \>\</embed\>\</object\>\</div\>"));
  
  return $player;
}

function pt(){
  return PHPTHUMB;
}

/**
 * Return a array with the order of a group
 *
 * @param string $groupName 
 */
function get_order_group($field_name,$post_id=NULL){
  global $post,$wpdb;
  
  if(!$post_id){ $post_id = $post->ID; }
  $sql = sprintf(
    "SELECT DISTINCT(group_count) ".
    "FROM %s " .
    "WHERE field_name = '%s' AND post_id = %d " . 
    "ORDER BY group_count ASC",
    MF_TABLE_POST_META,
    $field_name,
    $post_id
  );
  $elements = $wpdb->get_results($sql,ARRAY_A);

  $order = array();
  foreach($elements as $element){
    $order[] =  $element['group_count'];
  }
  return $order;
}

function get_order_field( $field_name, $group_index = 1 , $post_id = NULL ){
  global $post,$wpdb; 
	
  if(!$post_id){ $post_id = $post->ID; }
  $sql = sprintf(
    "SELECT DISTINCT( field_count ) " .
    "FROM %s " .
    "WHERE field_name = '%s' AND group_count = %d AND post_id = %d " .
    "ORDER BY field_count ASC",
    MF_TABLE_POST_META,
    $field_name,
    $group_index,
    $post_id
  );
  $elements = $wpdb->get_results($sql,ARRAY_A);

  $order = array();
  foreach($elements as $element){
    $order[] =  $element['field_count'];
  }
  return $order;

}

function get_post_type_name($post_id = NULL){
  global $post,$wpdb; 
	
  if(!$post_id){ $post_id = $post->ID; }

  $post_type = get_post_type($post_id);

  return $post_type;
}

function get_image ($field_name, $group_index=1, $field_index=1,$tag_img=1,$post_id=NULL,$params=NULL, $attr = NULL , $wp_size='original') {

  return create_image(
    array(
      'field_name' => $field_name, 
      'group_index' => $group_index, 
      'field_index' => $field_index,
      'param' => $params,
      'attr' => $attr,
      'post_id' => $post_id,
      'tag_img' => (boolean) $tag_img,
      'wp_size' => $wp_size
    )
  );
}

function get_field( $field_name , $group_index = 1 , $post_id = NULL ){
  global $post,$wpdb;
	
  if(!$post_id){ $post_id = $post->ID; }

  $sql = sprintf(
    "SELECT m.field_name, c.type, w.meta_value, m.field_count, c.options " .
    "FROM %s m " .
    "JOIN %s c ON m.field_name = c.name " .
    "JOIN %s w ON w.meta_id = m.meta_id " .
    "WHERE m.post_id = %d AND m.field_name = '%s' AND m.group_count = %d AND w.meta_value <> '' ".
    "ORDER BY m.group_count,c.display_order, m.field_count ASC",
    MF_TABLE_POST_META,
    MF_TABLE_CUSTOM_FIELDS,
    $wpdb->postmeta,
    $post_id,
    $field_name,
    $group_index
  );
  $fields = $wpdb->get_results($sql,ARRAY_A);

  $result = array();
  foreach($fields as $field){
    $type    = $field['type'];
    $options = $field['options'];
    $value   = $field['meta_value'];
    $field_index = $field['field_count'];

    if(is_serialized($value)){
      $value= unserialize( $value );
    }

    $result[$field_index] = _processed_value($value, $type, $options,1);

  }
  return $result;
}

function get_group( $group_name , $post_id = NULL ){
  global $post,$wpdb; 
	
  if(!$post_id){ $post_id = $post->ID; }
  
  $sql = sprintf(
    "SELECT m.field_name, c.type, w.meta_value, m.group_count, m.field_count, c.options ".
    "FROM %s m " .
    "JOIN %s c ON m.field_name = c.name " .
    "JOIN %s g ON c.custom_group_id = g.id  " .
    "JOIN %s w ON w.meta_id = m.meta_id " .
    "WHERE m.post_id = %d AND g.name = '%s' AND w.meta_value <> '' ".
    "ORDER BY m.group_count,c.display_order, m.field_count ASC",
    MF_TABLE_POST_META,
    MF_TABLE_CUSTOM_FIELDS,
    MF_TABLE_CUSTOM_GROUPS,
    $wpdb->postmeta,
    $post_id,
    $group_name
  );

  $fields = $wpdb->get_results($sql,ARRAY_A);

  $result = array();
  foreach($fields as $field){
    $type    = $field['type'];
    $options = $field['options'];
    $value   = $field['meta_value'];
    $group_index = $field['group_count'];
    $field_index = $field['field_count'];
    $field_name = $field['field_name'];

    if(is_serialized($value)){
      $value= unserialize( $value );
    }

    $result[$group_index][$field_name][$field_index] = _processed_value($value, $type, $options,1);
  }
  return $result;
}

function get_label($field_name,$post_id=NULL){
  global $post,$wpdb; 
	
  if(!$post_id){ $post_id = $post->ID; }
  
  $field = get_data($field_name,1,1,$post_id);
  if(!$field) return FALSE;

  return $field['label'];
}


/* AUX FUNCITONS */

/*
 * Generate an image from a field value
 *
 * Accepts a single options, an array of settings. 
 * These are the parameteres it supports:
 *
 *   'field_name' => (string) the name of the field which holds the image value, 
 *   'group_index' => (int) which group set to display, 
 *   'field_index' => (int) which field set to display,
 *   'param' => (string|array) a html parameter string to use with PHPThumb for the image, can also be a key/value array
 *   'attr' => (array) an array of extra attributes and values for the image tag,
 *   'post_id' => (int) a specific post id to fetch,
 *   'tag_img' => (boolean) a flag to determine if an img tag should be created, or just return the link to the image file
 *   'wp_size' => (string) size of image for image_media
 *
 */
function create_image($options){
  global $post;
	
  // establish the default values, then override them with 
  // whatever the user has passed in
  $options = array_merge(
    array(
      // the default options
      'field_name' => '', 
      'group_index' => 1, 
      'field_index' => 1,
      'param' => NULL,
      'attr' => NULL,
      'post_id' => NULL,
      'tag_img' => true,
      'wp_size' => 'original'
    ), (array) $options);
	
  // finally extract them into variables for this function
  extract($options);
  
  // check for a specified post id, or see if the $post global has one
  if($post_id){
    
  }elseif(isset($post->ID)){
    $post_id = $post->ID;
  } else {
    return false;
  }

  // basic check
  if(empty($field_name)) return FALSE;
	
  $field = get_data($field_name,$group_index, $field_index,$post_id);
  if(!$field) return FALSE;

  $field_type = $field['type'];
  $field_css = $field['options']['css_class'];
  unset($field['options']['css_class']);
  $field_param = $field['options'];
  
  $field_value = $field['meta_value'];

  if($field_type == 'image_media'){
    $data = wp_get_attachment_image_src($field_value, $wp_size);
    $field_value = $data[0];
  }

  if(empty($field_value)) return "";
  
  $tmp_param = array();
  foreach($field_param as $p_k => $p_v){
    if( !empty($p_v) ){
      if( $p_k == 'max_height' ) $p_k ='h';
      if( $p_k == 'max_width' ) $p_k = 'w';
      if( $p_k == 'custom'){
        $tmp_param[] = $p_v;
      }else{
        $tmp_param[] = sprintf('%s=%s',$p_k,$p_v);
      }
    }
  }
  if( count($tmp_param) ){
    $field_param = implode('&',$tmp_param);
  }else{
    $field_param = '';
  }

  // override the default phpthumb parameters if needed
  // works with both strings and arrays
  if(!empty($param)) {
    if(is_array($param)){
      $p = array();
      foreach($param as $k => $v){
        $p[] = $k."=".$v;
      }
      $field_param = implode('&', $p);
    } else {
      $field_param = $param;
    }
  }

  // check if exist params, if not exist params, return original image
  if ( empty($field_param) ){
    if($field_type == 'image'){
      $field_value = MF_FILES_URL.$field_value;
    }
  }else{
    //generate or check de thumb
    $field_value = aux_image($field_value,$field_param,$field_type);
  }

  if($tag_img){
    // make sure the attributes are an array
    if( !is_array($attr) ) $attr = (array) $attr;

    // we're generating an image tag, but there MAY be a default class. 
    // if one was defined, however, override it
    if( !isset($attr['class']) && !empty($field_css) ) 
      $attr['class'] = $field_css;

    // ok, put it together now
    if(count($attr)){
      $add_attr = NULL;
      foreach($attr as $k => $v){
        $add_attr .= sprintf('%s="%s"',$k,$v);
      }
      $finalString = "<img src='".$field_value."' ".$add_attr." />";
    }else{
      $finalString = "<img src='".$field_value."' />";
    }
  }else{
    $finalString = $field_value;
  }
  return $finalString;
}

function aux_image($value,$params,$type = NULL){

  $md5_params = md5($params);

  $thumb_path = MF_CACHE_DIR.'th_'.$md5_params."_".$value;
  $thumb_url  = MF_CACHE_URL.'th_'.$md5_params."_".$value;
  $image_path = MF_FILES_DIR.$value;
  $name_image = $value;

  if($type == 'image_media'){
    $data = preg_split('/\//',$value);
    $thumb_path = MF_CACHE_DIR.'th_'.$md5_params."_".$data[count($data)-1];
    $thumb_url = MF_CACHE_URL.'th_'.$md5_params."_".$data[count($data)-1];
    $image_path = str_replace(WP_CONTENT_URL,WP_CONTENT_DIR,$value);
    $name_image = $data[count($data)-1];
  }

  if (file_exists($thumb_path)) {
    $value = $thumb_url;
  }else{
    //generate thumb
    $create_md5_filename = 'th_'.$md5_params."_".$name_image;
    $output_filename = MF_CACHE_DIR.$create_md5_filename;
    $final_filename = MF_CACHE_URL.$create_md5_filename;

    $default = array(
      'zc'  => 1,
      'w'   => 0,
      'h'   => 0,
      'q'   => 85,
      'src' => $image_path,
      'far' => false,
      'iar' => false
    );

    $size = @getimagesize($image_path);
    $defaults['w'] = $size[0];
    $defaults['h'] = $size[1];

    $params_image = explode("&",$params);

    foreach($params_image as $param){
      if($param){
        $p_image=explode("=",$param);
        $default[$p_image[0]] = $p_image[1];
      }
    }
    if( ($default['w'] > 0) && ($default['h'] == 0) ){
      $default['h'] = round( ($default['w']*$defaults['h']) / $defaults['w'] );
    }elseif( ($default['w'] == 0) && ($default['h'] > 0) ){
      $default['w'] = round( ($default['h']*$defaults['w']) / $defaults['h'] );
    }
	
    $MFthumb = MF_PATH.'/MF_thumb.php';
    require_once($MFthumb);
    $thumb = new mfthumb();
    $thumb_path = $thumb->image_resize(
      $default['src'],
      $default['w'],
      $default['h'],
      $default['zc'],
      $default['far'],
      $default['iar'],
      $output_filename,
      $default['q']
    );
        
	
    if ( is_wp_error($thumb_path) )
      return $thumb_path->get_error_message();
    $value = $final_filename;
  }
  return $value;
}

function _processed_value($value, $type, $options = array(), $image_array = 0 ){
		
  if(is_serialized($options)){
    $options= unserialize( $options );
  }

  $result = '';
  switch($type){
    case 'audio':
    case 'file':
      if( !empty($value) ) $result = MF_FILES_URL . $value;
      break;
    case 'image': 
      if($image_array){
        if( !empty($value) ){
          unset($options['css_class']);
          $options = _processed_params($options);
          $result['original'] = MF_FILES_URL . $value;
          if( empty($options) ){
            $result['thumb'] = $result['original'];
          }else{
            $result['thumb'] = aux_image($value,$options,$type);
          }
        }
      }else{
        if( !empty($value) ) $result = MF_FILES_URL . $value;
      }
      break;
    case 'checkbox':
      if ($value == '1')  $result = 1; else $result = 0; 
      break;
    case 'datepicker':
      if( !empty($value) ){
        $result = date($options['format'],strtotime($value)); 
      }
      break;
    case 'dropdown':
      $result = ($options['multiple'] == '0')? $value[0] : $value ;
      break;
    case 'image_media':
      if($image_array){
        if( !empty($value) ){
          unset($options['css_class']);
          $options = _processed_params($options);
          
          $data = wp_get_attachment_image_src($value,'original');
          $result['original'] = $data[0];
          if( empty($options) ){
            $result['thumb'] = $result['original'];
          }else{
            $result['thumb'] = aux_image($result['original'],$options,$type);
          }
        }
      }else{
        if( !empty($value) ){
          $data = wp_get_attachment_image_src($value,'original');
          $result = $data[0];
        }
      }
      
      break;
    case 'multiline':
      $result = apply_filters('the_content',$value);
      break;
    default:
      $result = $value; 
      break;
  }
  return $result;
}


function get_data( $field_name, $group_index=1, $field_index=1, $post_id ){
  global $wpdb;

  $field_name = str_replace(" ","_",$field_name);

  $sql = sprintf(
    "SELECT m.meta_id,w.meta_value,f.type,f.options,f.description,f.label " .
    "FROM %s m " .
    "JOIN %s w ON m.meta_id = w.meta_id " .
    "JOIN %s f ON m.field_name = f.name " .
    "WHERE m.post_id = %d AND m.field_name = '%s' AND m.group_count = %d AND m.field_count = %d ",
    MF_TABLE_POST_META,
    $wpdb->postmeta,
    MF_TABLE_CUSTOM_FIELDS,
    $post_id,
    $field_name,
    $group_index,
    $field_index
  );

  $result = $wpdb->get_row($sql,ARRAY_A);
  
  if( empty($result) ) return NULL;

  $result['options'] = unserialize($result['options']);

  if(is_serialized($result['meta_value'])){
    $result['meta_value'] = unserialize( $result['meta_value'] );
  }
  
  return $result;
}

function _resolve_linebreaks($data = NULL){
  $data = preg_replace(array("/\r\n/","/\r/","/\n/"),"\\n",$data);
  return $data;
}

function _processed_params($params = array()){
  
  $tmp_param = array();
  foreach($params as $p_k => $p_v){
    if( !empty($p_v) ){
      if( $p_k == 'max_height' ) $p_k ='h';
      if( $p_k == 'max_width' ) $p_k = 'w';
      if( $p_k == 'custom'){
        $tmp_param[] = $p_v;
      }else{
        $tmp_param[] = sprintf('%s=%s',$p_k,$p_v);
      }
    }
  }
  if( count($tmp_param) ){
    $field_param = implode('&',$tmp_param);
  }else{
    $field_param = '';
  }

  return $field_param;
}

function mf_get_form($posttype, $postid=NULL){
	global $mf_domain, $mf_post_values;

	if (!current_user_can('edit_posts')) {
		_e('You don’t have the required permissions to edit the page/post.');
		return;
	}

	// load admin/mf_post.php by hand
	require_once 'admin/mf_post.php';

	// create a new post object
	$_GET['post_type'] = $posttype; // setting posttype for the form, @todo refactoring or some other proper solution needed to solve this
	$p = new mf_post();

	// load the existing post
	$posttitle = '';
	$ep = get_post($postid);
	if(is_object($ep) && !is_null($postid)){
		$posttitle = $ep->post_title;
    		$mf_post_values = $p->mf_get_post_values($postid);
	}

	// load js and css
	wp_enqueue_script( 'jquery' ); // @todo quickfix for proper loading order
	wp_enqueue_script( 'suggest' ); // for autocompletion (suggestion)
	wp_enqueue_script( 'jquery.validate',MF_BASENAME.'js/third_party/jquery.validate.min.js', array( 'jquery' ) );
	wp_enqueue_script( 'jquery.metadata',MF_BASENAME.'js/third_party/jquery.metadata.js', array( 'jquery' ) );
	wp_enqueue_script( 'mf_admin',MF_BASENAME.'js/mf_admin.js', array( 'jquery.validate', 'jquery.metadata', 'jquery' ) );
	$p->load_js_css_base();
	$p->load_js_css_fields();
        $p->general_option_multiline();

	// display the message
	if(isset($_GET['mf_message'])){
		switch($_GET['mf_message']){
			case 'success':
				echo '<div id="message" class="updated">
					'.__('The post was saved successfully.').'
				</div>';
			break;
		}
	}

	// loading the settings of the post type
	$pt = mf_posttype::get_post_type($posttype);

	// start the form
	echo '<form name="post" action="" method="post" id="post">';

	echo '<input type="hidden" name="mf-frontend-form" value="true">'; // the mf frontend handling looks for this

	echo '<input type="hidden" name="post-type" value="'.$posttype.'">'; // the post type
	echo '<input type="hidden" name="post-id" value="'.$postid.'">'; // the post id

	// the title
	echo '<label for="post-title"><span class="name">Title</span></label>';
	echo '<div class="clear"></div>';
	echo '<input id="post-title" name="post-title" class="mf-title" value="'.$posttitle.'" validate="required:true"><br>';
	echo '<div class="clear"></div>';

	// the editor (if it is supported)
	if(post_type_supports($posttype, 'editor')){
		// @todo refactoring, so javascript can be loaded more cleanly
      		add_thickbox();
		wp_enqueue_script('media-upload');
		wp_enqueue_script('editor'); // load admin/mf_editor.js (switchEditor)
		mf_autoload('mf_tiny_mce'); // load admin/mf_tiny_mce.php (tinyMCE)
		add_action( 'print_footer_scripts', 'mf_tiny_mce', 25 ); // embed tinyMCE
		add_action( 'print_footer_scripts', array($p, 'media_buttons_add_mf'), 51 );
		
		//echo plugins_url('magic-fields-2/field_types/multiline_field/multiline_field.js');
		wp_enqueue_script( 'multiline_field', plugins_url('magic-fields-2/field_types/multiline_field/multiline_field.js') );
     
		/* 
		$output .= sprintf('<div class="tab_multi_mf">');
		$output .= sprintf('<a onclick="del_editor(\'%s\');" class="edButtonHTML_mf">HTML</a>',$field['input_id']);
		$output .= sprintf('<a onclick="add_editor(\'%s\');" class="edButtonHTML_mf current" >Visual</a>',$field['input_id']);
		$output .= sprintf('</div>');
      		$output .= '<div class="clear"></div>'; // @todo ugly fix for front-end multiline textbox floating
		*/
		$output .= '<textarea id="post-content" class="mf_editor wp-editor theEditor" rows="15"></textarea>';
		echo $output;
	}

      	// getting the groups (each group is a metabox)
	$groups = $p->get_groups_by_post_type($posttype);
      	foreach( $groups as $group ) {
		// fill in the groups
		echo '<div id="mf_'.$group['id'].'" class="postbox">';
		echo '<h3 class="hndl"><span>'.$group['label'].'</span></h3>';
		$p->mf_metabox_content($p, array( 'args' => array('group_info' => $group)));
		echo '</div>';
	}

	// checking if tags are enabled 
	// @todo find a proper way to check with, something similar to post_type_supports
	if( ( isset($pt['taxonomy']) && array_key_exists('post_tag', $pt['taxonomy']) ) || $posttype == 'post' ){
		// get the tags of the post (this is supposed to be inside The Loop, but it works)
		$posttags = '';
		$tags = get_the_tags($postid);
		if ($tags) {
			foreach($tags as $tag) {
				$posttags .= $tag->name . ', '; 
			}
			$posttags = substr($posttags, 0, -2); // cut off the last comma
		}

		echo '<label for="post-tags"><span class="name">Tags</span></label><br>';
		echo '<input id="post-tags" name="post-tags" value="'.$posttags.'" class="mf-tags" /><br>';
		echo '<p class="howto">Separate tags with commas</p>';
	}

	// checking if categories are enabled
	if( ( isset($pt['taxonomy']) && array_key_exists('category', $pt['taxonomy']) ) || $posttype == 'post' ){
		echo '<label for="post-categories"><span class="name">Categories</span></label><br>';
		
		// listing all the categories
		$categories = get_categories();
		$p_categories = get_the_category($postid);
		foreach($categories as $c){
			$checked = '';
			foreach($p_categories as $pc){
				if($c->term_id == $pc->term_id){
					// if the post has the listed category
					$checked = 'checked="checked"';
					break;
				}
			}
			echo '<input type="checkbox" name="post-categories['.$c->term_id.']" '.$checked.'>'.$c->name.'<br>';
		}
	}

	// button for saving the post
	echo '<p class="submit"><input type="submit" name="save" id="save" class="button" value="'. __('Save'). '"  /></p>';

	// end of form
	echo '</form>';
}

// add post handling function for front end form function
add_action('init', 'mf_handle_form');
function mf_handle_form(){
	if( !isset($_POST) || empty($_POST) || !isset($_POST['mf-frontend-form'])){
		// if there's no post data to handle exit the function
		return;
	}

	// user id
	$current_user = wp_get_current_user();

	$poststatus = 'publish';
	if(!is_numeric($_POST['post-id'])) $poststatus = 'pending';

	//print_r(array_keys($_POST['post-categories']));
	//die();

	// Create post object
	$my_post = array(
		'ID' => $_POST['post-id'],
		'post_title' => $_POST['post-title'],
		'post_content' => '',
		'post_status' => $poststatus,
		'post_author' => $current_user->ID,
		'post_type' => $_POST['post-type'],
		'post_category' => array_keys($_POST['post-categories']),
		'tags_input' => $_POST['post-tags'],

		// the magic fields elements will be included anyway since they are in the $_POST
	);

	// Insert the post into the database
	$pid = wp_insert_post( $my_post );
	
	// redirect on the new post on save
	//$link = get_permalink( $pid );
	$link = '?mf_message=success';
	wp_safe_redirect( $link );
	exit(); // don't do anything after redirect
}

// add addloadevent javascript to frontend (ajaxurl, etc)
add_action('wp_head', mf_addLoadEvent);
function mf_addLoadEvent (){
	// @todo: this function is an ugly hack
	global $current_user;

	echo "
	<script type=\"text/javascript\">
		addLoadEvent = function(func){if(typeof jQuery!=\"undefined\")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
		var userSettings = {
			'url': '".$_SERVER["REQUEST_URI"]."',
			'uid': '".$current_user->ID."',
			'time':'".time()."'
		},
		ajaxurl = '".admin_url('admin-ajax.php')."',
		pagenow = 'dashboard',
		typenow = '',
		adminpage = 'index-php',
		thousandsSeparator = ',',
		decimalPoint = '.',
		isRtl = 0;
	</script>";
}
