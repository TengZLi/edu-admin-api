<?php

use App\Models\Course;
use \Illuminate\Database\Eloquent\Builder;
use  \App\Exceptions\ApiException;
if (!function_exists('lang')) {
    function lang($key) {
        return $key;
    }
}

function paginate(Builder $builder, $column = ['*'])
{
        $page = request()->get('page', 1);
        $perPage = request()->get('per_page', 15);
        if($perPage > 100){
            throw new ApiException(lang('分页大小不能超过100'));
        }
        if($page > 10000){
            throw new ApiException(lang('分页页数不能超过10000'));
        }

        $data =  $builder->paginate($perPage, $column, 'page', $page);
        return [
            'data' => $data->items(),
            'total' => $data->total(),
//            'per_page' => $data->perPage(),
//            'current_page' => $data->currentPage(),
        ];
}
// 可以在此处添加更多的公共函数
