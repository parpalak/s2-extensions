/** * Adds functions to the admin panel * * @copyright (C) 2011 Roman Parpalak * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher * @package s2_search */var s2_search = {	reindex_query: function ()	{		GETAsyncRequest(sUrl + 'action=s2_search_makeindex', s2_search.reindex_callback);	},	reindex: function ()	{		s2_search.reindex_query();		document.getElementById('s2_search_progress').innerHTML = ': 0%...';		return false;	},	reindex_callback: function ()	{		if (xmlhttp_async.readyState != 4)			return;		var response = xmlhttp_async.responseText;		if (response.indexOf('go_') == 0)		{			setTimeout(s2_search.reindex_query, 50);			document.getElementById('s2_search_progress').innerHTML = ': ' + response.substr(3) + '%...';		}		else			document.getElementById('s2_search_progress').innerHTML = ': 100%';	},	refresh_index: function (sChapter)	{		GETAsyncRequest(sUrl + 'action=s2_search_makeindex&chapter=' + encodeURIComponent(sChapter), s2_search.refresh_callback);		window.status = '...';	},	refresh_callback: function ()	{		if (xmlhttp_async.readyState != 4)			return;		window.status = window.defaultStatus;	}}