<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     *
     * @param string $messageKey Translation key or plain text.
     * @param mixed $data
     * @param int $code HTTP status code.
     */
    protected function success(string $messageKey, mixed $data = null, int $code = 200): JsonResponse
    {
        if ($data instanceof JsonResource || $data instanceof AnonymousResourceCollection) {
            $data = $data->resolve();
        }

        return response()->json([
            'status'  => true,
            'message' => __($messageKey),
            'data'    => $data,
        ], $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $messageKey Translation key or plain text.
     * @param mixed $errors Array/List of errors or string.
     * @param int $code HTTP status code.
     */
    protected function error(string $messageKey, mixed $errors = null, int $code = 400): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => __($messageKey),
            'errors'  => $errors,
        ], $code);
    }

    /**
     * Standard paginated response.
     *
     * @param string $messageKey
     * @param LengthAwarePaginator $paginator
     */
    public function paginate(string $messageKey, $collection, $paginator, int $code = 200): JsonResponse
    {
        if ($collection instanceof JsonResource || $collection instanceof AnonymousResourceCollection) {
            $collection = $collection->resolve();
        }

        return response()->json([
            'status'     => true,
            'message'    => __($messageKey),
            'data'       => $collection,
            'pagination' => [
                'total'        => $paginator->total(),
                'perPage'      => $paginator->perPage(),
                'currentPage'  => $paginator->currentPage(),
                'lastPage'     => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ], $code);
    }

    /**
     * Execute a callback safely inside try/catch and return auto-handled JSON responses.
     *
     * Usage:
     * return $this->safeExecute('user.created', function () {
     *     DB::transaction(function() {
     *          service logic
     *     });
     *     return $user;
     * });
     *
     * @param string $successMessage
     * @param callable $callback
     */
    protected function safeExecute(string $successMessage, callable $callback, int $code = 200): JsonResponse
    {
        try {
            $result = $callback();

            return $this->success($successMessage, $result, $code);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {

            report($e); // Logs error in Laravel logs

            return $this->error('messages.something_went_wrong', [
                'exception' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }
}
