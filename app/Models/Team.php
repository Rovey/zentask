<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    public function members()
    {
        return $this->belongsToMany(User::class);
    }

    public function todos()
    {
        return $this->hasMany(Todo::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(\App\Models\Role::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
