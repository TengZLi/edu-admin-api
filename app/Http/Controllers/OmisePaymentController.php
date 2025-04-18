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
     * 学生使用Omise支付账单
     *
     * @param Request $request
     * @param int $id 账单ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function payWithOmise(Request $request, $id)
    {
        try {
            $student = Auth::guard('student')->user();

            $invoice = Invoice::where('id', $id)
                ->where('student_id', $student->id)
                ->where('status', Invoice::STATUS_SENT) // 只能支付已发送的账单
                ->first();

            if (!$invoice) {
                return ApiResponse::error('账单不存在或不可支付');
            }

            // 创建Omise AlipayPlus MPM支付
            $paymentResult = $this->omiseService->createAlipayPlusMpmPayment($invoice);

            return ApiResponse::success($paymentResult);
        } catch (\Exception $e) {
            Log::error('创建Omise支付失败', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error($e->getMessage());
        }
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
        return response('支付成功，请关闭页面', 200);
    }
}
