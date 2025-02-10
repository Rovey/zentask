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

    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Team Information')
                    ->description('Manage team details and ownership')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->placeholder('Team Alpha')
                            ->columnSpanFull()
                            ->hint('Display name for your team'),

                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Team::whereHas('users', fn ($query) => $query->where('team_user.user_id', Auth::id())))
            ->recordUrl(fn ($record) => $record->user_id === Auth::id()
                        ? self::getUrl('edit', ['record' => $record])
                        : null
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->user_id === Auth::id()
                        ? 'Your Team'
                        : null),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->badge()
                    ->color(fn ($record) => $record->user_id === Auth::id()
                        ? 'success'
                        : 'gray')
                    ->icon(fn ($record) => $record->user_id === Auth::id()
                        ? 'heroicon-o-check-circle'
                        : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignRight(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Members')
                    ->counts('users')
                    ->badge()
                    ->color('info')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('owned')
                    ->label('My Teams')
                    ->query(fn ($query) => $query->where('user_id', Auth::id())),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit Team')
                    ->visible(fn ($record) => $record->user_id === Auth::id()),

                Tables\Actions\Action::make('leave')
                    ->label('Leave')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->color('danger')
                    ->action(function ($record) {
                        $record->users()->detach(Auth::id());

                        /** @disregard P1013 Undefined method */
                        if (Auth::user()->teams()->count() === 0) {
                            return redirect()->route('filament.resources.teams.create');
                        }

                        return redirect()->route('filament.resources.teams.index');
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Leave Team')
                    ->modalDescription("Are you sure you want to leave this team? You'll need to be re-invited to regain access.")
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->visible(fn ($record) => $record->user_id !== Auth::id()),
            ])
            ->bulkActions([
                //
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
