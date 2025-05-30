<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamUser extends Model
{
    protected $table = 'team_user';

    protected $fillable = ['team_id', 'user_id'];

    public $timestamps = false;
}
