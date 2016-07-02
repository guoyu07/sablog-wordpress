<?php
/**
 * Sablog-X 2.0 20100301 ->WordPress 3.5.1 数据转移程序 code by Neeao
 *
 * 说明:本程序用来实现Sablog-X至WordPress数据转移.
 * 
 * 本程序基于maker Sablog-X 1.6 => WordPress 2.2.3 转换程序 – 080422 测试版
 * 
 * updated : 2013-6-15
 *
 */
error_reporting(0);

$s['hostname'] = 'localhost';
$s['username'] = 'neeao.com';
$s['password'] = 'neeao.com';
$s['dbname']   = 'neeao.com';
$s['sa_pre']   = 'sablog_';
$s['wp_pre']   = 'wp_';
$s['sa_dir']   = 'sa/';
$s['wp_dir']   = './';
$s['wp_url']   = 'http://neeao.com/';

#$s['web_root'] = 'D:\\wwwroot\\maker\\wwwroot\\';

$s['wp_root']  = $s['web_root'].$s['wp_dir'];

$att = $art = $ud = $attinfo = array();

title();
$db=mysql_connect ( $s['hostname'], $s['username'], $s['password']);
if(!$db){
	echo "数据库连接错误,请确认配置信息.";
}
mysql_select_db($s['dbname'],$db);
mysql_query('set names "utf8"',$db);

if(!$_GET['step']){
	m("开始进行数据转移","?step=1");
}

if($_GET['step'] == '1'){
	/**
	 * 移动附件
	 */
	$imgs = array();
	$sql = "SELECT * 
			FROM `".$s['sa_pre']."attachments` 
			ORDER BY `attachmentid` ASC";
	$res = mysql_query($sql);
	while($a = mysql_fetch_array($res)){
		if(!file_exists($s['wp_dir'].'/wp-content/uploads/')){
			mkdir($s['wp_dir'].'/wp-content/uploads/','0777');
		}
		if(!file_exists($s['wp_dir'].'/wp-content/uploads/'.date("Y",$a['dateline']))){
			mkdir($s['wp_dir'].'/wp-content/uploads/'.date("Y",$a['dateline']),'0777');
		}

		if(!file_exists($s['wp_dir'].'/wp-content/uploads/'.date("Y",$a['dateline']).'/'.date("m",$a['dateline']))){
			mkdir($s['wp_dir'].'/wp-content/uploads/'.date("Y",$a['dateline']).'/'.date("m",$a['dateline']),'0777');
		}
		#mb_convert_encoding($a['filename'], "gbk", "utf-8");
		$new = $s['wp_dir'].'wp-content/uploads/'.date("Y",$a['dateline']).'/'.date("m",$a['dateline']).'/'.$a['attachmentid'].'_'.$a['filename'];
		
		if(!copy('sa/attachments'.$a['filepath'],$new)){
			echo '<b>ERROR:</b>'.$a['filepath'].' --> '.$new."<br>\n";
		}else{
			$type = getimagesize($new);
			if(in_array($type[2],array(1,2,3))){
				$imgs[] = $new;
			}
		}
	}
	arr2files($imgs,'imgs', 'imgs.inc');
	if($imgs)
		m("附件移动完毕,下一部生成缩略图","?step=1_5");
	else
		m("附件移动完毕","?step=2");
}

if($_GET['step'] == '1_5'){
	include 'imgs.inc.php';
	$num = isset($_GET['num'])?$_GET['num']:0;
	if($imgs){
		for($i=$num;$i<$num+10;$i++){
			wp_create_thumbnail($imgs[$i]);
			if($i >= count($imgs)){
				m('缩略图生成完毕','?step=2');
				exit;
			}
		}
		$n = (string)($num+10);
		$p = ($num+10)/count($imgs);
		m($p.'%', '?step=1_5&num='.$n);
	}
}

if($_GET['step'] == '2'){
	/**
	 *导入分类
	 */
	metas("category");
	m("分类数据导入完毕","?step=2_5");
}

if($_GET['step'] == '2_5'){
	/**
	 *导入tag
	 */
	metas("tag");
	m("tag数据导入完毕","?step=3");
}

if($_GET['step'] == '3'){
	/**
	 *导入附件数据
	 */

	$sql = "SELECT * 
			FROM `".$s['sa_pre']."attachments` 
			ORDER BY `attachmentid` ASC";
	$result = mysql_query($sql);
	while($attach = mysql_fetch_array($result)){
		$sql = "INSERT INTO `wp_posts` (
					`ID` ,
					`post_author` ,
					`post_date` ,
					`post_date_gmt` ,
					`post_content` ,
					`post_title` ,
					`post_excerpt` ,
					`post_status` ,
					`comment_status` ,
					`ping_status` ,
					`post_password` ,
					`post_name` ,
					`to_ping` ,
					`pinged` ,
					`post_modified` ,
					`post_modified_gmt` ,
					`post_content_filtered` ,
					`post_parent` ,
					`guid` ,
					`menu_order` ,
					`post_type` ,
					`post_mime_type` ,
					`comment_count`
				)VALUES (
					NULL ,
					'1',
					'".date("Y-m-d H:i:s",$attach['dateline'])."',
					'".date("Y-m-d H:i:s",$attach['dateline'])."',
					'',
					'".$attach['attachmentid'].'_'.$attach['filename']."',
					'',
					'inherit',
					'open',
					'open',
					'',
					'".str_replace('.','',$attach['filename'])."',
					'',
					'',
					'".date("Y-m-d H:i:s",$attach['dateline'])."',
					'".date("Y-m-d H:i:s",$attach['dateline'])."',
					'',
					'".$attach['articleid']."',
					'".$s['wp_url'].'wp-content/uploads/'.date("Y",$attach['dateline']).'/'.date("m",$attach['dateline']).'/'.$attach['attachmentid'].'_'.$attach['filename']."',
					'0',
					'attachment',
					'".$attach['filetype']."',
					'0'
				)";
			mysql_query($sql);
			$att[$attach['attachmentid']] = mysql_insert_id();
			if($attach['isimage']){
				$za = getimagesize($s['wp_dir'].'wp-content/uploads/'.date("Y",$attach['dateline']).'/'.date("m",$attach['dateline']).'/'.$attach['attachmentid'].'_'.mb_convert_encoding($attach['filename'], "gbk", "utf-8"));
				$zz = getimagesize($s['wp_dir'].'wp-content/uploads/'.date("Y",$attach['dateline']).'/'.date("m",$attach['dateline']).'/'.$attach['attachmentid'].'_'.t(mb_convert_encoding($attach['filename'], "gbk", "utf-8")));

				$i = array(
					'width' => $za[0],
					'height'=> $za[1],
					'hwstring_small'=> "height='".$zz[1]."' width='".$zz[0]."'",
					'file'=> $s['wp_root']."wp-content/uploads/".date("Y",$attach['dateline']).'/'.date("m",$attach['dateline']).'/'.$attach['filename'],
					'thumb' => t($attach['attachmentid'].'_'.$attach['filename'])
				);
			}else{
				$i = array();
			}
			$sql = "INSERT INTO `wp_postmeta` (
						`meta_id` ,
						`post_id` ,
						`meta_key` ,
						`meta_value`
					)VALUES (
						NULL , 
						'".$att[$attach['attachmentid']]."', 
						'_wp_attachment_metadata', 
						'".addslashes(serialize($i))."'
					)";
			#echo $sql."<br>";
			mysql_query($sql);
			$sql = "INSERT INTO `wp_postmeta` (
						`meta_id` ,
						`post_id` ,
						`meta_key` ,
						`meta_value`
					)VALUES (
						NULL , 
						'".$att[$attach['attachmentid']]."', 
						'_wp_attached_file', 
						'".addslashes($s['wp_root'])."wp-content/uploads/".date("Y",$attach['dateline']).'/'.date("m",$attach['dateline']).'/'.$attach['attachmentid'].'_'.$attach['filename']."'
					)";
			mysql_query($sql);

	}
	arr2files($att,'att','att.inc');
	m("附件数据导入完毕","?step=4");
}
if($_GET['step'] == '4'){
	/** 
	 * 移动文章
	 */
	include_once "att.inc.php";
	include_once "category.inc.php";
	include_once "tag.inc.php";

	$sql = "SELECT * FROM `".$s['sa_pre']."articles` ORDER BY `articleid` ASC";
	$res = mysql_query($sql);
	while($a = mysql_fetch_array($res)){
		$a['content'] = preg_replace("/\[attach=(\d+)\]/ie", "a2s('\\1')", $a['content']);
		$sql = "INSERT INTO `wp_posts` (
					`ID` ,
					`post_author` ,
					`post_date` ,
					`post_date_gmt` ,
					`post_content` ,
					`post_title` ,
					`post_excerpt` ,
					`post_status` ,
					`comment_status` ,
					`ping_status` ,
					`post_password` ,
					`post_name` ,
					`to_ping` ,
					`pinged` ,
					`post_modified` ,
					`post_modified_gmt` ,
					`post_content_filtered` ,
					`post_parent` ,
					`guid` ,
					`menu_order` ,
					`post_type` ,
					`post_mime_type` ,
					`comment_count`
				)VALUES (
					NULL ,
					'1',
					'".date("Y-m-d H:i:s",$a['dateline'])."',
					'".date("Y-m-d H:i:s",$a['dateline'])."',
					'".addslashes($a['content'])."',
					'".$a['title']."',
					'',
					'publish',
					'open',
					'open',
					'".$a['readpassword']."',
					'".substr(urlencode($a['title']),150)."',
					'',
					'',
					'".date("Y-m-d H:i:s",$a['dateline'])."',
					'".date("Y-m-d H:i:s",$a['dateline'])."',
					'',
					'0',
					'".$s['wp_url']."?p="."',
					'0',
					'post',
					'',
					'".$a['comments']."'
				)";

		$ret=mysql_query($sql);
		if($ret){
			$new_art_id = mysql_insert_id();
			$art[$a['articleid']] = $new_art_id;
			//分类
			relationships($a['articleid'],$new_art_id,$category);
			//tags
			relationships($a['articleid'],$new_art_id,$tag);
		}else{
			echo "mysql错误：".mysql_error();
			print_r($sql);
		}
	}
	arr2files($art,'art','art.inc');
	m("文章数据导入完毕","?step=5");
}

if($_GET['step'] == '5'){
	include_once "art.inc.php";
	$sql = "SELECT * 
			FROM `wp_posts` 
			WHERE `post_type`='post'";
	$res = mysql_query($sql);
	while($a = mysql_fetch_array($res)){
		global $s;
		$sql = "UPDATE `wp_posts` 
				SET `guid` = '".$s['wp_url']."?p=".$a[ID]."' 
				WHERE `ID` =".$a['ID']." 
				LIMIT 1 ;";
		mysql_query($sql);
	}
	$sql = "SELECT * 
			FROM `wp_posts` 
			WHERE `post_type`='attachment'";
	$res = mysql_query($sql);
	while($a = mysql_fetch_array($res)){
		$sql = "UPDATE `wp_posts` 
				SET `post_parent` = '".$art[$a['post_parent']]."' 
				WHERE `ID` =".$a['ID']." LIMIT 1 ;";
		mysql_query($sql);
	}
	m("数据矫正完毕","?step=6");
}

if($_GET['step'] == '6'){
	include_once "art.inc.php";
	$sql = "SELECT * 
			FROM `".$s['sa_pre']."comments` 
			WHERE `visible`=1 ORDER BY `dateline` ASC";
	
	$res = mysql_query($sql);
	while($com = mysql_fetch_array($res)){
	
		$sql = "INSERT INTO `wp_comments` (
					`comment_ID` ,
					`comment_post_ID` ,
					`comment_author` ,
					`comment_author_email` ,
					`comment_author_url` ,
					`comment_author_IP` ,
					`comment_date` ,
					`comment_date_gmt` ,
					`comment_content` ,
					`comment_karma` ,
					`comment_approved` ,
					`comment_agent` ,
					`comment_type` ,
					`comment_parent` ,
					`user_id`
				)VALUES (
					NULL ,
					'".$art[$com['articleid']]."',
					'".$com['author']."',
					'".(strpos($com['url'],'@')?$com['url']:'')."',
					'".(!strpos($com['url'],'@')?$com['url']:'')."',
					'".$com['ipaddress ']."',
					'".date("Y-m-d H:i:s",$com['dateline'])."',
					'".date("Y-m-d H:i:s",$com['dateline'])."',
					'".$com['content']."',
					'0',
					'1',
					'',
					'',
					'0',
					'0'
				);";
	
		mysql_query($sql);
	}
	m("数据转移完毕","#");
}


function relationships($old_id,$new_id,$terms){
	global $db;
	if(count($terms)>0){
		foreach($terms as $tid=>$taxids){
			if(in_array($old_id,$taxids)){
				$sql = "INSERT INTO `wp_term_relationships` (
					`object_id` ,
					`term_taxonomy_id` ,
					`term_order`
				)
				VALUES (
					".$new_id." ,
					".$tid.",
					0
				)";
				mysql_query($sql,$db);
			}	
		}
	}
}

function metas($type){
	global $db;
	global $s;
	$t=array();
	$post_tag="post_tag";
	if($type=="category"){
		$post_tag="category";
	}
	$sql = "SELECT * FROM `".$s['sa_pre']."metas` where type='".$type."' ORDER BY `mid` ASC";
	$result = mysql_query($sql,$db);
	while($tag = mysql_fetch_array($result)){
		$sql_term = "INSERT INTO `wp_terms` (`name` ,`slug`)VALUES ('".$tag['name']."','".$tag['slug']."');";
		if(mysql_query($sql_term)){
			$new_id = mysql_insert_id();
			$sql_taxonomy = "INSERT INTO `wp_term_taxonomy` (
				`term_id` ,
				`taxonomy`,
				`description`,
				`parent`,
				`count`
				)VALUES (
				".$new_id.",
				'".$post_tag."',
				'',
				0,
				0
			);";
			if(mysql_query($sql_taxonomy,$db)){
				echo $tag['name']." 导入成功<br>";
			}
			//term_taxonomy_id
			$term_taxonomy_id=mysql_insert_id($db);
			//查询mid 对应的文章id
			$sql_rs="SELECT cid FROM ".$s['sa_pre']."relationships WHERE mid = ".$tag['mid'];
			$cid_result = mysql_query($sql_rs,$db);
			$cids = array();
			while($relationship = mysql_fetch_array($cid_result)){
				array_push($cids,$relationship['cid']);
			}
			$sql_count="update `wp_term_taxonomy` set count=".count($cids)." where term_taxonomy_id=".$term_taxonomy_id;
			mysql_query($sql_count,$db);

			$t[$term_taxonomy_id] = $cids;
		}
		
	}
	arr2files($t,$type,$type.'.inc');
}

function w($file, $content, $mode = 'w'){//write file
	$f = fopen($file, $mode);
	return fputs($f, $content);
}

function arr2files($array,$array_name, $filename = ''){//保存数组
	$filename = $filename === ''? C_DIR.$array_name : $filename;
	$str      = "<?\n";
	$str     .= "\${$array_name} = ";
	$str     .= var_export($array,1);
	$str     .= ";";
	return w($filename.'.php',$str);
}


function t($filename){
	$filename = str_replace(array('.gif','.GIF'),'.thumbnail.gif', $filename);
	$filename = str_replace(array('.jpg','.JPG'),'.thumbnail.jpg', $filename);
	$filename = str_replace(array('.png','.PNG'),'.thumbnail.png', $filename);
	return $filename;
}

function a2s($att_id)
{
	global $att,$s;
	$sql = "SELECT * FROM `".$s['sa_pre']."attachments` where attachmentid=".$att_id;
	$res = mysql_query($sql);
	$arr = mysql_fetch_array($res);
	$att_str = '';
	$filepath = $s['wp_url']."wp-content/uploads/".date("Y",$arr['dateline']).'/'.date("m",$arr['dateline']).'/'.$arr['attachmentid'].'_'.$arr['filename'];
	if($arr['isimage'])
	{
		$att_str = "<p>".(is_array($a)?"<b>图片附件:</b><br />":'')."<a rel=\"attachment wp-att-".$att[$arr['attachmentid']]."\" href=\"".$s['wp_url']."?attachment_id=".$att[$arr['attachmentid']]."\" title=\"".$arr['filename']."\"><img src=\"".t($filepath)."\" alt=\"".$arr['filename']."\" /></a></p>\n";
	}else{
		$att_str = "<p><b>附件:</b><a href=\"".$filepath."\" target=\"_blank\">".$arr['filename']."</a>(".$arr['filesize']." Byte)</p>\n";
	}
	return $att_str;
}

function m($msg,$url)
{
	//echo '<meta http-equiv="REFRESH" content="1;URL='.$url.'">';
	echo "<a href=\"$url\">{$msg},点击进行下一步!</a>";
}

function wp_create_thumbnail( $file, $max_side = 128, $effect = '' ) {

		// 1 = GIF, 2 = JPEG, 3 = PNG

	if ( file_exists( $file ) ) {
		$type = getimagesize( $file );

		// if the associated function doesn't exist - then it's not
		// handle. duh. i hope.

		if (!function_exists( 'imagegif' ) && $type[2] == 1 ) {
			//$error = __( 'Filetype not supported. Thumbnail not created.' );
			
		}
		elseif (!function_exists( 'imagejpeg' ) && $type[2] == 2 ) {
			//$error = __( 'Filetype not supported. Thumbnail not created.' );
		}
		elseif (!function_exists( 'imagepng' ) && $type[2] == 3 ) {
			//$error = __( 'Filetype not supported. Thumbnail not created.' );
		} else {

			// create the initial copy from the original file
			if ( $type[2] == 1 ) {
				$image = imagecreatefromgif( $file );
			}
			elseif ( $type[2] == 2 ) {
				$image = imagecreatefromjpeg( $file );
			}
			elseif ( $type[2] == 3 ) {
				$image = imagecreatefrompng( $file );
			}

			if ( function_exists( 'imageantialias' ))
				imageantialias( $image, TRUE );

			$image_attr = getimagesize( $file );

			// figure out the longest side

			if ( $image_attr[0] > $image_attr[1] ) {
				$image_width = $image_attr[0];
				$image_height = $image_attr[1];
				$image_new_width = $max_side;

				$image_ratio = $image_width / $image_new_width;
				$image_new_height = $image_height / $image_ratio;
				//width is > height
			} else {
				$image_width = $image_attr[0];
				$image_height = $image_attr[1];
				$image_new_height = $max_side;

				$image_ratio = $image_height / $image_new_height;
				$image_new_width = $image_width / $image_ratio;
				//height > width
			}

			$thumbnail = imagecreatetruecolor( $image_new_width, $image_new_height);
			@ imagecopyresampled( $thumbnail, $image, 0, 0, 0, 0, $image_new_width, $image_new_height, $image_attr[0], $image_attr[1] );

			// If no filters change the filename, we'll do a default transformation.
			//if ( basename( $file ) == $thumb = apply_filters( 'thumbnail_filename', basename( $file ) ) )
			$thumb = preg_replace( '!(\.[^.]+)?$!', '.thumbnail' . '$1', basename( $file ), 1 );

			$thumbpath = str_replace( basename( $file ), $thumb, $file );

			// move the thumbnail to its final destination
			if ( $type[2] == 1 ) {
				if (!imagegif( $thumbnail, $thumbpath ) ) {
					//$error = __( "Thumbnail path invalid" );
				}
			}
			elseif ( $type[2] == 2 ) {
				if (!imagejpeg( $thumbnail, $thumbpath ) ) {
					//$error = __( "Thumbnail path invalid" );
				}
			}
			elseif ( $type[2] == 3 ) {
				if (!imagepng( $thumbnail, $thumbpath ) ) {
					//$error = __( "Thumbnail path invalid" );
				}
			}

		}
	} else {
//		$error = __( 'File not found' );
	}

//	if (!empty ( $error ) ) {
//		return $error;
//	} else {
//		return apply_filters( 'wp_create_thumbnail', $thumbpath );
//	}
}
ini_set('date.timezone','Asia/Shanghai');


function title(){
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Sablog-X 2.0 20100301 ->WordPress 3.5.1 数据转移程序 Code By Neeao</title>
</head>
<body>
Sablog-X 2.0 20100301 ->WordPress 3.5.1 数据转移程序 code by Neeao 
<hr>
<?php
}	
?>
<hr>
<a href="Http://neeao.com">Neeao.com</a>
</body>
</html>
