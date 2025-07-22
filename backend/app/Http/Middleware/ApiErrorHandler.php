<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class ApiErrorHandler
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * Handle various types of exceptions
     */
    protected function handleException(Request $request, Throwable $e): Response
    {
        // Log the exception
        Log::error('API Exception', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // Handle specific exception types
        return match (true) {
            $e instanceof ValidationException => $this->handleValidationException($e),
            $e instanceof ModelNotFoundException => $this->handleModelNotFoundException($e),
            $e instanceof NotFoundHttpException => $this->handleNotFoundHttpException($e),
            $e instanceof MethodNotAllowedHttpException => $this->handleMethodNotAllowedHttpException($e),
            default => $this->handleGenericException($e)
        };
    }

    /**
     * Handle validation exceptions
     */
    protected function handleValidationException(ValidationException $e): Response
    {
        return response()->json([
            'success' => false,
            'error' => 'Validation failed',
            'message' => 'The given data was invalid',
            'errors' => $e->errors(),
            'status_code' => 422
        ], 422);
    }

    /**
     * Handle model not found exceptions
     */
    protected function handleModelNotFoundException(ModelNotFoundException $e): Response
    {
        $model = class_basename($e->getModel());
        
        return response()->json([
            'success' => false,
            'error' => 'Resource not found',
            'message' => "The requested {$model} could not be found",
            'status_code' => 404
        ], 404);
    }

    /**
     * Handle 404 not found exceptions
     */
    protected function handleNotFoundHttpException(NotFoundHttpException $e): Response
    {
        return response()->json([
            'success' => false,
            'error' => 'Not found',
            'message' => 'The requested resource could not be found',
            'status_code' => 404
        ], 404);
    }

    /**
     * Handle method not allowed exceptions
     */
    protected function handleMethodNotAllowedHttpException(MethodNotAllowedHttpException $e): Response
    {
        return response()->json([
            'success' => false,
            'error' => 'Method not allowed',
            'message' => 'The HTTP method is not allowed for this route',
            'status_code' => 405
        ], 405);
    }

    /**
     * Handle generic exceptions
     */
    protected function handleGenericException(Throwable $e): Response
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        // Don't expose internal error details in production
        $message = app()->isProduction() 
            ? 'An unexpected error occurred' 
            : $e->getMessage();

        return response()->json([
            'success' => false,
            'error' => 'Internal server error',
            'message' => $message,
            'status_code' => $statusCode,
            'debug' => app()->isProduction() ? null : [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ]
        ], $statusCode);
    }

    /**
     * Create standardized error response
     */
    public static function errorResponse(
        string $error, 
        string $message, 
        int $statusCode = 500, 
        array $extra = []
    ): Response {
        return response()->json([
            'success' => false,
            'error' => $error,
            'message' => $message,
            'status_code' => $statusCode,
            ...$extra
        ], $statusCode);
    }

    /**
     * Create standardized success response
     */
    public static function successResponse(
        mixed $data = null, 
        string $message = 'Success', 
        int $statusCode = 200,
        array $meta = []
    ): Response {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status_code' => $statusCode,
            'meta' => $meta
        ], $statusCode);
    }
}