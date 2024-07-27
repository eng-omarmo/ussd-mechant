<?php


namespace App\Traits;

trait ApiResponse
{
    protected function response($data = null, $message = null, $status = 200)
    {
        return response()->json([
            'data' => $data,
            'message' => $message
        ], $status);
    }
    protected function success($data = null, $message = null)
    {
        return $this->response($data, $message, 200);
    }
    protected function error($data = null, $message = null)
    {
        return $this->response($data, $message, 400);
    }

    protected function notFound($data = null, $message = null)
    {
        return $this->response($data, $message, 404);
    }

    protected function unauthorized($data = null, $message = null)
    {
        return $this->response($data, $message, 401);
    }
    protected function ok($data = null, $message = null)
    {
        return $this->response($data, $message, 200);
    }
}
