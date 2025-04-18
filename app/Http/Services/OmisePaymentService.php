<?php

namespace App\Http\Services;

use App\Exceptions\ApiException;
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

    private $error=null;
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->secretKey = config('services.omise.secret_key');
        $this->publicKey = config('services.omise.public_key');
        $this->webhookKey = config('services.omise.webhook_secret');
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
            $domain = request()->getHost();
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
                'return_uri' => "https://{$domain}/api/payment/callback",
            ];

            // 发送请求到Omise API创建支付
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post("{$this->apiUrl}/charges", $paymentData);
            Log::channel('pay')->info('【omise创建支付】',['invoice_id'=>$invoice->id,'response'=>$response->json()]);
            // 检查响应
            if ($response->successful()) {
                $paymentData = $response->json();
                // 返回支付信息
                return [
                    'transaction_id' => $paymentData['id'],
                    'qr_code' => $paymentData['source']['scannable_code']['image']['download_uri'] ?? null,
                    'payment_url' => $paymentData['authorize_uri'] ?? null,
                ];
            } else {
                throw new ApiException('支付创建失败：' . ($response->json()['message'] ?? '未知错误'));
            }
    }

    /**
     * 处理Omise支付回调
     *
     * @param Request $request 回调请求
     */
    public function handleWebhook(Request $request)
    {

        try {
            // 验证Webhook签名
//            if (!$this->verifyWebhookSignature($request)) {
//                return false;
//            }
//            failed
            $payload = $request->all();
            $event = $payload['key'] ?? ''; // 修正为获取event字段
            $data = $payload['data'] ?? [];

            // 处理支付完成事件
            if ($event === 'charge.complete') {
                $this->processSuccessfulPayment($data);
            }

        } catch (\Throwable $e) {
            Log::error('【处理Omise Webhook异常】', [
//                'header'=>$request->header(),
                'params' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
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
        $status = $data['source']['charge_status'] ?? '';
        //只处理成功的事件
        if($status <> 'successful'){
            return true;
        }
        //自己系统的账单id判断
        $invoice_id = $data['metadata']['invoice_id'] ?? null;

        if (!$invoice_id) {
            throw new ApiException(lang('找不到对应的支付记录'));
        }

        //todo 到omise查询订单是否真实有效

        //处理数据库逻辑
        DB::transaction(function ()use($invoice_id, $data){
            // 查找对应的支付记录，没有redis先直接用数据库行锁，预防并发请求，但此处操作幂等，不加也行
            $invoice = Invoice::query()->where('id', $invoice_id)->lockForUpdate()->first();

            if (!$invoice) {
                throw new ApiException(lang('找不到对应的支付记录'));
            }

            if($invoice->status > Invoice::STATUS_SENT){
                throw new ApiException(lang('订单状态异常'));
            }
            // 更新账单状态
            $invoice->transaction_id = $data['id'];
            $invoice->status = Invoice::STATUS_PAID_SUCCESS;
            $invoice->save();
        });

        return true;

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
        $calculatedSignature = hash_hmac('sha256', $request->getContent(), $this->webhookKey);
        if(!hash_equals($signature,$calculatedSignature)){
            Log::warning('Omise Webhook签名验证失败',['signature'=>$signature,'calculated'=>$calculatedSignature,'params'=>$request->all()]);
            return false;
        }
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
