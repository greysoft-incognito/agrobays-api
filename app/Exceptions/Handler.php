<?php

namespace App\Exceptions;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Request $request, Throwable $e) {
            //
        });

        $this->renderable(function (AccessDeniedHttpException|MethodNotAllowedHttpException|NotFoundHttpException|UnprocessableEntityHttpException|ThrottleRequestsException $e) {
            return $this->renderException($e->getMessage(), $e->getStatusCode());
        });

        $this->renderable(function (\ErrorException|TransportException|QueryException $e) {
            $line = ($e instanceof \ErrorException ? ' in '.$e->getFile().' on line '.$e->getLine() : '');

            return $this->renderException($e->getMessage().$line, 500);
        });

        $this->renderable(function (UnauthorizedHttpException $e) {
            return $this->renderException('You are not logged in.', 401);
        });
    }

    protected function renderException(string $msg, $code = 404, array $misc = [])
    {
        if (request()->is('api/*')) {
            return (new Controller)->buildResponse(array_merge([
                'message' => $msg,
                'status' => 'error',
                'response_code' => $code,
            ], $misc));
        }
    }
}