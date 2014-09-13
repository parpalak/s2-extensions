<?php
/**
 * Blog posts for a year.
 *
 * @copyright (C) 2007-2014 Roman Parpalak
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package s2_blog
 */

namespace s2_extensions\s2_blog;


class Page_Year extends Page_Abstract
{
	public function body (array $params = array())
	{
		global $page, $lang_s2_blog;

		$this->obtainTemplate(__DIR__.'/../../templates/');

		if (strpos($this->template, '<!-- s2_blog_calendar -->') !== false)
			$page['s2_blog_calendar'] = Lib::calendar($params['year'], '', 0);

		$page['title'] = '';

		// Posts of a year
		$page = self::year_posts($params['year']) + $page;

		// Bread crumbs
		if (S2_BLOG_CRUMBS)
			$page['path'][] = S2_BLOG_CRUMBS;
		if (S2_BLOG_URL)
			$page['path'][] = '<a href="'.S2_BLOG_PATH.'">'.$lang_s2_blog['Blog'].'</a>';
		$page['path'][] = $params['year'];
	}

	private static function year_posts ($year)
	{
		global $s2_db, $lang_s2_blog;

		$start_time = mktime(0, 0, 0, 1, 1, $year);
		$end_time = mktime(0, 0, 0, 1, 1, $year + 1);

		$page['head_title'] = $page['title'] =  sprintf($lang_s2_blog['Year'], $year);

		$page['link_navigation']['up'] = S2_BLOG_PATH;
		if ($year > S2_START_YEAR)
		{
			$page['title'] = '<a href="'.S2_BLOG_PATH.($year - 1).'/">&larr;</a> '.$page['title'];
			$page['link_navigation']['prev'] = S2_BLOG_PATH.($year - 1).'/';
		}
		if ($year < date('Y'))
		{
			$page['title'] .= ' <a href="'.S2_BLOG_PATH.($year + 1).'/">&rarr;</a>';
			$page['link_navigation']['next'] = S2_BLOG_PATH.($year + 1).'/';
		}

		$query = array(
			'SELECT'	=> 'create_time',
			'FROM'		=> 's2_blog_posts',
			'WHERE'		=> 'create_time < '.$end_time.' AND create_time >= '.$start_time.' AND published = 1'
		);
		($hook = s2_hook('fn_s2_blog_year_posts_pre_get_days_qr')) ? eval($hook) : null;
		$result = $s2_db->query_build($query) or error(__FILE__, __LINE__);

		$day_flags = array_fill(1, 12, '');
		while ($row = $s2_db->fetch_row($result))
			$day_flags[(int) date('m', $row[0])][(int) date('j', $row[0])] = 1;

		$output = '<table class="yc" align="center"><tr>';
		for ($i = 1; $i <= 12; $i++)
		{
			$output .= '<td>'.Lib::calendar($year, Lib::extend_number($i), '-1', '', $day_flags[$i]).'</td>';
			if (!($i % 2) && ($i != 12))
				$output .= '</tr><tr>';
		}
		$output .= '</tr></table>';

		$page['text'] = $output;
		return $page;
	}

}