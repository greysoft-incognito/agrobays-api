<?php

namespace App\Exceptions;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $request;

    protected $message;

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e)
    {
        $this->request = $request;

        if (config('app.testing', false) === false) {
            if ($request->isXmlHttpRequest() || request()->is('api/*')) {
                $line = method_exists($e, 'getFile') ? ' in ' . $e->getFile() : '';
                $line .= method_exists($e, 'getLine') ? ' on line ' . $e->getLine() : '';
                $getMessage = method_exists($e, 'getMessage') ? $e->getMessage() . $line : 'An error occured' . $line;
                $plainMessage = method_exists($e, 'getMessage') ? $e->getMessage() : null;

                if ((bool) collect($e?->getTrace())->firstWhere('function', 'abort')) {
                    $this->message = $e->getMessage();
                }

                $prefix = $e instanceof ModelNotFoundException ? str($e->getModel())->afterLast('\\')->title() . ' ' : '';

                return match (true) {
                    $e instanceof NotFoundHttpException ||
                        $e instanceof ModelNotFoundException => $this->renderException(
                            $prefix . HttpStatus::message(HttpStatus::NOT_FOUND),
                            HttpStatus::NOT_FOUND
                        ),
                    $e instanceof AuthorizationException ||
                        $e instanceof AccessDeniedHttpException ||
                        $e->getCode() === HttpStatus::FORBIDDEN => $this->renderException(
                            $plainMessage ? $plainMessage : HttpStatus::message(HttpStatus::FORBIDDEN),
                            HttpStatus::FORBIDDEN
                        ),
                    $e instanceof AuthenticationException ||
                        $e instanceof UnauthorizedHttpException => $this->renderException(
                            HttpStatus::message(HttpStatus::UNAUTHORIZED),
                            HttpStatus::UNAUTHORIZED
                        ),
                    $e instanceof MethodNotAllowedHttpException => $this->renderException(
                        HttpStatus::message(HttpStatus::METHOD_NOT_ALLOWED),
                        HttpStatus::METHOD_NOT_ALLOWED
                    ),
                    $e instanceof ValidationException => $this->renderException(
                        $e->getMessage(),
                        HttpStatus::UNPROCESSABLE_ENTITY,
                        ['errors' => $e->errors()]
                    ),
                    $e instanceof UnprocessableEntityHttpException => $this->renderException(
                        HttpStatus::message(HttpStatus::UNPROCESSABLE_ENTITY),
                        HttpStatus::UNPROCESSABLE_ENTITY
                    ),
                    // $e instanceof FileUnacceptableForCollection => $this->renderException(
                    //     __('You have selected an invalid image file.'),
                    //     HttpStatus::UNPROCESSABLE_ENTITY,
                    //     ['errors' => [collect($request->file())->keys()->first() => __('You have selected an invalid image file.')]]
                    // ),
                    $e instanceof ThrottleRequestsException => $this->renderException(
                        HttpStatus::message(HttpStatus::TOO_MANY_REQUESTS),
                        HttpStatus::TOO_MANY_REQUESTS
                    ),
                    default => $this->renderException($getMessage, HttpStatus::SERVER_ERROR),
                };
            } elseif ($this->isHttpException($e) && $e->getStatusCode() !== 401) {
                $this->registerErrorViewPaths();

                return response()->view(
                    'errors.generic',
                    [
                        'message' => $e->getMessage(),
                        'code' => $e->getStatusCode(),
                    ],
                    $e->getStatusCode()
                );
            }
        }

        return parent::render($request, $e);
    }

    protected function renderException(string $msg, $code = 404, array $misc = [])
    {
        $response = collect([
            'message' => $this->message ?? $msg,
            'status' => 'error',
            'response_code' => $code,
        ]);

        if ($this->request->version < 2) {
            return (new Controller())->buildResponse($response, array_merge($misc, ['data' => []]));
        } else {
            return (new Controller())->responseBuilder($response, array_merge($misc, ['response' => []]));
        }
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}