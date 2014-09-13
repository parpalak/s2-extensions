<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 07.09.14
 * Time: 18:38
 */

namespace s2_extensions\s2_blog;

class Page_Main extends Page_Abstract
{
	public function body (array $params = array())
	{
		global $lang_s2_blog, $page;

		$s2_blog_skip = !empty($params['page']) ? (int) $params['page'] : 0;

		$this->template_id = $s2_blog_skip ? 'blog.php' : 'blog_main.php';
		$this->obtainTemplate(__DIR__.'../../templates/');

		if (strpos($this->template, '<!-- s2_blog_calendar -->') !== false)
			$page['s2_blog_calendar'] = Lib::calendar(date('Y'), date('m'), '0');

		$page = self::last_posts($s2_blog_skip) + $page;

		// Bread crumbs
		if (S2_BLOG_CRUMBS)
			$page['path'][] = S2_BLOG_CRUMBS;
		if (S2_BLOG_URL)
			$page['path'][] = $s2_blog_skip ? '<a href="'.S2_BLOG_PATH.'">'.$lang_s2_blog['Blog'].'</a>' : $lang_s2_blog['Blog'];

		if ($s2_blog_skip)
			$page['link_navigation']['up'] = S2_BLOG_PATH;
		elseif (S2_BLOG_URL && S2_BLOG_CRUMBS && preg_match('#href="(.*?)"#', S2_BLOG_CRUMBS, $s2_blog_matches))
			$page['link_navigation']['up'] = $s2_blog_matches[1];
	}

	private static function last_posts ($skip = 0)
	{
		global $lang_common;

		if ($skip < 0)
			$skip = 0;

		$posts_per_page = S2_MAX_ITEMS ? S2_MAX_ITEMS : 10;
		$posts = Lib::last_posts_array($posts_per_page, $skip, true);

		$output = '';
		$i = 0;
		foreach ($posts as $post)
		{
			$i++;
			if ($i > $posts_per_page)
				break;

			$output .= Lib::format_post(
				s2_htmlencode($post['author']),
				'<a href="'.S2_BLOG_PATH.date('Y/m/d/', $post['create_time']).urlencode($post['url']).'">'.s2_htmlencode($post['title']).'</a>',
				s2_date($post['create_time']),
				s2_date_time($post['create_time']),
				$post['text'],
				$post['tags'],
				$post['comments'],
				$post['favorite']
			);
		}

		$paging = '';

		$link_nav = array();
		if ($skip > 0)
		{
			$link_nav['prev'] = S2_BLOG_PATH.($skip > $posts_per_page ? 'skip/'.($skip - $posts_per_page) : '');
			$paging = '<a href="'.$link_nav['prev'].'">'.$lang_common['Here'].'</a> ';
		}
		if ($i > $posts_per_page)
		{
			$link_nav['next'] = S2_BLOG_PATH.'skip/'.($skip + $posts_per_page);
			$paging .= '<a href="'.$link_nav['next'].'">'.$lang_common['There'].'</a>';
		}

		if ($paging)
			$output .= '<p class="s2_blog_pages">'.$paging.'</p>';

		return array('text' => $output, 'link_navigation' => $link_nav);
	}
}
