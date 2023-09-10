<?php

namespace App\EnumsAndConsts;

/**
 * HTTP Status codes
 */
class HttpStatus
{
    public const OK = '200';                   // OK

    public const CREATED = '201';              // Created

    public const ACCEPTED = '202';             // Accepted

    public const NO_CONTENT = '204';           // No Content

    public const BAD_REQUEST = '400';          // Bad Request

    public const UNAUTHORIZED = '401';         // Unauthenticated

    public const NOT_FOUND = '404';            // Not Found

    public const FORBIDDEN = '403';            // Access Denied

    public const METHOD_NOT_ALLOWED = '405';   // Method Not Allowed

    public const UNPROCESSABLE_ENTITY = '422'; // Unprocessable Entity

    public const TOO_MANY_REQUESTS = '429';    // Too Many Requests

    public const SERVER_ERROR = '500';         // Internal Server Error

    public static function message(string $code, $default = 'Not found.')
    {
        return (new self())->getMessage($code, $default);
    }

    public function getMessage($code = self::OK, $default = 'Not found.')
    {
        switch ($code) {
            case self::OK:
                return 'OK';
                break;

            case self::CREATED:
                return 'Created';
                break;

            case self::ACCEPTED:
                return 'Accepted';
                break;

            case self::NO_CONTENT:
                return 'No Content.';
                break;

            case self::BAD_REQUEST:
                return 'Bad Request.';
                break;

            case self::UNAUTHORIZED:
                return 'Unauthenticated: Please login to continue.';
                break;

            case self::FORBIDDEN:
                return 'Access Denied';
                break;

            case self::METHOD_NOT_ALLOWED:
                return 'Method Not Allowed.';
                break;

            case self::UNPROCESSABLE_ENTITY:
                return 'Unprocessable Entity.';
                break;

            case self::TOO_MANY_REQUESTS:
                return 'Too Many Requests.';
                break;

            case self::SERVER_ERROR:
                return 'Internal Server Error.';
                break;

            case self::NOT_FOUND:
                return 'Not Found.';
                break;

            default:
                return $default;
                break;
        }
    }
}
