<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::enablePasswordGrant();

        if (env('APP_ENV') === 'local' && env('APP_DEBUG') === true) {
            DB::listen(function ($query) {
                $sql = $query->sql;
                // 记录 SQL 查询语句和执行时间

                // 记录 SQL 查询绑定参数
                if (!empty($query->bindings)) {
                    $bindings = $query->bindings; // 确保 bindings 是一个数组
                    $sql = preg_replace_callback(
                        '/\?/',
                        function () use (&$bindings) {
                            return array_shift($bindings); // 安全地从数组中移除元素
                        },
                        $sql
                    );

                }
                Log::debug("[{$query->time}ms]{$sql}");

            });
        }
    }
}
