<?php
return [
    'host' => env('ELASTIC_HOST', 'http://elasticsearch:9200'),
    'index' => env('ELASTIC_INDEX', 'comments_v1'),
    'alias' => env('ELASTIC_ALIAS', 'comments'),
];
