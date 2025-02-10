<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'team_user');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(\App\Models\Role::class);
    }
}
