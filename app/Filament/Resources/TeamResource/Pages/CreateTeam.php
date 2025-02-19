<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    public function afterCreate()
    {
        $this->record->users()->attach(Auth::id());
    }
}
