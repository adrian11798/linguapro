<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'video_url',
        'material_url',
        'order',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /** lessons -> progress (1:N) */
    public function progress()
    {
        return $this->hasMany(Progress::class);
    }
}
