<?php

return [
	'resources' => [
		'listman' => ['url' => '/lists'],
		'member' => ['url' => '/members'],
		'message' => ['url' => '/messages'],
	],
	'routes' => [
		['name' => 'listman#settings',      'url' => '/settings',           'verb' => 'POST'],
		['name' => 'listman#messageview',   'url' => '/message-view/{rid}', 'verb' => 'GET' ],
		['name' => 'listman#messageinc',    'url' => '/message-inc/{rid}', 'verb' => 'GET' ],
		['name' => 'listman#messagewidget', 'url' => '/message-widget/{rid}',  'verb' => 'GET' ],
		['name' => 'listman#messagestat',   'url' => '/message-stats/{rid}',  'verb' => 'GET' ],
		['name' => 'listman#messagetext',   'url' => '/message-text/{rid}', 'verb' => 'GET' ],
		['name' => 'listman#messagemd'  ,   'url' => '/message-md/{rid}', 'verb' => 'GET' ],
		['name' => 'listman#messagesent',   'url' => '/message-sent/{mid}', 'verb' => 'POST'],
		['name' => 'listman#messagesend',   'url' => '/message-send/{mid}', 'verb' => 'POST'],
		['name' => 'listman#listdetails',   'url' => '/listdetails/{lid}',  'verb' => 'GET' ],
		['name' => 'listman#listmembers',   'url' => '/listmembers/{lid}',  'verb' => 'GET' ],
		['name' => 'listman#subscribePost', 'url' => '/subscribe/{lid}',    'verb' => 'POST'],
		['name' => 'listman#subscribe',     'url' => '/subscribe/{lid}',    'verb' => 'GET' ],
		['name' => 'listman#confirm',       'url' => '/confirm/{lid}',      'verb' => 'GET' ],
		['name' => 'listman#confirmPost',   'url' => '/confirm/{lid}',      'verb' => 'POST' ],
		['name' => 'page#index',            'url' => '/', 'verb' => 'GET'],
		['name' => 'listman_api#preflighted_cors', 'url' => '/api/0.1/{path}',
			'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']]
	]
];
