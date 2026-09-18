<?php

return [

    'rules' => [
        'A' => [
            'unit' => 50,
            'offers' => [
                ['type' => 'multiprice', 'bundle_count' => 3, 'bundle_price' => 130, 'active' => false],
                ['type' => 'buyonegetone', 'bundle_count' => 2, 'active' => true],
            ],
        ],
        'B' => [
            'unit' => 30,
            'offers' => [
                ['type' => 'multiprice', 'bundle_count' => 2, 'bundle_price' => 45, 'active' => true],
            ],
        ],
        'C' => ['unit' => 20, 'offers' => []],
        'D' => ['unit' => 15, 'offers' => []],
    ],

];
