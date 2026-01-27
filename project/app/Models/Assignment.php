<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $primaryKey = 'assignment_id';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'due_at',
        'max_points',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'assignment_id', 'assignment_id');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('due_at', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('due_at', '<=', now());
    }
}
