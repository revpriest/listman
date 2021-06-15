<?php

return [
	'resources' => [
		'listman' => ['url' => '/lists'],
		'member' => ['url' => '/members'],
		'message' => ['url' => '/messages'],
	],
	'routes' => [
		['name' => 'listman#messagesent', 'url' => '/message-sent/{mid}', 'verb' => 'POST'],
		['name' => 'listman#messagesend', 'url' => '/message-send/{mid}', 'verb' => 'POST'],
		['name' => 'listman#listdetails', 'url' => '/listdetails/{lid}', 'verb' => 'GET'],
		['name' => 'listman#listmembers', 'url' => '/listmembers/{lid}', 'verb' => 'GET'],
		['name' => 'listman#subscribe', 'url' => '/subscribe/{lid}', 'verb' => 'POST'],
		['name' => 'listman#confirm', 'url' => '/confirm/{lid}', 'verb' => 'GET'],
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'listman_api#preflighted_cors', 'url' => '/api/0.1/{path}',
			'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']]
	]
];
