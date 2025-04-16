<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseStudent extends Model
{
    use HasFactory;

    public $timestamps=false;
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }


    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
