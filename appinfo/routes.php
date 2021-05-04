<?php

return [
	'resources' => [
		'listman' => ['url' => '/lists'],
		'member' => ['url' => '/members'],
		'listman_api' => ['url' => '/api/0.1/listman']
	],
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'listman_api#preflighted_cors', 'url' => '/api/0.1/{path}',
			'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']]
	]
];
