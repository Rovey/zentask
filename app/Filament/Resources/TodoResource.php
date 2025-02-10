<?php

namespace App\Filament\Resources;

use App\Enums\Priority;
use App\Filament\Resources\TodoResource\Pages;
use App\Models\Todo;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TodoResource extends Resource
{
    protected static ?string $model = Todo::class;

    protected static bool $isScopedToTenant = true;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->autofocus()
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Describe the task...')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make()
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('estimated_hours')
                                    ->numeric()
                                    ->required()
                                    ->step(0.5)
                                    ->suffix('hours')
                                    ->inputMode('decimal')
                                    ->placeholder('2.5'),

                                Forms\Components\Select::make('priority')
                                    ->options(Priority::class)
                                    ->required()
                                    ->native(false)
                                    ->selectablePlaceholder(false),

                                Forms\Components\Toggle::make('is_completed')
                                    ->label('Completed?')
                                    ->inline(false)
                                    ->offIcon('heroicon-o-x-mark')
                                    ->onIcon('heroicon-o-check')
                                    ->visibleOn(['edit']),
                            ]),
                    ]),

                Forms\Components\Section::make('Assignment')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Hidden::make('team_id')
                                    ->default(Filament::getTenant()->id),
                            ])
                            ->createOptionAction(
                                fn ($action) => $action->label('Create New Project')),

                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Todo $record) => $record->description)
                    ->wrap(),

                Tables\Columns\TextColumn::make('project.name')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_hours')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' hrs')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Priority::HIGH->value => 'danger',
                        Priority::MEDIUM->value => 'warning',
                        Priority::LOW->value => 'success',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Assigned To')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('priority')
                    ->options(Priority::class),
                Tables\Filters\TernaryFilter::make('is_completed'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTodos::route('/'),
            'create' => Pages\CreateTodo::route('/create'),
            'edit' => Pages\EditTodo::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
