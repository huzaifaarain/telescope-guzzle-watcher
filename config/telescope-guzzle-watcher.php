<?php

// config for MuhammadHuzaifa/TelescopeGuzzleWatcher
return [

    /*
    |--------------------------------------------------------------------------
    | Except Request Headers
    |--------------------------------------------------------------------------
    |
    | This value is used when you need to exclude the request headers from
    | being recorded under the telescope. You can exclude any number of
    | headers containing sensitive information
    |
    */

    'except_request_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Except Response Headers
    |--------------------------------------------------------------------------
    |
    | This value is used when you need to exclude the response headers from
    | being recorded under the telescope. You can exclude any number of
    | headers containing sensitive information
    |
    */

    'except_response_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Enable URI Tags
    |--------------------------------------------------------------------------
    |
    | This value is used for determining wether the watcher should parse the url
    | and add it's segments as telescope tags
    |
    |
    */

    'enable_uri_tags' => true,

    /*
    |--------------------------------------------------------------------------
    | Exclude words from URI tags
    |--------------------------------------------------------------------------
    |
    | This value is used when you need to exclude words or patterns that should
    | be excluded from the tags list
    |
    */

    'exclude_words_from_uri_tags' => [],

    /*
    |--------------------------------------------------------------------------
    | Content Size Limit
    |--------------------------------------------------------------------------
    |
    | This value is used when you need to limit the response content.
    | Default is 64.
    |
    */

    'size_limit' => null,
];
