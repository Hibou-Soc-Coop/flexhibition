<?php

return[

    /*
    |--------------------------------------------------------------------------
    | Media Size Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file defines the media sizes used in the application.
    | Each size is defined with a width and height, which can be used for
    | image processing or display purposes.
    |
    */

    'sizes' => [
        'image' => [
            'max' => 2048,
        ],
        'audio' => [
            'max' => 4096,
        ],
    ],
    'dimensions' => [
        'image' => [
            'width' => 1200,
            'height' => 1536,
        ],
        'gallery' => [
            'width' => 1920,
            'height' => 1080,
        ],
    ],
    'types' => [
        'image' => 'jpeg,jpg,png,gif',
        'audio' => 'mp3',
    ],
];
