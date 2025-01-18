<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
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

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->wantsJson() || $request->is('api/*')) {
            if ($exception instanceof MethodNotAllowedHttpException) {
                return response()->json(['error' => 'Method not allowed.', 'message' => $exception->getMessage()], 405);
            }
            if ($exception instanceof NotFoundHttpException) {
                return response()->json(['error' => 'Not found.', 'message' => $exception->getMessage()], 404);
            }
            if ($exception instanceof ModelNotFoundException) {
                return response()->json(['error' => 'Model not found.', 'message' => $exception->getMessage()], 404);
            }
            if ($exception instanceof AuthorizationException) {
                return response()->json(['error' => 'Forbidden.', 'message' => $exception->getMessage()], 403);
            }
            if ($exception instanceof BadRequestException) {
                return response()->json(['error' => 'Bad Request.', 'message' => $exception->getMessage()], 400);
            }
            if ($exception instanceof HttpException) {
                return response()->json(['error' => 'Http Exception.', 'message' => $exception->getMessage()], $exception->getStatusCode());
            }
            if ($exception instanceof QueryException) {
                return response()->json(['error' => 'Database query error.', 'message' => $exception->getMessage()], 500);
            }
            // return response()->json(['error' => 'Database query error.', 'message' => 'An unexpected error occurred'], $exception->getStatusCode());
        }

        return parent::render($request, $exception);
    }
}
