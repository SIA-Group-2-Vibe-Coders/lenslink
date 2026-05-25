<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a 200 success response.
     */
    protected function successResponse(mixed $data = null, string $message = 'Success'): JsonResponse
    {
        $payload = ['status' => 'success', 'message' => $message];
        if (!is_null($data)) {
            $payload['data'] = $data;
        }
        return response()->json($payload, 200);
    }

    /**
     * Return a 201 Created response.
     */
    protected function createdResponse(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        $payload = ['status' => 'success', 'message' => $message];
        if (!is_null($data)) {
            $payload['data'] = $data;
        }
        return response()->json($payload, 201);
    }

    /**
     * Return a generic error response.
     */
    protected function errorResponse(string $message = 'An error occurred', int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Return a 401 Unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthenticated.'): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], 401);
    }

    /**
     * Return a 403 Forbidden response.
     */
    protected function forbiddenResponse(string $message = 'You are not authorized to perform this action.'): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], 403);
    }

    /**
     * Return a 404 Not Found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found.'): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], 404);
    }

    /**
     * Return a 422 Unprocessable Entity response.
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed.'): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], 422);
    }
}
