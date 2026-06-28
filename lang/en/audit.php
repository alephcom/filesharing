<?php

return [
    'title' => 'Audit log',
    'navigation_label' => 'Audit log',

    'export' => 'Export',
    'export_success' => 'Audit log exported',

    'columns' => [
        'event' => 'Event',
        'bundle' => 'Bundle',
        'actor' => 'Actor',
        'recipient' => 'Recipient',
        'ip' => 'IP',
        'created_at' => 'When',
    ],

    'filters' => [
        'event_type' => 'Event type',
        'bundle' => 'Bundle',
        'actor' => 'User',
        'from' => 'From',
        'to' => 'To',
    ],

    'export_form' => [
        'from' => 'From date',
        'to' => 'To date',
        'format' => 'Format',
    ],
];
