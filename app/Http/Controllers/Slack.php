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
        
        $hash = 'v0='.hash_hmac('sha256', $sig_basestring, env('453712b724e4d873f664725026312706'));
        if (time() - $timestamp > 60*5 || !hash_equals($hash, $signature)) {
            return response("Invalid Request.", 200)->header('Content-Type', 'application/json');
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
        } elseif (in_array($request->user_id, $this->uids)) {
            $settings = \Settings::options(['settings' => 'settings']);
            $json = \Settings::fresh()->json()->options(['settings' => 'settings'])->get();
            $settings->saveConfigFile(['slack_debug' => ($request->text === 'on' ? 'true' : 'false')], $json);

            $msg = "Slack debugs are now turned {$request->text}!";
        } else {
            $msg = 'Sorry, you do not have permision to perform this action!';
        }

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
