<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    // 支付状态常量
    const STATUS_PENDING = 0;  // 待处理
    const STATUS_SENT = 1;     // 已发送
    const STATUS_PAID = 2;     // 已支付

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'transaction_id',
        'amount',
        'status',
        'paid_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'paid_at' => 'datetime',
    ];

    /**
     * 获取关联的账单
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * 表明模型不使用updated_at时间戳
     *
     * @var bool
     */
    public $timestamps = false;
}
