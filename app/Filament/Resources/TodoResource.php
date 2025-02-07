<?php

namespace App\Filament\Resources;

use App\Enums\Priority;
use App\Filament\Resources\TodoResource\Pages;
use App\Models\Todo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TodoResource extends Resource
{
    protected static ?string $model = Todo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->autofocus()
                    ->maxLength(255)
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->placeholder('Enter a description if needed.')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('estimated_hours')
                    ->required()
                    ->numeric()
                    ->step(0.1)
                    ->placeholder('e.g., 2.5 hours')
                    ->rule('regex:/^\d+(\.\d{1,2})?$/'),
                Forms\Components\Select::make('priority')
                    ->options(fn () => array_map('ucwords', array_column(Priority::cases(), 'value', 'name')))
                    ->required(),
                Forms\Components\Toggle::make('is_completed')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->required()
                    ->relationship('user', 'name')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estimated_hours')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable(),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
