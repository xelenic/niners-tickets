<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Raffle Name
    |--------------------------------------------------------------------------
    |
    | Shown at the top of the kiosk and the admin entries list.
    |
    */

    'name' => env('RAFFLE_NAME', 'Gift Vouchers – Raffle Draw'),

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Image file names inside the public folder, used by the kiosk and the
    | LED winners screen.
    |
    */

    'branding' => [
        'logo' => 'LOGO_NEW_glow.png',
        'logo_alt' => 'The Niners Grand Soirée – Honouring the Bond',
        'background' => 'Textured Navy Blue Stone Background.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Display Timezone
    |--------------------------------------------------------------------------
    |
    | Entry times are stored in the application timezone (UTC) and converted
    | to this timezone when shown in the admin entries list.
    |
    */

    'timezone' => env('RAFFLE_TIMEZONE', 'Asia/Colombo'),

];
