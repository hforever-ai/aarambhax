<?php

namespace App\Filament\Resources\CaseRecordResource\Pages;

use App\Filament\Resources\CaseRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCaseRecord extends EditRecord
{
    protected static string $resource = CaseRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
