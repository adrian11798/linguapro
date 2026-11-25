<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'language',
        'level',
        'image',
        'status',
        'teacher_id',
    ];

    /** courses -> lessons (1:N) */
    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    /** courses -> enrollments (1:N) */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /** courses -> live_classes (1:N) */
    public function liveClasses()
    {
        return $this->hasMany(LiveClass::class);
    }

    /** courses -> assignments (1:N) */
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
