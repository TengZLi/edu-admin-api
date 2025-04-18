<?php
namespace App\Http\Controllers;
use App\Http\ApiResponse;
use App\Http\Services\AuthService;
use App\Http\Services\StudentService;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Spatie\FlareClient\Api;

class StudentController extends Controller
{
    public function list()
    {
        return ApiResponse::success(StudentService::teacherStudentList(Auth::id()));
    }
}
