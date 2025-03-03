<?php

namespace App\Filament\Resources;

use App\Enums\Priority;
use App\Filament\Resources\TodoResource\Pages;
use App\Models\ProjectCategory;
use App\Models\Todo;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
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
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('estimated_hours')
                                    ->required()
                                    ->step(0.5)
                                    ->suffix('hours')
                                    ->inputMode('decimal')
                                    ->placeholder('2.5')
                                    ->rule([
                                        'regex:/^
                                            \d+      # Whole number part
                                            ([,.]     # Decimal separator (either , or .)
                                            \d{1})?  # Exactly one decimal digit
                                        $/x',
                                    ])
                                    ->dehydrateStateUsing(function ($state) {
                                        // Normalize to dot and remove extra decimals
                                        $state = str_replace(',', '.', $state);
                                        $parts = explode('.', $state);

                                        // Keep only first decimal if multiple exist
                                        if (count($parts) > 1) {
                                            return $parts[0].'.'.substr($parts[1], 0, 1);
                                        }

                                        return $state;
                                    }),

                                Forms\Components\Select::make('priority')
                                    ->options(Priority::class)
                                    ->required()
                                    ->native(false)
                                    ->selectablePlaceholder(false),
                            ]),

                        Forms\Components\Grid::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_completed')
                                    ->label('Completed?')
                                    ->inline(false)
                                    ->offIcon('heroicon-o-x-mark')
                                    ->onIcon('heroicon-o-check')
                                    ->reactive()
                                    ->visibleOn(['view', 'edit'])
                                    ->afterStateUpdated(function ($get, $set) {
                                        $isCompleted = $get('is_completed');

                                        if ($isCompleted) {
                                            $set('completed_at', now());
                                        } else {
                                            $set('completed_at', null);
                                        }
                                    }),

                                Forms\Components\DatePicker::make('completed_at')
                                    ->label('Completed At')
                                    ->displayFormat('d-m-Y')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->reactive()
                                    ->visible(fn ($get) => $get('is_completed')),
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
                            ])
                            ->createOptionModalHeading('Create Project')
                            ->createOptionUsing(function (array $data): int {
                                return Filament::getTenant()->projects()->create($data)->getKey();
                            })
                            ->default(function () {
                                $tenant = Filament::getTenant();
                                $projects = $tenant->projects;

                                return $projects->count() === 1 ? $projects->first()->id : null;
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($set) {
                                $set('project_category_id', null);
                            }),

                        Forms\Components\Select::make('project_category_id')
                            ->relationship('category', 'name')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($get) => ! $get('project_id'))
                            ->options(function ($get) {
                                $projectId = $get('project_id');
                                if (! $projectId) {
                                    return [];
                                }

                                return \App\Models\ProjectCategory::where('project_id', $projectId)->pluck('name', 'id');
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\ColorPicker::make('color')
                                    ->required()
                                    ->regex('/^#([a-f0-9]{6}|[a-f0-9]{3})\b$/'),
                            ])
                            ->createOptionModalHeading('Create Project Category')
                            ->createOptionUsing(function (array $data, callable $get): int {
                                $projectId = $get('project_id');
                                $data['project_id'] = $projectId;

                                return ProjectCategory::create($data)->getKey();
                            }),

                        Forms\Components\Select::make('assigned_to')
                            ->relationship('assignedTo', 'name')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                $tenant = Filament::getTenant();

                                return $tenant->users()->pluck('name', 'id');
                            }),

                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Todo $record) => self::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Todo $record) => $record->description)
                    ->wrap(),

                Tables\Columns\TextColumn::make('project.name')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->badge()
                    ->color(fn (Todo $record) => Color::hex($record->category->color ?? '#ffffff'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned to')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-user')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created by')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-user')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->relationship('project', 'name')
                    ->label('Project'),
                Tables\Filters\SelectFilter::make('priority')
                    ->options(Priority::class),
                Tables\Filters\TernaryFilter::make('is_completed')
                    ->default(false),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                // Add action to mark todo as completed
                Tables\Actions\Action::make('mark-as-completed')
                    ->hiddenLabel()
                    ->icon('heroicon-o-check')
                    ->form([
                        Forms\Components\DatePicker::make('completed_at')
                            ->required()
                            ->label('Completed At')
                            ->default(now())
                            ->displayFormat('d-m-Y')
                            ->native(false)
                            ->closeOnDateSelection(),
                    ])
                    ->action(fn (Todo $record) => $record->update([
                        'is_completed' => true,
                        'completed_at' => now(),
                    ]))
                    ->visible(fn (Todo $record) => ! $record->is_completed),
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
            ])
            ->persistFiltersInSession();
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
            'view' => Pages\ViewTodo::route('/{record}'),
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
