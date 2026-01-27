<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $primaryKey = 'audit_id';

    protected $fillable = [
        'actor_user_id',
        'action_type',
        'entity_type',
        'before_json',
        'after_json',
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'user_id');
    }

    public static function log(string $action, string $entity, $before = null, $after = null)
    {
        return self::create([
            'actor_user_id' => auth()->id(),
            'action_type' => $action,
            'entity_type' => $entity,
            'before_json' => $before,
            'after_json' => $after,
        ]);
    }
}
