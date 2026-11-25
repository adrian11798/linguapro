<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * El correo de "¿Olvidaste tu contraseña?" apunta a la pantalla del
     * frontend, no a una página de Laravel.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = rtrim(config('linguapro.frontend_url'), '/')
            .'/reset-password?token='.$token.'&email='.urlencode($this->email);

        $this->notify(new \App\Notifications\ResetPasswordNotification($url));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /** users -> enrollments (1:N) */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /** users -> progress (1:N) */
    public function progress()
    {
        return $this->hasMany(Progress::class);
    }

    /** Cursos que imparte (si role = teacher) */
    public function coursesTaught()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }
}
