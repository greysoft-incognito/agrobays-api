<?php

namespace App\Actions\Slack;

use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;
use Spatie\SlashCommand\Handlers\BaseHandler;

class AgrobaysLogger extends BaseHandler
{
    protected $description = 'Toggle debug logs on or off for requests originating from Slack. This action accepts one of two parameters: {on|off}.';
    
    public function canHandle(Request $request): bool
    {
        return str_is($request->command, 'agrobays-logger') && in_array($request->text, ['on', 'off']);
    }

    public function handle(Request $request): Response
    {   
        $settings = \Settings::options(['settings' => 'settings']);
        $json = \Settings::fresh()->json()->options(['settings' => 'settings'])->get();
        $settings->saveConfigFile(['slack_logger' => ($request->text === 'on' ? 'true' : 'false')], $json);

        return $this->respondToSlack('')
            ->withAttachment(Attachment::create()
                ->setColor('good')
                ->setText("Slack request logs are now turned {$request->text}!")
    }
}