<?php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;

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
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }
 
        return parent::render($request, $exception);
    }
 
    private function handleApiException(Request $request, Throwable $exception): \Illuminate\Http\JsonResponse
    {
        $exception = $this->prepareException($exception);
 
        $status = $this->getExceptionStatus($exception);
        $message = $this->getExceptionMessage($exception, $status);
 
        $response = [
            'status' => 'error',
            'message' => $message,
        ];
 
        if ($exception instanceof ValidationException) {
            $response['errors'] = $exception->errors();
        }
 
        if (config('app.debug')) {
            $response['exception'] = get_class($exception);
            $response['trace'] = collect($exception->getTrace())->map(function ($trace) {
                return \Illuminate\Support\Arr::except($trace, ['args']);
            })->all();
        }
 
        return response()->json($response, $status);
    }
 
    /**
     * Determine the correct HTTP status code for the exception.
     */
    private function getExceptionStatus(Throwable $exception): int
    {
        if ($this->isHttpException($exception)) {
            return $exception->getStatusCode();
        }
 
        if ($exception instanceof ValidationException) {
            return $exception->status;
        }
 
        if ($exception instanceof AuthenticationException) {
            return 401;
        }
 
        if ($exception instanceof AuthorizationException) {
            return 403;
        }
 
        return 500;
    }
 
    /**
     * Determine the error message based on the exception and status.
     */
    private function getExceptionMessage(Throwable $exception, int $status): string
    {
        $message = $exception->getMessage();
 
        if (!empty($message) && !config('app.debug')) {
            return match ($status) {
                401 => 'Unauthenticated.',
                403 => 'Forbidden.',
                404 => 'The requested resource was not found.',
                422 => 'The given data was invalid.',
                500 => 'An unexpected server error occurred.',
                default => 'An error occurred.',
            };
        }
 
        if (!empty($message)) {
            return $message;
        }
 
        return match ($status) {
            401 => 'Unauthenticated.',
            403 => 'Forbidden.',
            404 => 'Not Found.',
            405 => 'Method Not Allowed.',
            422 => 'The given data was invalid.',
            default => 'An unexpected server error occurred.',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | How to Use This Exception Handler
    |--------------------------------------------------------------------------
    |
    | This handler is designed to automatically catch exceptions for API routes
    | and format them into a consistent JSON response that the Flutter app expects.
    |
    | 1. How It Works:
    |    - The `render` method checks if an incoming request is for an API route
    |      (i.e., starts with `/api/` or has an `Accept: application/json` header).
    |    - If it is an API request, it passes the exception to `handleApiException`.
    |    - `handleApiException` formats the error into a standard JSON object:
    |      {
    |        "status": "error",
    |        "message": "A descriptive error message.",
    |        "errors": { ... } // (This key only appears for 422 Validation Errors)
    |      }
    |    - It automatically handles common exceptions like ValidationException (422),
    |      AuthenticationException (401), AuthorizationException (403),
    |      and NotFoundHttpException (404).
    |    - Any other unhandled exception will result in a 500 Internal Server Error.
    |
    | 2. How to Register a Custom Exception:
    |    You can create your own specific exceptions to have more control over
    |    error responses.
    |
    |    Step 1: Create a new Exception Class using Artisan.
    |    ----------------------------------------------------
    |    php artisan make:exception PaymentFailedException
    |
    |    Step 2: Throw the exception from your application logic (e.g., a Controller).
    |    -----------------------------------------------------------------------------
    |    use App\Exceptions\PaymentFailedException;
    |
    |    if ($paymentFails) {
    |        throw new PaymentFailedException('The payment could not be processed.');
    |    }
    |
    |    Step 3: Register a handler for it in the `register()` method of this file.
    |    ---------------------------------------------------------------------------
    |    public function register()
    |    {
    |        $this->renderable(function (PaymentFailedException $e, Request $request) {
    |            // Check if it's an API request and return a custom JSON response.
    |            if ($request->is('api/*')) {
    |                return response()->json([
    |                    'status' => 'error',
    |                    'message' => $e->getMessage()
    |                ], 422); // Use an appropriate HTTP status code.
    |            }
    |        });
    |    }
    |
    */
}