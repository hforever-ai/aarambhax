<?php

namespace App\Filament\Resources\NewsletterBroadcastResource\Pages;

use App\Filament\Resources\NewsletterBroadcastResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNewsletterBroadcast extends EditRecord
{
    protected static string $resource = NewsletterBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
