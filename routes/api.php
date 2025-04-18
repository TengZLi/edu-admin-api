<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OmisePaymentController;
use App\Http\Controllers\StudentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// 公共路由
Route::post('/auth/login', [AuthController::class, 'login']);
// Route::get('/auth/captcha', [AuthController::class, 'captcha']);
// Omise支付回调
Route::post('/payment/webhook', [OmisePaymentController::class, 'handleWebhook']);
Route::get('/payment/callback', [OmisePaymentController::class, 'paymentCallback'])->name('payment.callback');

// 教师路由组
Route::middleware('auth:teacher')->prefix('teacher')->group(function () {
    // 用户信息
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // 课程管理
    Route::prefix('course')->group(function () {
        Route::get('/', [CourseController::class, 'index']);
        Route::get('/list', [CourseController::class, 'list']);
        Route::post('/', [CourseController::class, 'create']);
        Route::get('/{id}', [CourseController::class, 'show']);
        Route::put('/{id}', [CourseController::class, 'update']);
    });
    // 获取学生列表
    Route::get('/students', [StudentController::class, 'list']);
    // 账单管理
    Route::prefix('invoice')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'create']);
        Route::post('/{id}/send', [InvoiceController::class, 'send']);
    });
});

// 学生路由组
Route::middleware('auth:student')->prefix('student')->group(function () {
    // Omise支付
    Route::post('/invoice/{id}/pay-with-omise', [OmisePaymentController::class, 'payWithOmise']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // 我的课程
    Route::get('/course', [CourseController::class, 'studentCourses']);

    // 我的账单
    Route::prefix('invoice')->group(function () {
        Route::get('/', [InvoiceController::class, 'studentInvoices']);
        Route::post('/pay/{id}', [InvoiceController::class, 'pay']);
    });
});
