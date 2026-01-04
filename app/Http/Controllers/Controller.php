<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return a success response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data = null, ?string $message = null, int $code = 200)
    {
        $response = ['success' => true];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param string $errorCode
     * @param int $httpCode
     * @param array|null $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $message, string $errorCode = 'ERROR', int $httpCode = 400, ?array $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'code' => $errorCode,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $httpCode);
    }

    /**
     * Return a created response.
     *
     * @param mixed $data
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function created($data = null, ?string $message = null)
    {
        return $this->success($data, $message ?? __('messages.created'), 201);
    }

    /**
     * Return a no content response.
     *
     * @return \Illuminate\Http\Response
     */
    protected function noContent()
    {
        return response()->noContent();
    }
}



