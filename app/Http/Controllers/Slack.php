<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\SlackAlerts\Facades\SlackAlert;

class Slack extends Controller
{
    protected $uids = [
        'U0320GQTFM2'
    ];

    public function index(Request $request, $action = 'debug')
    {
        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');
        $sig_basestring = 'v0:' . $timestamp . ':' . $request->getContent();
        
        $hash = 'v0='.hash_hmac('sha256', $sig_basestring, env('SLACK_SECRET'));

        if (time() - $timestamp > 60*5 || !hash_equals($hash, $signature)) {
            return $this->msg("Invalid Request.");
        }

        if (!in_array($request->user_id, $this->uids)) {
            return $this->msg("Sorry, you do not have permision to perform this action.");
        }

        return $this->{$action}($request);
    }

    /**
     * Enable or disable debugging
     * @return void
     */
    protected function debug(Request $request) 
    {
        if (!in_array($request->text, ['on', 'off'])) {
            $msg = "Invalid parameter.";
        } else {
            $settings = \Settings::options(['settings' => 'settings']);
            $json = \Settings::fresh()->json()->options(['settings' => 'settings'])->get();
            $settings->saveConfigFile(['slack_debug' => ($request->text === 'on' ? 'true' : 'false')], $json);

            $msg = "Slack debugs are now turned {$request->text}!";
        }

        return $this->msg($msg);
    }

    /**
     * Enable or disable debugging
     * @return void
     */
    protected function logger(Request $request) 
    {
        if (!in_array($request->text, ['on', 'off'])) {
            $msg = "Invalid parameter.";
        } else {
            $settings = \Settings::options(['settings' => 'settings']);
            $json = \Settings::fresh()->json()->options(['settings' => 'settings'])->get();
            $settings->saveConfigFile(['slack_logger' => ($request->text === 'on' ? 'true' : 'false')], $json);

            $msg = "Slack request logs are now turned {$request->text}!";
        }

        return $this->msg($msg);
    }

    protected function msg($msg) 
    {
        $request = request();

        if ($request->response_url) {
            $client = new \GuzzleHttp\Client(['base_uri' => $request->response_url]);
            $client->request('POST', '/', [
                'headers'     => ['Content-type' => 'application/json'],
                'form_params' => [
                    'text' => $msg,
                    'response_type' => 'ephemeral',
                ]
            ]);
            return response("OK", 200)->header('Content-Type', 'application/json');
        }

        return response(["response_type" => "ephemeral", "text" => $msg], 200)
                  ->header('Content-Type', 'application/json');
    }
}
