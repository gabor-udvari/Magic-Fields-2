<?php
ob_start();
//use wp-load. Normally right here, but if it's not...
if( file_exists('../../../../../wp-load.php')){
  require_once('../../../../../wp-load.php');
  $loaded = true;
} elseif( file_exists( dirname(__FILE__).'/../../mf-config.php')){
  include_once(dirname(__FILE__).'/../../mf-config.php');

  include_once('./mf-config.php');
  require_once(MF_WP_LOAD);
  $loaded = true;
}

if($loaded  !== true){
  die('Could not load wp-load.php, edit/add mf-config.php and define MF_WP_LOAD to point to a valid wp-load file');
}
ob_end_clean();

$MFthumb = MF_PATH.'/MF_thumb.php';

require_once($MFthumb);

//Default Values
$default = array(
  'iar'   => 0,
  'far'   => 0,
  'zc'    => 1,
  'q'	=>  95,
  'w'	=>  0,
  'h'	=> 0,
  'src' => ''
);

//getting the name of the image
$dir = preg_quote(MF_FILES_URL, '/');
//$pattern = '/'.$dir.'([0-9\_a-z\/\-\.]+\.(jpg|jpeg|png|gif))/i'; // old one based on MF_FILES_URL (wrong)
$pattern = '/.*\/'.'([0-9\_a-z\/\-\.]+\.(jpg|jpeg|png|gif))/i';
preg_match($pattern, $_GET['src'], $match);
$image_name_clean = $match[1];
$extension = $match[2];

//is wp mu o wp network
if(isset($current_blog)){
  $image_name_clean = preg_replace('/blogs.dir\/(\d+)\//','',$image_name_clean);
}


//Getting the original size of the image
/*
if( preg_match('/'.MF_FILES_NAME.'/',$image_name_clean) ){
  // needed for the upload function?
  $image_name_clean = preg_replace('/'.MF_FILES_NAME.'\//','',$image_name_clean);
  $file = MF_FILES_DIR.$image_name_clean;
}else if(file_exists(MF_FILES_DIR.$image_name_clean)){
  // needed for thumbnails
  $file = MF_FILES_DIR.$image_name_clean;
}else{
  // defaults to wp_content
  $file = WP_CONTENT_DIR.DS.$image_name_clean;
}
*/
$file = ABSPATH . str_replace(get_site_url(), '', $_GET['src']);

if(file_exists($file) && (empty($_GET['w']) || empty($_GET['h']))){
	$size = @getimagesize($file);
	$default['w'] = $size[0];
	$default['h'] = $size[1];
}
//TODO: sanitize the variables
$params = array();				
foreach($_GET as $key => $value){
  if(in_array($key,array('zc','w','h','q','src','far','iar'))){
    $params[$key] = $value;
  }
}

$params = array_merge($default,$params);
$md5_params =  md5("w=".$params['w']."&h=".$params['h']."&q=".$params['q']."&zc=".$params['zc']."&far=".$params['far']."&iar=".$params['iar']);

//The file must be "jpg" or "png" or "gif" 
if(!in_array(strtolower($extension),array('jpg','jpeg','png','gif'))){
  return false;
}

//
$image_sin = preg_split('/\//',$image_name_clean);
$new_image_clean = $image_sin[count($image_sin)-1];
//name with a png extension
$image_name = $md5_params."_".$new_image_clean;
//this code can be refactored
if(file_exists(MF_CACHE_DIR.$image_name)){
  //Displaying the image
  $size = getimagesize(MF_CACHE_DIR.$image_name);
  $handle = fopen(MF_CACHE_DIR.$image_name, "rb");
  $contents = NULL;
  while (!feof($handle)) {
    $contents .= fread($handle, 1024);
  }
  fclose($handle);
	
  header("Cache-Control: public"); 
  header("Content-type: image/".$extension); 
  header("Content-Disposition: inline; filename=\"".MF_CACHE_DIR.$image_name."\""); 
  header('Content-Length: ' . filesize(MF_CACHE_DIR.$image_name)); 
  echo $contents;
}else{
  //generating the image
  $thumb = new mfthumb();
  //$file = '/2012/11/ibm.png';
  $thumb_path = $thumb->image_resize($file,$params['w'],$params['h'],$params['zc'],$params['far'],$params['iar'],MF_CACHE_DIR.$image_name);
  //Displaying the image
  if(is_wp_error($thumb_path)){
    // error checking
    // @todo: mfthumb only generates an image file, how do we display errors?
  }else if(file_exists($thumb_path)){
    // if no error and the image file exists, display the image
    $size = getimagesize($thumb_path);
    $handle = fopen($thumb_path, "rb");
    $contents = NULL;
    while (!feof($handle)) {
      $contents .= fread($handle, filesize($thumb_path));
    }
    fclose($handle);
    
    header("Cache-Control: public"); 
    header("Content-type: image/".$extension); 
    header("Content-Disposition: inline; filename=\"".$thumb_path."\""); 
    header('Content-Length: ' . filesize($thumb_path)); 
    echo $contents;
  }
}
