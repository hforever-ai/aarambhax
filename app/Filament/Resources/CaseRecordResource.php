<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaseRecordResource\Pages;
use App\Filament\Resources\CaseRecordResource\RelationManagers;
use App\Models\CaseRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CaseRecordResource extends Resource
{
    protected static ?string $model = CaseRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'App';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name'),
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\TextInput::make('forum')
                    ->required(),
                Forms\Components\TextInput::make('court_name'),
                Forms\Components\TextInput::make('case_no'),
                Forms\Components\TextInput::make('jurisdiction'),
                Forms\Components\TextInput::make('category'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('opposing_party'),
                Forms\Components\TextInput::make('khasra_no'),
                Forms\Components\TextInput::make('khata_no'),
                Forms\Components\TextInput::make('khatauni_no'),
                Forms\Components\TextInput::make('gram'),
                Forms\Components\TextInput::make('tehsil'),
                Forms\Components\TextInput::make('jila'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('forum')
                    ->searchable(),
                Tables\Columns\TextColumn::make('court_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('case_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jurisdiction')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('opposing_party')
                    ->searchable(),
                Tables\Columns\TextColumn::make('khasra_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('khata_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('khatauni_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gram')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tehsil')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jila')
                    ->searchable(),
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
            'index' => Pages\ListCaseRecords::route('/'),
            'create' => Pages\CreateCaseRecord::route('/create'),
            'edit' => Pages\EditCaseRecord::route('/{record}/edit'),
        ];
    }
}
