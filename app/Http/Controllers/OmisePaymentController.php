<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\ApiResponse;
use App\Http\Services\OmisePaymentService;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OmisePaymentController extends Controller
{
    /**
     * Omise支付服务
     *
     * @var OmisePaymentService
     */
    protected OmisePaymentService $omiseService;

    /**
     * 构造函数
     *
     * @param OmisePaymentService $omiseService
     */
    public function __construct(OmisePaymentService $omiseService)
    {
        $this->omiseService = $omiseService;
    }

    /**
     * 处理Omise支付回调
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        try {
            $this->omiseService->handleWebhook($request);
            return response('Webhook processed successfully');
        } catch (\Throwable $e) {
            return response('Webhook processing error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 支付回调前端跳转页面
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function paymentCallback(Request $request)
    {
        return response('支付已完成，请关闭页面', 200);
    }
}
