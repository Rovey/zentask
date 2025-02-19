<?php

namespace App\Filament\Resources\TodoResource\Pages;

use App\Filament\Exports\TodoExporter;
use App\Filament\Resources\TodoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTodos extends ListRecords
{
    protected static string $resource = TodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ExportAction::make()
                ->exporter(TodoExporter::class)
                ->modifyQueryUsing(function (Builder $query, array $options) {
                    if ($options['start_date'] && $options['end_date']) {
                        $query->whereBetween('created_at', [$options['start_date'], $options['end_date']]);
                    } elseif ($options['start_date']) {
                        $query->whereDate('created_at', '>=', $options['start_date']);
                    } elseif ($options['end_date']) {
                        $query->whereDate('created_at', '<=', $options['end_date']);
                    }
                }),
        ];
    }
}
