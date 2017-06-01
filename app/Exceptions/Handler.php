<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        // 自定义处理参数验证异常,错误码为10000
        if ($e instanceof ValidationException) {
            $result = [
                "msg" => $e->getResponse()->original,
                "code" => 10000
            ];
            return response($result, 401);
        // 处理自定义异常
        } else if ($e instanceof ApiException) {
            $result = [
                "msg"    => $e->getMessage(),
                "code" => $e->getCode()
            ];
            if ($e->http_code == 404) {
                return response("", $e->http_code);
            } else {
                return response($result, $e->http_code);
            }
        } else {
            // 交给框架自己的异常错误处理类去处理
            return parent::render($request, $e);
        }
    }
}
