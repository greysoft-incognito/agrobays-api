<?php

namespace App\Actions\Slack;

use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;
use Spatie\SlashCommand\Handlers\BaseHandler;

class AgrobaysDebug extends BaseHandler
{
    protected $description = 'Toggle slack debug logs on or off. This action accepts one of two parameters: {on|off}.';
    
    public function canHandle(Request $request): bool
    {
        return str_is($request->command, 'agrobays-debug') && in_array($request->text, ['on', 'off']);
    }

    public function handle(Request $request): Response
    {   
        $settings = \Settings::options(['settings' => 'settings']);
        $json = \Settings::fresh()->json()->options(['settings' => 'settings'])->get();
        $settings->saveConfigFile(['slack_debug' => ($request->text === 'on' ? 'true' : 'false')], $json);

        return $this->respondToSlack('')
            ->withAttachment(Attachment::create()
                ->setColor('good')
                ->setText("Slack debugs are now turned {$request->text}!")
            );
    }
}