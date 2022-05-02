<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
       // \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        //\Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];
    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        $token_error_msg = "Oops! An error occurred, please try again!";
        
        if($exception instanceof AuthenticationException){
            if ($request->is('api/lead/*')) {
                return response([
                    "success" => false,
                    "message" => "Unauthenticated.",
                    "code" => 401
                ],401);
            } else if ($request->is('api/*')) {
                return response([
                    "success" => false,
                    "message" => "You have already logged in with another device.",
                    "code" => 401
                ],401);
            }else{
                \Session::flash('errorFails',$token_error_msg);
                if(!\Auth::check() && $request->ajax() && $request->segment(1) == 'messaging') {
                    return response()->json(['error' => 'Unauthenticated.'], 401); 
                }
                return redirect('/login');
            }
        }elseif($exception instanceof MethodNotAllowedHttpException) {
            \Session::flash('errorFails',$token_error_msg);
            return redirect('/');
        }elseif($exception instanceof TokenMismatchException){
            if(!\Auth::check() && $request->ajax() && \Route::currentRouteName() == 'reviewFeedback') {
                return response()->json(['error' => 'Unauthenticated.'], 401); 
            }
            \Session::flash('errorFails',$token_error_msg);
            return redirect('/');
        }/*elseif($exception instanceof \ErrorException){
            return response()->view('errors.500', [], 500);
        }elseif($exception instanceof \Exception){
            return response()->view('errors.404', [], 404);
        }*/
        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        return redirect()->guest(route('login'));
    }
}
