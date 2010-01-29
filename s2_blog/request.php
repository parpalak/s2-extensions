<?php
/**
 * Processes ajax queries for blog administrating.
 *
 * @copyright (C) 2007-2010 Roman Parpalak
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package s2_blog
 */

if ($action == 'load_blog_posts')
{
	$required_rights = array('view');
	($hook = s2_hook('blrq_action_load_blog_posts_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	require $ext_info['path'].'/lang/'.S2_LANGUAGE.'.php';
	require $ext_info['path'].'/blog_lib.php';

	s2_blog_output_post_list($_POST['posts']);
}

elseif ($action == 'edit_blog_post')
{
	$required_rights = array('view');
	($hook = s2_hook('blrq_action_edit_blog_post_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	if (!isset($_GET['id']))
		die('Error in GET parameters.');
	$id = (int) $_GET['id'];

	require $ext_info['path'].'/lang/'.S2_LANGUAGE.'.php';
	require $ext_info['path'].'/blog_lib.php';

	($hook = s2_hook('blrq_action_edit_blog_post_pre_output')) ? eval($hook) : null;

	s2_blog_edit_post_form($id);
}

// Saving data to DB
elseif ($action == 'save_blog')
{
	$required_rights = array('edit_s2_blog');
	($hook = s2_hook('rq_action_save_blog_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	require $ext_info['path'].'/lang/'.S2_LANGUAGE.'.php';
	require $ext_info['path'].'/blog_lib.php';

	if (!isset($_POST['page']))
		die('Error in POST parameters.');
	$page = $_POST['page'];

	$favorite = (int) isset($_POST['flags']['favorite']);
	$published = (int) isset($_POST['flags']['published']);
	$commented = (int) isset($_POST['flags']['commented']);

	$create_time = isset($_POST['cr_time']) ? s2_time_from_array($_POST['cr_time']) : time();
	$modify_time = isset($_POST['m_time']) ? s2_time_from_array($_POST['m_time']) : time();
	$id = (int) $page['id'];

	$label = $s2_db->escape($page['new_label'] ? $page['new_label'] : $page['label']);

	$error = false;

	$query = array(
		'UPDATE'	=> 's2_blog_posts',
		'SET'		=> "title = '".$s2_db->escape($page['title'])."', text = '".$s2_db->escape($page['text'])."', url = '".$s2_db->escape($page['url'])."', published = $published, favorite = $favorite, commented = $commented, create_time = $create_time, modify_time = $modify_time, label = '$label'",
		'WHERE'		=> 'id = '.$id
	);
	($hook = s2_hook('blrq_action_save_blog_pre_post_upd_qr')) ? eval($hook) : null;
	$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);
	if ($s2_db->affected_rows() == -1)
		$error = true;

	// Dealing with tags

	$new_tags = isset($_POST['keywords']) ? $_POST['keywords'] : '|';

	$query = array(
		'SELECT'	=> 'tag_id',
		'FROM'		=> 's2_blog_post_tag',
		'WHERE'		=> 'post_id = '.$id,
		'ORDER BY'	=> 'id'
	);
	($hook = s2_hook('blrq_action_save_blog_pre_get_tags_qr')) ? eval($hook) : null;
	$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);
	$old_tags = '|';
	while ($row = $s2_db->fetch_row($result))
		$old_tags .= $row[0].'|';

	// Compare old and new tags
	if ($old_tags != $new_tags)
	{
		// Deleting old links
		$query = array(
			'DELETE'	=> 's2_blog_post_tag',
			'WHERE'		=> 'post_id = '.$id
		);
		($hook = s2_hook('blrq_action_save_blog_pre_del_tags_qr')) ? eval($hook) : null;
		$s2_db->query_build($query) or error(__FILE__, __LINE__);
		if ($s2_db->affected_rows() == -1)
			$error = true;

		// Inserting new links
		if ($new_tags != '' && $new_tags != '|')
		{
			foreach (explode('|', substr($new_tags, 1, -1)) as $tag_id)
			{
				$tag_id = (int) $tag_id;
				if (!$tag_id)
					continue;

				$query = array(
					'INSERT'	=> 'post_id, tag_id',
					'INTO'		=> 's2_blog_post_tag',
					'VALUES'	=> $id.', '.$tag_id
				);
				($hook = s2_hook('blrq_action_save_blog_pre_ins_tags_qr')) ? eval($hook) : null;
				$s2_db->query_build($query) or error(__FILE__, __LINE__);
				if ($s2_db->affected_rows() == -1)
					$error = true;
			}
		}
	}

	if ($error)
		echo $lang_admin['Not saved correct'];
}

elseif ($action == 'create_blog_post')
{
	$required_rights = array('edit_s2_blog');
	($hook = s2_hook('blrq_action_create_blog_post_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	$now = time();

	$query = array(
		'INSERT'	=> 'create_time, modify_time, title, text, published',
		'INTO'		=> 's2_blog_posts',
		'VALUES'	=> $now.', '.$now.', \''.$lang_admin['New page'].'\', \'\', 0'
	);
	($hook = s2_hook('blrq_action_create_blog_post_pre_ins_qr')) ? eval($hook) : null;
	$s2_db->query_build($query) or error(__FILE__, __LINE__);

	require $ext_info['path'].'/lang/'.S2_LANGUAGE.'.php';
	require $ext_info['path'].'/blog_lib.php';

	s2_blog_edit_post_form($s2_db->insert_id());
}

elseif ($action == 'delete_blog_post')
{
	$required_rights = array('edit_s2_blog');
	($hook = s2_hook('blrq_action_delete_blog_post_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	if (!isset($_GET['id']))
		die('Error in GET parameters.');
	$id = (int) $_GET['id'];

	$query = array(
		'DELETE'	=> 's2_blog_posts',
		'WHERE'		=> 'id = '.$id,
		'LIMIT'		=> '1'
	);
	($hook = s2_hook('blrq_action_delete_blog_post_pre_del_post_qr')) ? eval($hook) : null;
	$s2_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'DELETE'	=> 's2_blog_post_tag',
		'WHERE'		=> 'post_id = '.$id
	);
	($hook = s2_hook('blrq_action_delete_blog_post_pre_del_tags_qr')) ? eval($hook) : null;
	$s2_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'DELETE'	=> 's2_blog_comments',
		'WHERE'		=> 'post_id = '.$id
	);
	($hook = s2_hook('blrq_action_delete_blog_post_pre_del_comments_qr')) ? eval($hook) : null;
	$s2_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = s2_hook('blrq_action_delete_blog_post_end')) ? eval($hook) : null;
}

elseif ($action == 'flip_favorite_post')
{
	$required_rights = array('edit_s2_blog');
	($hook = s2_hook('blrq_action_edit_blog_post_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	if (!isset($_GET['id']))
		die('Error in GET parameters.');
	$id = (int)$_GET['id'];

	$query = array(
		'SELECT'	=> 'favorite',
		'FROM'		=> 's2_blog_posts',
		'WHERE'		=> 'id = '.$id,
	);
	($hook = s2_hook('blrq_action_edit_blog_post_pre_get_fav_qr')) ? eval($hook) : null;
	$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);

	$favorite = 1 - $s2_db->result($result);

	$query = array(
		'UPDATE'	=> 's2_blog_posts',
		'SET'		=> 'favorite = '.$favorite,
		'WHERE'		=> 'id = '.$id,
	);
	($hook = s2_hook('blrq_action_edit_blog_post_pre_set_fav_qr')) ? eval($hook) : null;
	$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);

	require $ext_info['path'].'/lang/'.S2_LANGUAGE.'.php';

	$button = $favorite ?
		'<img src="i/sy.png" height="16" width="16" alt="'.$lang_s2_blog['Undo favorite'].'" onclick="return ToggleFavBlog(this, '.$id.');">' :
		'<img src="i/sg.png" height="16" width="16" alt="'.$lang_s2_blog['Do favorite'].'" onclick="return ToggleFavBlog(this, '.$id.');">';

	echo $button;
}

//=======================[Blog comments]========================================

elseif ($action == 'load_blog_comments')
{
	$required_rights = array('view');
	($hook = s2_hook('blrq_action_edit_blog_post_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	if (!isset($_GET['id']))
		die('Error in GET parameters.');
	$id = (int)$_GET['id'];

	require $ext_info['path'].'/lang/'.S2_LANGUAGE.'.php';
	require $ext_info['path'].'/blog_lib.php';

	echo s2_blog_show_comments($id);
}

elseif ($action == 'delete_blog_comment')
{
	$required_rights = array('edit_comments');
	($hook = s2_hook('blrq_action_delete_blog_comment_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	if (!isset($_GET['id']))
		die('Error in GET parameters.');
	$id = (int) $_GET['id'];

	// Does the comment exist?
	// We need post id for displaying the other comments
	$query = array(
		'SELECT'	=> 'post_id',
		'FROM'		=> 's2_blog_comments',
		'WHERE'		=> 'id = '.$id
	);
	($hook = s2_hook('blrq_action_delete_blog_comment_pre_get_rid_qr')) ? eval($hook) : null;
	$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);
	if ($s2_db->num_rows($result) != 1)
		die('Comment not found!');

	list($post_id) = $s2_db->fetch_row($result);

	$query = array(
		'DELETE'	=> 's2_blog_comments',
		'WHERE'		=> 'id = '.$id
	);
	($hook = s2_hook('blrq_action_delete_blog_comment_pre_del_qr')) ? eval($hook) : null;
	$s2_db->query_build($query) or error(__FILE__, __LINE__);

	require $ext_info['path'].'/lang/'.S2_LANGUAGE.'.php';
	require $ext_info['path'].'/blog_lib.php';

	echo s2_blog_show_comments($post_id);
}

elseif ($action == 'edit_blog_comment')
{
	$required_rights = array('edit_comments');
	($hook = s2_hook('blrq_action_edit_blog_comment_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	if (!isset($_GET['id']))
		die('Error in GET parameters.');
	$id = (int) $_GET['id'];

	// Get comment
	$query = array(
		'SELECT'	=> 'id, nick, email, text, show_email, subscribed',
		'FROM'		=> 's2_blog_comments',
		'WHERE'		=> 'id = '.$id
	);
	($hook = s2_hook('blrq_action_edit_blog_comment_pre_get_rid_qr')) ? eval($hook) : null;
	$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);
	if ($s2_db->num_rows($result) != 1)
		die('Comment not found!');

	$comment = $s2_db->fetch_assoc($result);

	s2_output_comment_form($comment, 'blog');
}

elseif ($action == 'hide_blog_comment')
{
	$required_rights = array('hide_comments');
	($hook = s2_hook('blrq_action_hide_blog_comment_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	if (!isset($_GET['id']))
		die('Error in GET parameters.');
	$id = (int)$_GET['id'];

	// Does the comment exist?
	// We need post id for displaying comments.
	// Also we need the comment if the premoderation is turned on.
	$query = array(
		'SELECT'	=> 'post_id, sent, shown, nick, email, text',
		'FROM'		=> 's2_blog_comments',
		'WHERE'		=> 'id = '.$id
	);
	($hook = s2_hook('blrq_action_hide_blog_comment_pre_get_comment_qr')) ? eval($hook) : null;
	$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);
	if ($s2_db->num_rows($result) != 1)
		die('Comment not found!');

	$comment = $s2_db->fetch_assoc($result);

	$sent = 1;
	if (!$comment['shown'] && !$comment['sent'])
	{
		// Premoderation is enabled and we have to send the comment to be shown
		// to subscribed commentators
		if (!defined('S2_COMMENTS_FUNCTIONS_LOADED'))
			require S2_ROOT.'include/comments.php';
		require S2_ROOT.'lang/'.S2_LANGUAGE.'/comments.php';

		// Getting some info about the post commented
		$query = array(
			'SELECT'	=> 'title, create_time, url',
			'FROM'		=> 's2_blog_posts',
			'WHERE'		=> 'id = '.$comment['post_id'].' AND published = 1 AND commented = 1'
		);
		($hook = s2_hook('blrq_action_hide_blog_comment_pre_get_post_info_qr')) ? eval($hook) : null;
		$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);

		if ($post = $s2_db->fetch_assoc($result))
		{
			$link = S2_BASE_URL.'/'.urlencode(S2_BLOG_URL).date('/Y/m/d/', $post['create_time']).urlencode($post['url']);

			// Fetching receivers' names and adresses
			$query = array(
				'SELECT'	=> 'DISTINCT nick, email',
				'FROM'		=> 's2_blog_comments',
				'WHERE'		=> 'post_id = '.$comment['post_id'].' and subscribed = 1 and email <> \''.$s2_db->escape($comment['email']).'\''
			);
			($hook = s2_hook('blrq_action_hide_blog_comment_pre_get_receivers_qr')) ? eval($hook) : null;
			$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);

			while ($receiver = $s2_db->fetch_assoc($result))
				s2_mail_comment($receiver['nick'], $receiver['email'], $comment['text'], $post['title'], $link, $comment['nick']);
		}
		else
			$sent = 0;
	}

	// Toggle comment visibility
	$query = array(
		'UPDATE'	=> 's2_blog_comments',
		'SET'		=> 'shown = 1 - shown, sent = '.$sent,
		'WHERE'		=> 'id = '.$id
	);
	($hook = s2_hook('blrq_action_hide_blog_comment_pre_upd_qr')) ? eval($hook) : null;
	$s2_db->query_build($query) or error(__FILE__, __LINE__);

	require $ext_info['path'].'/lang/'.S2_LANGUAGE.'.php';
	require $ext_info['path'].'/blog_lib.php';

	echo s2_blog_show_comments($comment['post_id']);
}

elseif ($action == 'mark_blog_comment')
{
	$required_rights = array('edit_comments');
	($hook = s2_hook('blrq_action_mark_blog_comment_start')) ? eval($hook) : null;
	s2_test_user_rights($session_id, $required_rights);

	if (!isset($_GET['id']))
		die('Error in GET parameters.');
	$id = (int)$_GET['id'];

	// Does the comment exist?
	// We need post id for displaying comments
	$query = array(
		'SELECT'	=> 'post_id',
		'FROM'		=> 's2_blog_comments',
		'WHERE'		=> 'id = '.$id
	);
	($hook = s2_hook('blrq_action_mark_blog_comment_pre_get_rid_qr')) ? eval($hook) : null;
	$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);
	if ($s2_db->num_rows($result) != 1)
		die('Comment not found!');

	list($post_id) = $s2_db->fetch_row($result);

	// Mark comment
	$query = array(
		'UPDATE'	=> 's2_blog_comments',
		'SET'		=> 'good = 1 - good',
		'WHERE'		=> 'id = '.$id
	);
	($hook = s2_hook('blrq_action_mark_blog_comment_pre_upd_qr')) ? eval($hook) : null;
	$s2_db->query_build($query) or error(__FILE__, __LINE__);

	require $ext_info['path'].'/lang/'.S2_LANGUAGE.'.php';
	require $ext_info['path'].'/blog_lib.php';

	echo s2_blog_show_comments($post_id);
}
