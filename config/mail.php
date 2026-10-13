<?php

return [
    'default' => 'log',
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'scheme' => null,
            'url' => null,
            'host' => '',
            'port' => 587,
            'username' => null,
            'password' => null,
            'timeout' => 20,
            'local_domain' => null,
        ],
        'log' => [
            'transport' => 'log',
            'channel' => null,
        ],
        'array' => [
            'transport' => 'array',
        ],
    ],
    'from' => [
        'address' => 'hello@example.com',
        'name' => 'Clarity',
    ],
];
