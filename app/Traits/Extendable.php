<?php

namespace App\Traits;

use App\EnumsAndConsts\HttpStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * Provide methods that determine how response should be generated.
 */
trait Extendable
{
    /**
     * Prepare the API response
     *
     * @param  array  $data
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function responseBuilder($data = [], $extra_data = null)
    {
        $resp = $data['response_data'] ?? null;
        $errors = $data['errors'] ?? null;
        $token = $data['token'] ?? null;
        $info = [
            'api' => \App\Services\AppInfo::basic(),
            'message' => $data['message'] ?? HttpStatus::message(HttpStatus::OK),
            'status' => $data['status'] ?? 'success',
            'response_code' => $data['response_code'] ?? HttpStatus::OK,
        ];

        $data = collect($data)->except('message', 'response_code', 'status', 'errors', 'token', 'response_data');

        $main_data = $data['data'] ?? $data ?? [];
        if (isset($main_data['data']['data']) && count($main_data['data']) === 1) {
            $main_data = $main_data['data']['data'] ?? [];
        }

        $response = collect($info);
        if ($extra_data) {
            if (is_array($extra_data)) {
                foreach ($extra_data as $key => $value) {
                    $response->prepend($value, $key);
                }
            } else {
                $response->prepend($extra_data, 'load');
            }
        }
        if ($resp) {
            $response->prepend($resp, 'resp');
        }
        if ($errors) {
            $response->prepend($errors, 'errors');
        }
        if ($token) {
            $response->prepend($token, 'token');
        }
        $response->prepend($main_data, 'data');

        return response($response, $info['response_code']);
    }

    /**
     * Prepare the API response
     *
     * @param  array  $data
     *
     * @deprecated version 1.0.6-beta use responseBuilder instead
     *
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function buildResponse($data = [], $extra_data = null)
    {
        $message = $data['message'] ?? 'Request was successful';
        $code = $data['response_code'] ?? 200;
        $resp = $data['response_data'] ?? null;
        $status = $data['status'] ?? 'success';
        $errors = $data['errors'] ?? null;
        $token = $data['token'] ?? null;

        unset($data['message'], $data['response_code'], $data['status'], $data['errors'], $data['token'], $data['response_data']);

        $response = [
            'api' => \App\Services\AppInfo::basic(),
            'message' => $message,
            'status' => $status,
            'response_code' => $code,
            'response' => $data ?? [],
        ];

        if ($extra_data) {
            $response = array_merge($response, is_array($extra_data) ? $extra_data : ['load' => $extra_data]);
        }

        if ($errors) {
            $response['errors'] = $errors;
        }

        if ($token) {
            $response['token'] = $token;
        }

        if ($resp) {
            $response['response_data'] = $resp;
        }

        return response($response, $code);
    }

    /**
     * Prepare the validation error.
     *
     * @param  Validator  $validator
     * @return void
     */
    public function validatorFails(Validator $validator, $field = null)
    {
        return $this->buildResponse([
            'message' => $field ? $validator->errors()->first() : 'Your input has a few errors',
            'status' => 'error',
            'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
            'errors' => $validator->errors(),
        ]);
    }

    public function time()
    {
        return time();
    }

    /**
     * Check if this app is running on a local host
     *
     * @return bool
     */
    public function isLocalHosted(): bool
    {
        $ip = request()->ip();

        return stripos($ip, '127.0.0') !== false && env('APP_ENV') === 'local';
    }

    /**
     * Get the client IP address  or return preset IP if locally hosted
     *
     * @return void
     */
    public function ip()
    {
        $ip = request()->ip();
        if ($this->isLocalHosted()) {
            $ip = '197.210.76.68';
        }

        return $ip;
    }

    /**
     * Get the client's IP information
     *
     * @param [type] $key
     * @return void
     */
    public function ipInfo($key = null)
    {
        $info['country'] = 'US';

        if (! $this->isLocalHosted()) {
            if (($user = Auth::user()) && $user->access_data) {
                $info = $user->access_data;
            } else {
                if (config('settings.system.ipinfo.access_token') && config('settings.collect_user_data', true)) {
                    $ipInfo = \Illuminate\Support\Facades\Http::get('ipinfo.io/' . $this->ip(), [
                        'token' => config('settings.system.ipinfo.access_token'),
                    ]);
                    if ($ipInfo->status() === 200) {
                        $info = $ipInfo->json() ?? $info;
                    }
                }
            }
        }

        return $key ? ($info[$key] ?? '') : $info;
    }

    public static function generateString($strength = 16, $group = 0, $input = null)
    {
        $groups = [
            '0123456789abcdefghi' . md5(time()) . 'jklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' . time() . rand(),
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' . time() . rand(),
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '01234567890123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ];
        $input = $input ?? $groups[$group] ?? $groups[2];

        $input_length = strlen($input);
        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
    }

    private static function makeSlug($string = null)
    {
        $slug = $string
            ? str($string)->slug()->toString()
            : self::generateString(32);

        if (self::whereSlug($slug)->exists()) {
            if ($string) {
                $string .= rand();
            }
            return self::makeSlug($string);
        }

        return $slug;
    }
}
