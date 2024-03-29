<?php

return [

    /*
     * The log profile which determines whether a request should be logged.
     * It should implement `LogProfile`.
     */
    'log_profile' => \Spatie\HttpLogger\LogNonGetRequests::class,

    /*
     * The log writer used to write the request to a log.
     * It should implement `LogWriter`.
     */
    // 'log_writer' => \Spatie\HttpLogger\DefaultLogWriter::class,
    'log_writer' => App\Actions\Greysoft\GreyLogWriter::class,

    /*
    * The log channel used to write the request.
    */
    'log_channel' => env('LOG_CHANNEL', 'stack'),

    /*
     * Filter out body fields which will never be logged.
     */
    'except' => [
        'password',
        'token',
        'password_confirmation',
    ],

];
