<?php

return [
	'resources' => [
		'listman' => ['url' => '/lists'],
		'member' => ['url' => '/members'],
	],
	'routes' => [
		['name' => 'listman#listmembers', 'url' => '/listmembers/{lid}', 'verb' => 'GET'],
		['name' => 'listman#subscribe', 'url' => '/subscribe/{lid}', 'verb' => 'POST'],
		['name' => 'listman#confirm', 'url' => '/confirm/{lid}', 'verb' => 'GET'],
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'listman_api#preflighted_cors', 'url' => '/api/0.1/{path}',
			'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']]
	]
];
