<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Greysoft\Charts;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Actions\ArrayFile;

class AdminController extends Controller
{
    protected $configs = [
        'contact_phone' => 'string',
        'contact_email' => 'string',
        'contact_address' => 'string',
        'office_address' => 'string',
        'currency' => 'string',
        'currency_symbol' => 'string',
        // "default_banner" => "string",
        'frontend_link' => 'string',
        'prefered_notification_channels' => 'array',
        'site_name' => 'string',
        'withdraw_to' => 'string',
        'slack_debug' => 'boolean',
        'slack_logger' => 'boolean',
        'token_lifespan' => 'integer',
        'shipping_fee' => 'integer',
        'paid_shipping' => 'boolean',
        // "trx_prefix" => "string",
        'verify_email' => 'boolean',
        'verify_phone' => 'boolean',
        'feedback_system' => 'boolean',
        'foodbag_locktime' => 'decimal',
    ];

    public function charts($type = 'pie')
    {
        \Gate::authorize('usable', 'dashboard');

        return $this->buildResponse([
            'message' => 'OK',
            'status' => 'success',
            'response_code' => 200,
            'charts' => [
                'pie' => (new Charts())->getPie('admin'),
                'bar' => (new Charts())->getBar('admin'),
                'transactions' => (new Charts())->totalTransactions('admin', 'month'),
                'customers' => (new Charts())->customers('admin', 'month'),
                'income' => (new Charts())->income('admin', 'month'),
                'sales' => (new Charts())->sales('admin', 'week'),
                'subscriptions' => [
                    'all' => Subscription::count(),
                    'pending' => Subscription::whereStatus('pending')->count(),
                    'active' => Subscription::whereStatus('active')->count(),
                    'completed' => Subscription::whereStatus('complete')->count(),
                    'withdraw' => Subscription::whereStatus('withdraw')->count(),
                    'closed' => Subscription::whereStatus('closed')->count(),
                ],
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

        $config = ArrayFile::open(base_path('config/settings.php'));

        collect($request->config)->map(function ($value, $key) use ($config) {
            if (in_array($key, array_keys($this->configs))) {
                // Cast the value to the correct type
                $type = $this->configs[$key];
                $value = match (true) {
                    $type == 'boolean' => (bool) $value,
                    $type == 'integer' => (int) $value,
                    $type == 'array' => (array) $value,
                    $type == 'string' => (string) $value,
                    $type == 'decimal' => (float) $value,
                    default => $value,
                };

                // Write the config to the file
                $config->set($key, $value);
            }
        });

        $config->set('last_setting_time', now()->toDateTimeString());
        $config->write();

        return $this->responseBuilder([
            'message' => 'Configuration Saved.',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }
}
