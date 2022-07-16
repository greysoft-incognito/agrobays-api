<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Greysoft\Charts;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function charts($type = 'pie')
    {
        \Gate::authorize('usable', 'dashboard');

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  'success',
            'response_code' => 200,
            'charts' => [
                'pie' => (new Charts)->getPie('admin'),
                'bar' => (new Charts)->getBar('admin'),
                'transactions' => (new Charts)->totalTransactions('admin', 'month'),
                'customers' => (new Charts)->customers('admin', 'month'),
                'income' => (new Charts)->income('admin', 'month'),
                'sales' => (new Charts)->sales('admin', 'week'),
            ],
        ]);
    }

    public function saveSettings(Request $request)
    {
        \Gate::authorize('usable', 'foodbags');
        $validator = Validator::make($request->all(), [
            'config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => $validator->errors()->first(),
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        collect($request->config)->map(function($config, $key) {
            if (!in_array($config, [
                "contact_address",
                "currency",
                "currency_symbol",
                "default_banner",
                "frontend_link",
                "prefered_notification_channels",
                "site_name",
                "slack_debug",
                "slack_logger",
                "token_lifespan",
                "trx_prefix",
                "use_queue",
                "verify_email",
                "verify_phone",
            ])) {
                return $this->buildResponse([
                    'message' => "You are not allowed to configure " . $config,
                    'status' => 'error',
                    'response_code' => 422,
                ]);
            }
            Config::write("settings.{$key}", $config);
        });

        return $this->buildResponse([
            'message' => 'Configuration Saved.',
            'status' =>  'success',
            'response_code' => 200,
        ]);
    }
}