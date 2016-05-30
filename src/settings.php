<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
        ],

        // Database connection
        'db' => [
            "host" => "localhost",
            "db" => "gantt-rest-php",
            "user"=> "root",
            "pwd"=> ""
        ]
    ],
];
