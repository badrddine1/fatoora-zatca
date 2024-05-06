<?php

return [

    /**
     * Zatca Phase 2
     *
     * we will consider some config data for zatca v2 in this file.
     * the mode of zatca app is by default the same as app environment.
     */
    'portals'       => [
        'local'         => env('ZATCA_LOCAL', 'https://gw-apic-gov.gazt.gov.sa/e-invoicing/developer-portal'),
        'simulation'    => env('ZATCA_SIMULATION', 'https://gw-apic-gov.gazt.gov.sa/e-invoicing/simulation'),
        'production'    => env('ZATCA_PRODUCTION', 'https://gw-apic-gov.gazt.gov.sa/e-invoicing/production'),
    ],
    'app' => [
        'environment'   => env('ZATCA_ENVIRONMENT', env('APP_ENV', 'local')), # local|simulation|production
    ],

];
