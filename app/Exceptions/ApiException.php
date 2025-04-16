<?php

namespace App\Exceptions;

use App\Http\Controllers\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Psr\Container\NotFoundExceptionInterface;

class ApiException extends \Exception
{

}
