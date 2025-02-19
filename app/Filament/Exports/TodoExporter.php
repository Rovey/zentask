<?php

namespace App\Filament\Exports;

use App\Models\Todo;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;

class TodoExporter extends Exporter
{
    protected static ?string $model = Todo::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('#'),
            ExportColumn::make('title')
                ->label('Title'),
            ExportColumn::make('description')
                ->label('Description'),
            ExportColumn::make('estimated_hours')
                ->label('Est. Hours'),
            ExportColumn::make('priority')
                ->label('Priority')
                ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ExportColumn::make('user.name')
                ->label('User'),
            ExportColumn::make('project.name')
                ->label('Project'),
            ExportColumn::make('is_completed')
                ->label('Completed?')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
            ExportColumn::make('completed_at')
                ->label('Completed At')
                ->formatStateUsing(fn (?string $state): string => $state ? date('d-m-Y', strtotime($state)) : ''),
            ExportColumn::make('created_at')
                ->label('Created At')
                ->enabledByDefault(false)
                ->formatStateUsing(fn (?string $state): string => $state ? date('d-m-Y', strtotime($state)) : ''),
            ExportColumn::make('updated_at')
                ->label('Updated At')
                ->enabledByDefault(false)
                ->formatStateUsing(fn (?string $state): string => $state ? date('d-m-Y', strtotime($state)) : ''),
            ExportColumn::make('deleted_at')
                ->label('Deleted At')
                ->enabledByDefault(false)
                ->formatStateUsing(fn (?string $state): string => $state ? date('d-m-Y', strtotime($state)) : ''),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your todo export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            DatePicker::make('start_date')
                ->label('Start Date')
                ->native(false)
                ->closeOnDateSelection(true),
            DatePicker::make('end_date')
                ->label('End Date')
                ->native(false)
                ->closeOnDateSelection(true),
        ];
    }
}
