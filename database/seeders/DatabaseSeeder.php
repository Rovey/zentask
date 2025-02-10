<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('mg64$UAQQ%asCp'),
            'email_verified_at' => now(),
        ]);

        $team = Team::create([
            'owner_id' => $user->id,
            'name' => 'Test Team',
        ]);

        $user->teams()->attach($team->id);

        Artisan::call('shield:generate --all --panel=admin');
        Artisan::call('shield:super-admin --tenant='.$team->id);
    }
}
