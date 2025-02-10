<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    // protected static bool $isScopedToTenant = true;

    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Team::whereHas('users', function ($query) {
                $query->where('id', Auth::id());
            }))
            ->recordUrl(fn ($record) => $record->user_id === Auth::id()
                ? TeamResource::getUrl('edit', ['record' => $record])
                : null
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->user_id === Auth::id()),
                Tables\Actions\Action::make('leave')
                    ->label('Leave Team')
                    ->action(function ($record) {
                        $record->users()->detach(Auth::id());

                        // Check if the user has any teams left
                        /** @disregard P1013 Undefined method */
                        $userTeamsCount = Auth::user()->teams()->count();

                        // Redirect to the appropriate page
                        if ($userTeamsCount > 0) {
                            return redirect()->route('filament.resources.teams.index');
                        } else {
                            return redirect()->route('filament.resources.teams.create');
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->user_id !== Auth::id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'users' => TeamResource\RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
