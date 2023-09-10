<?php

namespace App\Http\Controllers\v2\Admin;

use App\Actions\ArrayFile;
use App\Actions\Greysoft\Charts;
use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Jobs\MassUpdateUsers;
use App\Models\Subscription;
use Illuminate\Http\Request;

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
        'referral_system' => 'boolean',
        'referral_bonus' => 'decimal',
        'referral_mode' => 'integer',
        'foodbag_locktime' => 'decimal',
    ];

    public function charts()
    {
        \Gate::authorize('usable', 'dashboard');

        return $this->responseBuilder([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
            'data' => [
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
        $this->validate($request, [
            'config' => 'required|array',
        ]);

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

        // MassUpdateUsers::dispatch(['has_pending_updates' => true]);

        return $this->responseBuilder([
            'message' => 'Configuration Saved.',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }
}