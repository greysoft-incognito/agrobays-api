<?php

namespace App\Exceptions;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Request;
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

        $this->renderable(function (
            \ErrorException | AccessDeniedHttpException | MethodNotAllowedHttpException | NotFoundHttpException | UnprocessableEntityHttpException | ThrottleRequestsException $e) {
            return $this->renderException($e->getMessage(), $e instanceof \ErrorException ? 500 : $e->getStatusCode());
        });

        $this->renderable(function (UnauthorizedHttpException $e) {
            return $this->renderException('You are not logged in.', 401);
        });

    }

    protected function renderException($msg, $code = 404)
    {
        if (request()->is('api/*')) {
            return (new Controller)->buildResponse([
                'message' => $msg,
                'status' => 'error',
                'response_code' => $code,
            ]);
        }
    }
}
