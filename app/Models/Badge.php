<?php

namespace STS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'image_path',
        'rules'
    ];

    protected $casts = [
        'rules' => 'array'
    ];

    /**
     * Get the users that have this badge.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('awarded_at')
            ->withTimestamps();
    }
} 