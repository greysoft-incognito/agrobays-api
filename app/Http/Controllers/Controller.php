<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Prepare the API response
     *
     * @param  array  $data
     * @return void
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
            'api' => [
                'name' => 'Agrobays',
                'version' => env('APP_VERSION', '1.0.6-beta'),
                'author' => 'Greysoft Limited',
                'updated' => now(),
            ],
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
            'response_code' => 422,
            'errors' => $validator->errors(),
        ]);
    }
}
