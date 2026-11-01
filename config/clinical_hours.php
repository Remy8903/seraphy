<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Target Hours
    |--------------------------------------------------------------------------
    |
    | The number of clinical hours each student must complete for every
    | hours type before graduating. Keys match the HoursType enum values.
    |
    */

    'targets' => [
        'observation' => 100,
        'hands_on' => 250,
    ],

    /*
    |--------------------------------------------------------------------------
    | Weekly Reminder
    |--------------------------------------------------------------------------
    |
    | When students are emailed a reminder to fill in their log form. The
    | day uses 0 (Sunday) to 6 (Saturday) and the time is interpreted in
    | the given timezone.
    |
    */

    'reminder' => [
        'day' => 0,
        'time' => '20:00',
        'timezone' => 'Asia/Kuala_Lumpur',
    ],

];
