<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Greysoft\Charts;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;

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
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        collect($request->config)->map(function($config) {
            Config::write("settings.{$config->key}", $config->value);
        });

        return $this->buildResponse([
            'message' => 'Configuration Saved.',
            'status' =>  'success',
            'response_code' => 200,
        ]);
    }
}
