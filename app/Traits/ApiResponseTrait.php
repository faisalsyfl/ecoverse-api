<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Memformat respons sukses.
     */
    protected function successResponse($data, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Memformat respons error.
     */
    protected function errorResponse(string $message, int $statusCode, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    // --- Helper Sukses ---

    protected function respondSuccess($data, string $message = 'Success'): JsonResponse
    {
        return $this->successResponse($data, $message, 200);
    }

    protected function respondCreated($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    // --- Helper Error ---
    
    protected function respondError(string $message, $errors = null): JsonResponse
    {
        return $this->errorResponse($message, 400, $errors);
    }

    protected function respondUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    protected function respondForbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    protected function respondNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    protected function respondInternalError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, 500);
    }
}