<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Equipment office contact
    |--------------------------------------------------------------------------
    |
    | Where someone is sent when the app cannot help them itself — a locked-out
    | account, an address they cannot remember registering with, equipment due
    | back today. These are read by the forgot-password screen rather than
    | written into the template, because an address that stops working has to be
    | changeable without a deploy.
    |
    | The default address comes from design-reference/Forgot Password.dc.html.
    | Point OFFICE_EMAIL at the real inbox before this reaches anyone: copy that
    | sends people to a mailbox nobody reads is worse than copy that sends them
    | to the counter.
    |
    */

    'email' => env('OFFICE_EMAIL', 'cict.equipment@nmsc.edu.ph'),

    'hours' => env('OFFICE_HOURS', '8:00 AM – 5:00 PM, Monday to Saturday'),

];
