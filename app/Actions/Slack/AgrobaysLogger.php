<?php

namespace App\Actions\Slack;

use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;
use Spatie\SlashCommand\Handlers\BaseHandler;
use Spatie\SlashCommand\Handlers\SignatureHandler;
use Spatie\SlashCommand\Attachment;

class AgrobaysLogger extends SignatureHandler
{
    protected $signature = "agrobays-logger {action? : This command accepts one of two parameters: {on|off}.}";
    protected $description = 'Toggle debug logs on or off for requests originating from Slack. [Arguments: on|off]';

    public function canHandle(Request $request): bool
    {
        return in_array($request->text, ['on', 'off']);
    }

    public function handle(Request $request): Response
    {   
        $action = $this->getArgument('action');
        $settings = \Settings::options(['settings' => 'settings']);
        $json = \Settings::fresh()->json()->options(['settings' => 'settings'])->get();
        $settings->saveConfigFile(['slack_logger' => ($action === 'on' ? 'true' : 'false')], $json);

        return $this->respondToSlack('')
            ->withAttachment(Attachment::create()
                ->setColor('good')
                ->setText("Slack request logs are now turned {$request->text}!")
            );
    }
}