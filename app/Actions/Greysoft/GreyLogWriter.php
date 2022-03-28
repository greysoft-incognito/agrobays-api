<?php 
namespace App\Actions\Greysoft;

use \Spatie\HttpLogger\LogWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Spatie\SlackAlerts\Facades\SlackAlert;
/**
 * 
 */
class GreyLogWriter implements LogWriter
{
    public function logRequest(Request $request): void
    {
        $r = new Response;

        $method = strtoupper($request->getMethod());
        
        $uri = $request->getPathInfo();

        if ($uri !== '/slacker' || config('settings.slack_logger')) {
            $fullUrl = $request->fullUrl();
            $getHost = $request->getHost();
            $headers = json_encode($request->header(), JSON_UNESCAPED_SLASHES);
            $bodyAsJson  = json_encode($request->except(config('http-logger.except')));
            $status      = $r->statusText();
            $status_code = $r->status();

            $message = "
            *[$status_code $status]* \n 
            {$method} _{$uri}_ - `{$bodyAsJson}` > *$getHost* \n 
            *Request URL*: {$fullUrl} \n 
            *Headers:*
            ```$headers``` 
            ";

            // Log::channel(config('http-logger.log_channel'))->info($message);
            if (config('settings.slack_debug') === true) {
                SlackAlert::message($message);
            }
        }
    }
}