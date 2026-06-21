<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DraftResource\Pages;
use App\Filament\Resources\DraftResource\RelationManagers;
use App\Models\Draft;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DraftResource extends Resource
{
    protected static ?string $model = Draft::class;

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
                    ->relationship('case', 'title'),
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\TextInput::make('forum')
                    ->required(),
                Forms\Components\TextInput::make('category')
                    ->required(),
                Forms\Components\TextInput::make('draft_type')
                    ->required(),
                Forms\Components\TextInput::make('language')
                    ->required(),
                Forms\Components\Textarea::make('context_facts')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('context_legal')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('context_user_prefs')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('current_content_md')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('current_content_html')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required(),
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
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('forum')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\TextColumn::make('draft_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('language')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
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
            'index' => Pages\ListDrafts::route('/'),
            'create' => Pages\CreateDraft::route('/create'),
            'edit' => Pages\EditDraft::route('/{record}/edit'),
        ];
    }
}
