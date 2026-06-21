<?php

namespace App\Filament\Resources\NewsletterBroadcastResource\Pages;

use App\Filament\Resources\NewsletterBroadcastResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNewsletterBroadcasts extends ListRecords
{
    protected static string $resource = NewsletterBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
