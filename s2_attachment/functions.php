<?php
/**
 * Functions for the attachment extension
 *
 * @copyright (C) 2010 Roman Parpalak
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package s2_attachment
 */

//
// Interface functions
//
function s2_attachment_list ($id)
{
	global $s2_db, $lang_s2_attachment;

	$query = array(
		'SELECT'	=> 'id, name, filename, size, time, article_id, is_picture',
		'FROM'		=> 's2_attachment_files',
		'WHERE'		=> 'article_id = '.$id
	);
	($hook = s2_hook('fn_s2_attachment_list_pre_qr')) ? eval($hook) : null;
	$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);

	$list_files = $list_pictures = '';
	while ($row = $s2_db->fetch_assoc($result))
	{
		$buttons = array(
			'edit'		=> '<img onclick="return s2_attachment_rename_file('.$row['id'].', \''.$lang_s2_attachment['New filename'].'\', \''.str_replace('\'', '\\\'', rawurlencode($row['name'])).'\');" class="rename" src="i/1.gif" alt="'.$lang_s2_attachment['Rename'].'">', 
			'delete'	=> '<img onclick="return s2_attachment_delete_file('.$row['id'].', \''.str_replace('\'', '\\\'', rawurlencode(sprintf($lang_s2_attachment['Confirm delete'], $row['name'], $row['filename'], s2_frendly_filesize($row['size'])))).'\');" class="delete" src="i/1.gif" alt="'.$lang_s2_attachment['Delete'].'">', 
		);

		$item = '<li><div class="buttons">'.implode('', $buttons).'</div><a href="'.S2_PATH.'/'.S2_IMG_DIR.'/'.date('Y', $row['time']).'/'.$row['article_id'].'/'.$row['filename'].'" target="_blank" title="'.$row['filename'].', '.s2_frendly_filesize($row['size']).'">'.s2_htmlencode($row['name'] ? $row['name'] : '<'.$row['filename'].'>').'</a></li>';

		if ($row['is_picture'])
			$list_pictures .= $item;
		else
			$list_files .= $item;
	}

	$lists = array();
	if ($list_pictures)
		$lists[] = '<ul>'.$list_pictures.'</ul>';
	if ($list_files)
		$lists[] = '<ul>'.$list_files.'</ul>';

	return implode('<hr />', $lists);
}

function s2_attachment_add_col ($id)
{
	global $lang_s2_attachment, $lang_pictures;

?>
	<div class="r-float" id="s2_attachment_col">
		<form target="s2_attachment_result" enctype="multipart/form-data" action="<?php echo S2_PATH; ?>/_admin/pict_ajax.php?action=s2_attachment_upload" method="post" onsubmit="s2_attachment_upload_submit(this);">
			<div id="s2_attachment_file_upload_input">
				<input name="pictures[]" multiple="true" min="1" max="999" size="9" type="file" />
			</div>
			<?php printf($lang_pictures['Upload limit'], s2_frendly_filesize(s2_return_bytes(ini_get('upload_max_filesize'))), s2_frendly_filesize(s2_return_bytes(ini_get('post_max_size')))); ?><br />
			<input type="submit" value="<?php echo $lang_pictures['Upload']; ?>" />
			<input type="hidden" name="id" value="<?php echo $id; ?>" />
		</form>
		<hr />
		<div class="text_wrapper" style="padding-bottom: 7.0em;">
			<div class="tags_list" id="s2_attachment_list"><?php echo s2_attachment_list($id); ?></div>
		</div>
	</div>
<?php

}

//
// Saving thumbnails
//
function s2_attachment_save_thumbnail ($filename, $save_to, $max_size = 100)
{
	$image_info = getimagesize($filename);

	switch ($image_info['mime'])
	{
		case 'image/gif':
			if (imagetypes() & IMG_GIF)
				$image = imagecreatefromgif($filename);
			else
				$error = 'GIF images are not supported';
			break;
		case 'image/jpeg':
			if (imagetypes() & IMG_JPG)
				$image = imagecreatefromjpeg($filename);
			else
				$error = 'JPEG images are not supported';
			break;
		case 'image/png':
			if (imagetypes() & IMG_PNG)
				$image = imagecreatefrompng($filename);
			else
				$error = 'PNG images are not supported';
			break;
		case 'image/wbmp':
			if (imagetypes() & IMG_WBMP)
				$image = imagecreatefromwbmp($filename);
			else
				$error = 'WBMP images are not supported';
			break;
		default:
			$error = $image_info['mime'].' images are not supported';
			break;
	}

	if (isset($error))
		return $error;

	$sx = imagesx($image);
	$sy = imagesy($image);
	$dx = $max_size;
	$dy = round($sy * $max_size / $sx);
/* 	if ($sx < $sy)
	{
		if ($sy > $max_size)
		{
			$dy = $max_size;
			$dx = round($dy * $sx / $sy); 
		}
		else
		{
			$dx = $sx;
			$dy = $sy;
		}
	}
	else
	{
		if ($sx > $max_size)
		{
			$dx = $max_size;
			$dy = round($dx * $sy / $sx); 
		}
		else
		{
			$dx = $sx;
			$dy = $sy;
		}
	} */

	$thumbnail = imagecreatetruecolor($dx, $dy);

	imagealphablending($thumbnail, false);
	imagesavealpha($thumbnail, true);
	$white = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
	imagefilledrectangle($thumbnail, 0, 0, $dx, $dy, $white);
	imagecolortransparent($thumbnail, $white);

	imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $dx, $dy, $sx, $sy);

	imagepng($thumbnail, $save_to);

	imagedestroy($image);
	imagedestroy($thumbnail);

	return false;
}

//
// Processes the placeholder
//
function s2_attachment_placeholder_content ($id)
{
	global $s2_db, $lang_s2_attachment;

	$query = array(
		'SELECT'	=> 'id, name, filename, size, time, is_picture',
		'FROM'		=> 's2_attachment_files',
		'WHERE'		=> 'article_id = '.$id
	);
	($hook = s2_hook('fn_s2_attachment_list_pre_qr')) ? eval($hook) : null;
	$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);

	$list_files = $list_pictures = '';
	while ($row = $s2_db->fetch_assoc($result))
	{
		if ($row['is_picture'])
		{
			$list_pictures .= '<a href="'.S2_PATH.'/'.S2_IMG_DIR.'/'.date('Y', $row['time']).'/'.$id.'/'.$row['filename'].'" class="highslide" onclick="return hs.expand(this)"><img src="'.S2_PATH.'/'.S2_IMG_DIR.'/'.date('Y', $row['time']).'/'.$id.'/micro/'.$row['filename'].'.png" alt="" /></a>';
			if ($row['name'])
				$list_pictures .= '<div class="highslide-caption">'.s2_htmlencode($row['name']).'</div>';
		}
		else
		{
			$list_files .= '<li><a href="'.S2_PATH.'/'.S2_IMG_DIR.'/'.date('Y', $row['time']).'/'.$id.'/'.$row['filename'].'">'.s2_htmlencode($row['name'] ? $row['name'] : $row['filename']).' ('.s2_frendly_filesize($row['size']).')</a></li>';
		}
	}

	if ($list_pictures)
		$list_pictures = '<div class="highslide-gallery">'.$list_pictures.'</div>';
	if ($list_files)
		$list_files = '<div class="s2_attachment_files"><h2>'.$lang_s2_attachment['Attached files'].'</h2><ul>'.$list_files.'</ul></div>';

	return array($list_files, $list_pictures);
}

define('S2_ATTACHMENT_FUNCTIONS_LOADED', 1);