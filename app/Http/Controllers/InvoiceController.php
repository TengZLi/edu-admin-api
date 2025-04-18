<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\ApiResponse;
use App\Http\Services\OmisePaymentService;
use App\Http\Services\StudentService;
use App\Models\Course;
use App\Models\CourseStudent;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * 教师创建账单
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            $request->validate([
                'course_id' => 'required|integer|min:1',
                'student_id' => 'required|integer|min:1',
                'amount' => 'required|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error($e->getMessage());
        }

        $teacherId = Auth::guard('teacher')->id();
        // 验证课程是否属于该教师
        $course = Course::where('id', $request->course_id)
            ->where('teacher_id', $teacherId)
            ->first();
        if (!$course) {
            return ApiResponse::error(lang('课程不存在'));
        }

        StudentService::checkStudentStatus($request->student_id);

        // 验证学生是否在该课程中
        $studentExists =CourseStudent::where('course_id', $request->course_id)->where('student_id', $request->student_id)->exists();
        if (!$studentExists) {
            return ApiResponse::error(lang('请先添加学生到此课程中在创建账单'));
        }
        // 验证是否已经存在账单
        $invoiceExists = Invoice::where('course_id', $request->course_id)
            ->where('student_id', $request->student_id)
            ->exists();
        if ($invoiceExists) {
            return ApiResponse::error(lang('已存在账单'));
        }
        // 创建账单
        Invoice::create([
            'course_id' => $request->course_id,
            'student_id' => $request->student_id,
            'teacher_id' => $teacherId,
            'amount' => $request->amount,
            'status' => Invoice::STATUS_PENDING,
        ]);
        return ApiResponse::success();

    }

    /**
     * 教师获取账单列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $teacherId = Auth::guard('teacher')->id();
        //增加学生id检索
        $studentId = $request->student_id ?? 0;
        //增加课程id检索
        $courseId = $request->course_id ?? 0;
        $where = [];
        if ($studentId) {
            $where[] = ['student_id', '=', $studentId];
        }
        if ($courseId) {
            $where[] = ['course_id', '=', $courseId];
        }
        $invoices = Invoice::where('teacher_id', $teacherId)
            ->where($where)
            ->with(['course:id,name', 'student:id,name'])
            ->orderBy('id', 'desc');
        $invoices = paginate($invoices);
        return ApiResponse::success($invoices);
    }

    /**
     * 教师发送账单
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function send($id)
    {
        $teacherId = Auth::guard('teacher')->id();

        $invoice = Invoice::where('id', $id)
            ->where('teacher_id', $teacherId)
            ->first();

        if (!$invoice) {
            return ApiResponse::error('账单不存在');
        }

        if ($invoice->status != Invoice::STATUS_PENDING) {
            return ApiResponse::error('只有待处理的账单可以发送');
        }
        StudentService::checkStudentStatus($invoice->student_id);

        // 更新账单状态为已发送
        $invoice->status = Invoice::STATUS_SENT;
        $invoice->sent_at = now();
        $invoice->save();

        return ApiResponse::success();

    }

    /**
     * 学生获取自己的账单列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function studentInvoices(Request $request)
    {
        $studentId = Auth::guard('student')->id();

        $invoices = Invoice::where('student_id', $studentId)
            ->where('status', '>=', Invoice::STATUS_SENT) // 只显示已发送的账单
            ->with(['course:id,name', 'teacher:id,name'])
            ->orderBy('id', 'desc');

        return ApiResponse::success(paginate($invoices));
    }

    /**
     * 学生支付账单
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(Request $request, $id)
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

            $omiseService = new OmisePaymentService();
            //先写死一个支付
            // 创建Omise AlipayPlus MPM支付
            $paymentResult = $omiseService->createAlipayPlusMpmPayment($invoice);

            return ApiResponse::success($paymentResult);
        } catch (\Exception $e) {
            Log::error('创建Omise支付失败', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error(lang('支付失败，请稍后再试'));
        }
    }

}
