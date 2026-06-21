<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HearingResource\Pages;
use App\Filament\Resources\HearingResource\RelationManagers;
use App\Models\Hearing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HearingResource extends Resource
{
    protected static ?string $model = Hearing::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'App';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('case_id')
                    ->relationship('case', 'title')
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\TextInput::make('time'),
                Forms\Components\TextInput::make('court'),
                Forms\Components\TextInput::make('purpose'),
                Forms\Components\Textarea::make('outcome')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('next_date'),
                Forms\Components\DateTimePicker::make('reminded_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('case.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('time'),
                Tables\Columns\TextColumn::make('court')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->searchable(),
                Tables\Columns\TextColumn::make('next_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reminded_at')
                    ->dateTime()
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListHearings::route('/'),
            'create' => Pages\CreateHearing::route('/create'),
            'edit' => Pages\EditHearing::route('/{record}/edit'),
        ];
    }
}
