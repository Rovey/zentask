<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function todos()
    {
        return $this->hasMany(Todo::class);
    }

    public function categories()
    {
        return $this->hasMany(ProjectCategory::class);
    }
}
