<?php

return [
    /*
     * Your EasyPost API key.
     * Set EASYPOST_API_KEY in docker-compose.yml (or .env for local dev).
     * Test keys begin with "EZAK" – use them during development.
     * Production keys begin with "EZAKprod".
     */
    'api_key' => env('EASYPOST_API_KEY'),

    /*
     * Label format returned by EasyPost.
     * Supported: 'PDF', 'PNG', 'ZPL'
     */
    'label_format' => env('EASYPOST_LABEL_FORMAT', 'PDF'),
];
