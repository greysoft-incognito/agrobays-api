<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function buildResponse($data = [])
    {
        $message = $data['message'] ?? 'Request was successful';
        $code   = $data['response_code'] ?? 200;
        $resp   = $data['response_data'] ?? null;
        $status = $data['status'] ?? 'success';
        $errors = $data['errors'] ?? null;
        $token  = $data['token'] ?? null;

        unset($data['message'], $data['response_code'], $data['status'], $data['errors'], $data['token'], $data['response_data']);

        $response = [
            'message' => $message,
            'status' => $status,
            'response_code' => $code,
            'response' => $data,
        ];

        if ($errors)
        {
            $response['errors'] = $errors;
        }

        if ($token)
        {
            $response['token'] = $token;
        }

        if ($resp)
        {
            $response['response_data'] = $resp;
        }

        return response($response, $code);
    }
}