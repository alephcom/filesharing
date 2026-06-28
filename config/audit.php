<?php

return [
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),

    'export_default_format' => env('AUDIT_EXPORT_DEFAULT_FORMAT', 'csv'),
];
