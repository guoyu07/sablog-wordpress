<?php
/**
 * Sablog-X 1.6 ->WordPress 2.2.3 动数据转移程序
 *
 * 说明:本程序用来实现Sablog-X至WordPress数据转移.
 *
 * author:maker<m4ker@163.com>
 * QQ:35650697
 *
 * updated : 2008-4-22
 */
#########################################################
$s['hostname'] = 'localhost';
$s['username'] = 'root';
$s['password'] = '';
$s['dbname']   = 's-w';
$s['sa_pre']   = 'sablog_';
$s['wp_pre']   = 'wp_';
$s['sa_dir']   = 'sa/';
$s['wp_dir']   = 'wp/';
$s['wp_url']   = 'http://www.m4ker.net/';
#########################################################
#$s['web_root'] = 'D:\\wwwroot\\maker\\wwwroot\\';
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title></title>
</head>
<body>
<!--
-----------------------------
1.移动附件

2.生成缩略图
    thmub....ext
3.导入分类
	categories -> category

4.导入附件
	attachments -> posts
	            -> postmeta

5.导入文章
	articles -> posts
	         -> post2cat

6.修改附件所属文章
	posts.parent_id

  修改文章guid
	post.guid

7.导入评论
	comments -> comment

8.导入链接
	links -> link
	      -> link2cat

#导入统计
#导入设置
	setting -> option
						blogname
						blogdescription

------------------------------
-->
<?
$s['wp_root']  = $s['web_root'].$s['wp_dir'];

$c = $att = $art = $ud = $attinfo = array();

/**
 *Db Connect
 */
if(!mysql_connect ( $s['hostname'], $s['username'], $s['password'])){
	echo "数据库连接错误,请确认配置信息.";
}
mysql_select_db($s['dbname']);
mysql_query('set names "utf8"');

if($_GET['step'] == '1' OR !$_GET['step']){
	/**
	 * 移动附件
	 */
	$imgs = array();
	$sql = "SELECT * 
			FROM `".$s['sa_pre']."attachments` 
			ORDER BY `attachmentid` DESC";
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
		$new = $s['wp_dir'].'wp-content/uploads/'.date("Y",$a['dateline']).'/'.date("m",$a['dateline']).'/'.$a['attachmentid'].'_'.mb_convert_encoding($a['filename'], "gbk", "utf-8");
		
		if(!rename('sa/attachments'.$a['filepath'],$new)){
			echo '<b>ERROR:</b>'.$a['filepath'].' --> '.$new."<br>\n";
		}else{
			$type = getimagesize($new);
			if(in_array($type[2],array(1,2,3))){
				$imgs[] = $new;
			}
		}

	}
	arr2file('imgs', 'imgs.inc');
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

	$sql = "SELECT * 
			FROM `".$s['sa_pre']."categories` 
			ORDER BY `displayorder` DESC";

	$result = mysql_query($sql);
	while($category = mysql_fetch_array($result)){

		$sql = "INSERT INTO `wp_categories` (
					`cat_ID` ,
					`cat_name` ,
					`category_nicename` ,
					`category_description` ,
					`category_parent` ,
					`category_count` ,
					`link_count` ,
					`posts_private` ,
					`links_private`
				)VALUES (
					NULL ,
					'".$category['name']."',
					'".$category['name']."',
					' ',
					'0',
					'".$category['articles']."',
					'0',
					'0',
					'0'
				);";
		
		if(mysql_query($sql)){
			echo $category['name']." 导入成功<br>";
		}
		$c[$category['cid']] = mysql_insert_id();
	}
	arr2file('c','c.inc');
	m("分类数据导入完毕","?step=3");
}


if($_GET['step'] == '3'){
	/**
	 *导入附件
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
					`post_category` ,
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
					'0',
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
	arr2file('att','att.inc');
	m("附件数据导入完毕","?step=4");
}
	if($_GET['step'] == '4'){
	/** 
	 * 移动文章
	 */
	include_once "att.inc.php";
	include_once "c.inc.php";
	$sql = "SELECT * 
			FROM `".$s['sa_pre']."articles` 
			ORDER BY `articleid` DESC";
	$res = mysql_query($sql);
	while($a = mysql_fetch_array($res)){
		#articles -> posts
		#         -> post2cat
		$atta = unserialize($a['attachments']);


		if($atta){
			foreach($atta as $k=>$v){
				$attinfo[$v['attachmentid']] = array_merge($v,$attinfo);
			}
			#arr2file('attinfo','attinfo.inc');
			$a['content'] = preg_replace("/\[attach=(\d+)\]/ie", "a2s('\\1')", $a['content']);

			foreach($atta as $k=>$v){
				if(!$ud[$v['attachmentid']]){
					$a['content'] .= a2s($v);
				}
			}
		}

		$sql = "INSERT INTO `wp_posts` (
					`ID` ,
					`post_author` ,
					`post_date` ,
					`post_date_gmt` ,
					`post_content` ,
					`post_title` ,
					`post_category` ,
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
					'".$a['content']."',
					'".$a['title']."',
					'0',
					'',
					'publish',
					'open',
					'open',
					'',
					'".urlencode($a['title'])."',
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

		mysql_query($sql);
		$art[$a['articleid']] = mysql_insert_id();

		$sql = "INSERT INTO `wp_post2cat` (
					`rel_id` ,
					`post_id` ,
					`category_id`
				)
				VALUES (
					NULL ,
					'".mysql_insert_id()."',
					'".$c[$a['cid']]."'
				)";

		mysql_query($sql);
	}

	arr2file('art','art.inc');

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
		#echo $sql."<br>";
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
	m("评论导入完毕","?step=7");
}


if($_GET['step'] == '7'){
	
	$sql = "SELECT * 
			FROM `".$s['sa_pre']."links` 
			WHERE `visible`=1 
			ORDER BY `displayorder` ASC";
	
	$res = mysql_query($sql);
	while($link = mysql_fetch_array($res)){
	
		$sql = "INSERT INTO `wp_links` (
					`link_id` ,
					`link_url` ,
					`link_name` ,
					`link_image` ,
					`link_target` ,
					`link_category` ,
					`link_description` ,
					`link_visible` ,
					`link_owner` ,
					`link_rating` ,
					`link_updated` ,
					`link_rel` ,
					`link_notes` ,
					`link_rss`
				)VALUES (
					NULL ,
					'".$link['url']."',
					'".addslashes($link['name'])."',
					'',
					'',
					'0',
					'".addslashes($link['note'])."',
					'Y',
					'1',
					'0',
					'0000-00-00 00:00:00',
					'',
					'',
					''
				);";

		if(!mysql_query($sql)){echo $sql."<br>";}
		$sql1 = "INSERT INTO `wp_link2cat` (
					`rel_id` ,
					`link_id` ,
					`category_id`
				)VALUES (
					NULL ,
					'".mysql_insert_id()."',
					'2'
				);";
		mysql_query($sql1);
	}
	m("数据转移完毕","#");
}



function w($file, $content, $mode = 'w'){//write file
	$f = fopen($file, $mode);
	return fputs($f, $content);
}

function arr2file($array_name, $filename = ''){//保存数组
	$filename = $filename === ''? C_DIR.$array_name : $filename;
	$array    = $GLOBALS[$array_name];
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

function a2s($a)
{
	global $att,$ud,$attinfo,$s;
	$att_str = '';
	if(is_array($a)){
		$arr = $a;
	}else{
		$arr = $attinfo[$a];
	}
	$filepath = $s['wp_url']."wp-content/uploads/".date("Y",$arr['dateline']).'/'.date("m",$arr['dateline']).'/'.$arr['attachmentid'].'_'.$arr['filename'];
	#print_r($arr);
	if($arr['isimage'])
	{
		$att_str = "<p>".(is_array($a)?"<b>图片附件:</b><br />":'')."<a rel=\"attachment wp-att-".$att[$arr['attachmentid']]."\" href=\"".$s['wp_url']."?attachment_id=".$att[$arr['attachmentid']]."\" title=\"".$arr['filename']."\"><img src=\"".t($filepath)."\" alt=\"".$arr['filename']."\" /></a></p>\n";
	}else{
		$att_str = "<p><b>附件:</b><a href=\"".$filepath."\" target=\"_blank\">".$arr['filename']."</a>(".$arr['filesize']." Byte)</p>\n";
	}
	$ud[$arr['attachmentid']] = true;
	return $att_str;
}

function m($msg,$url)
{
	echo '<meta http-equiv="REFRESH" content="1;URL='.$url.'">';
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


?>
<hr>
<?=date("H:i:s")?>
</body>
</html>
