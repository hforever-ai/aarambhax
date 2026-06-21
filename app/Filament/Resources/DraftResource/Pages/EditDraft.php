<?php

namespace App\Filament\Resources\DraftResource\Pages;

use App\Filament\Resources\DraftResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDraft extends EditRecord
{
    protected static string $resource = DraftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
