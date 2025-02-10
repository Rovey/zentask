<?php

namespace App\Filament\Resources\TeamResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('User Email')
                            ->required()
                            ->email()
                            ->exists('users', 'email')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data) {
                        $user = User::where('email', $data['email'])->firstOrFail();
                        if (! $this->getRelationship()->get()->contains($user)) {
                            $this->getRelationship()->attach($user);
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('User Already Attached')
                                ->body('The user is already attached to the team.')
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->visible(fn (Model $record) => $record->user_id !== $this->getOwnerId()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }

    protected function getOwnerId(): int
    {
        return $this->getRelationship()->getParent()->user_id;
    }
}
