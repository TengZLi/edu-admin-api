<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YearMonthRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $yearMonth, Closure $fail): void
    {
        if( strlen($yearMonth) <> 6){
            $fail(lang('课程年份月必须是6位数字'));
        }
        //获取当前年月
        $currentYear = date('Ym');
        if($currentYear > $yearMonth){
            $fail(lang('课程年份月份不能小于当前年份月份'));
        }
    }
}
