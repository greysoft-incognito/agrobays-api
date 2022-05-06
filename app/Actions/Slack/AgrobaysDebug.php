<?php

namespace App\Actions\Slack;

use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;
use Spatie\SlashCommand\Handlers\BaseHandler;
use Spatie\SlashCommand\Handlers\SignatureHandler;
use Spatie\SlashCommand\Attachment;

class AgrobaysDebug extends SignatureHandler
{
    protected $signature = "agrobays-debug {action? : This command accepts one of two arguments: on|off.}";
    protected $description = 'Toggle slack debug logs on or off. [Arguments: on|off]';

    public function canHandle(Request $request): bool
    {
        return str_is($request->command, 'agrobays-debug') && in_array($request->text, ['on', 'off']);
    }

    public function handle(Request $request): Response
    {
        $action = $this->getArgument('action');
        \Config::write('settings.slack_debug', $action === 'on');

        return $this->respondToSlack('')
            ->withAttachment(Attachment::create()
                ->setColor('good')
                ->setText("Slack debugs are now turned {$request->text}!")
            );
    }
}