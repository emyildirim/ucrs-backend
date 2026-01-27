<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $primaryKey = 'role_id';

    protected $fillable = [
        'name',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }

    public function isAdmin(): bool
    {
        return $this->name === 'Admin';
    }

    public function isInstructor(): bool
    {
        return $this->name === 'Instructor';
    }

    public function isStudent(): bool
    {
        return $this->name === 'Student';
    }
}
