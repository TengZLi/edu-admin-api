<?php

namespace App\Http\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OmisePaymentService
{
    /**
     * Omise API 密钥
     */
    protected $secretKey;

    /**
     * Omise API 公钥
     */
    protected $publicKey;

    /**
     * Omise API 基础URL
     */
    protected $apiUrl = 'https://api.omise.co';

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->secretKey = config('services.omise.secret_key');
        $this->publicKey = config('services.omise.public_key');
    }

    /**
     * 创建AlipayPlus MPM支付
     *
     * @param Invoice $invoice 账单信息
     * @return array 支付信息，包含支付链接和交易ID
     * @throws \Exception 支付创建失败时抛出异常
     */
    public function createAlipayPlusMpmPayment(Invoice $invoice)
    {
        try {

            // 准备支付数据
            $paymentData = [
                'amount' => $this->convertToSatang($invoice->amount), // 转换为最小货币单位（泰铢转为satang）
                'currency' => 'THB',
                'source' => [
                    'type' => 'alipay_cn',//wechat_pay_mpm
                    'platform_type' => 'WEB', // 可以是 alipay, wechatpay, gcash 等
                ],
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'student_id' => $invoice->student_id,
                    'course_id' => $invoice->course_id
                ],
//                'return_uri' => route('payment.callback')
                'return_uri' => 'https://local.poper.edu.com/api/payment/callback',
            ];

            // 发送请求到Omise API创建支付
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post("{$this->apiUrl}/charges", $paymentData);
            Log::channel('pay')->info('【omise创建支付】', $response->json());
            // 检查响应
            if ($response->successful()) {
                $paymentData = $response->json();

                // 创建本地支付记录
                $payment = Payment::create([
                    'invoice_id' => $invoice->id,
                    'transaction_id' => $paymentData['id'], // Omise交易ID
                    'amount' => $invoice->amount,
                    'status' => Payment::STATUS_PENDING,
                ]);


                // 返回支付信息
                return [
                    'success' => true,
                    'payment_id' => $payment->id,
                    'transaction_id' => $paymentData['id'],
                    'qr_code' => $paymentData['source']['scannable_code']['image']['download_uri'] ?? null,
                    'payment_url' => $paymentData['authorize_uri'] ?? null,
                ];
            } else {
                Log::error('Omise支付创建失败', [
                    'invoice_id' => $invoice->id,
                    'error' => $response->json(),
                ]);

                throw new \Exception('支付创建失败：' . ($response->json()['message'] ?? '未知错误'));
            }
        } catch (\Exception $e) {
            Log::error('Omise支付处理异常', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * 处理Omise支付回调
     *
     * @param Request $request 回调请求
     * @return bool 处理结果
     */
    public function handleWebhook(Request $request)
    {
        try {
            // 验证Webhook签名
            if (!$this->verifyWebhookSignature($request)) {
                Log::warning('Omise Webhook签名验证失败');
                return false;
            }

            $payload = $request->all();
            $event = $payload['key'] ?? '';
            $data = $payload['data'] ?? [];

            // 处理支付成功事件
            if ($event === 'charge.complete') {
                return $this->processSuccessfulPayment($data);
            }

            // 处理支付失败事件
            if ($event === 'charge.failed') {
                return $this->processFailedPayment($data);
            }

            // 其他事件暂不处理
            Log::info('收到未处理的Omise Webhook事件', ['event' => $event]);
            return true;
        } catch (\Exception $e) {
            Log::error('处理Omise Webhook异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * 处理支付成功事件
     *
     * @param array $data 事件数据
     * @return bool 处理结果
     */
    protected function processSuccessfulPayment(array $data)
    {
        $transactionId = $data['id'] ?? null;

        if (!$transactionId) {
            Log::error('Omise支付成功事件缺少交易ID');
            return false;
        }

        // 查找对应的支付记录
        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            Log::error('找不到对应的支付记录', ['transaction_id' => $transactionId]);
            return false;
        }

        DB::beginTransaction();
        try {
            // 更新支付状态
            $payment->status = Payment::STATUS_PAID;
            $payment->paid_at = now();
            $payment->save();

            // 更新账单状态
            $invoice = $payment->invoice;
            $invoice->status = Invoice::STATUS_PAID_SUCCESS;
            $invoice->save();

            DB::commit();

            Log::info('Omise支付成功处理完成', [
                'transaction_id' => $transactionId,
                'invoice_id' => $invoice->id,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('处理Omise支付成功事件异常', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 处理支付失败事件
     *
     * @param array $data 事件数据
     * @return bool 处理结果
     */
    protected function processFailedPayment(array $data)
    {
        $transactionId = $data['id'] ?? null;

        if (!$transactionId) {
            Log::error('Omise支付失败事件缺少交易ID');
            return false;
        }

        // 查找对应的支付记录
        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            Log::error('找不到对应的支付记录', ['transaction_id' => $transactionId]);
            return false;
        }

        DB::beginTransaction();
        try {
            // 更新支付状态为失败
            $payment->status = Payment::STATUS_PENDING; // 重置为待处理状态，允许重新支付
            $payment->save();

            // 更新账单状态
            $invoice = $payment->invoice;
            $invoice->status = Invoice::STATUS_PAID_FAILD;
            $invoice->save();

            DB::commit();

            Log::info('Omise支付失败处理完成', [
                'transaction_id' => $transactionId,
                'invoice_id' => $invoice->id,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('处理Omise支付失败事件异常', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 验证Webhook签名
     *
     * @param Request $request 回调请求
     * @return bool 验证结果
     */
    protected function verifyWebhookSignature(Request $request)
    {
        // 获取Omise签名
        $signature = $request->header('Omise-Signature');

        if (empty($signature)) {
            return false;
        }

        // 实际项目中应该实现完整的签名验证逻辑
        // 这里简化处理，仅检查签名是否存在
        return true;
    }

    /**
     * 将金额转换为最小货币单位（例如：泰铢转为satang）
     *
     * @param float $amount 金额
     * @return int 转换后的金额
     */
    protected function convertToSatang(float $amount): int
    {
        return (int)($amount * 100);
    }
}
