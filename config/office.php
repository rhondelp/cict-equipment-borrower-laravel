<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Equipment office contact
    |--------------------------------------------------------------------------
    |
    | Where someone is sent when the app cannot help them itself — a locked-out
    | account, an address they cannot remember registering with, equipment due
    | back today. These are read by the public pages rather than written into
    | the templates, because an address that stops working has to be
    | changeable without a deploy.
    |
    | Copy that sends people to a mailbox nobody reads is worse than copy that
    | sends them to the counter: leave OFFICE_EMAIL empty and the pages fall
    | back to "ask at the equipment room".
    |
    */

    'email' => env('OFFICE_EMAIL', 'quincyjane.oliver@nmsc.edu.ph'),

    // Shown on the landing page's visit panel. From design-reference/
    // Landing.dc.html — confirm it before relying on it.
    'location' => env('OFFICE_LOCATION', 'Equipment room, CICT building'),

    /*
    |--------------------------------------------------------------------------
    | Opening hours
    |--------------------------------------------------------------------------
    |
    | The one source for when the equipment room is open. Every page that
    | states the hours renders them through App\Support\OfficeHours, which also
    | answers "is it open today?" for the landing page's status pill — a
    | display string could not do that, which is why this is structured.
    |
    | `days` are ISO-8601 weekday numbers: 1 = Monday … 7 = Sunday.
    | Times are 24-hour HH:MM in the app timezone (config/app.php).
    |
    */

    'hours' => [
        'days' => [1, 2, 3, 4, 5],
        'open' => '08:00',
        'close' => '17:00',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default loan period
    |--------------------------------------------------------------------------
    |
    | Days between approval and the due date on the loan an approved request
    | creates (ItemRequestController::requestActions). The landing page quotes
    | the same value, so the two cannot drift apart.
    |
    */

    'loan_days' => 7,

];
