<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Team;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register team';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                // ...
            ]);
    }

    protected function handleRegistration(array $data): Team
    {
        /** @disregard P1013 Undefined method */
        $data['user_id'] = auth()->id();
        $team = Team::create($data);

        /** @disregard P1013 Undefined method */
        $team->members()->attach(auth()->user());

        return $team;
    }
}
