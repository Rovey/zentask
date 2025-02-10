<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        if ($this->record->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to edit this team.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
