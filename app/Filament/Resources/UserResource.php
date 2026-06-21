<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Mail\AccountApproved;
use App\Mail\AccountRejected;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Aarambh Legal';

    protected static ?int $navigationSort = -1;

    /** Sidebar badge: pending-approval count. */
    public static function getNavigationBadge(): ?string
    {
        $n = User::where('status', User::STATUS_PENDING)->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identity')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('email')->email()->required(),
                        Forms\Components\TextInput::make('bar_enrolment_no'),
                        Forms\Components\DateTimePicker::make('email_verified_at'),
                    ]),
                Forms\Components\Section::make('Approval & access')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                User::STATUS_PENDING => 'Pending',
                                User::STATUS_APPROVED => 'Approved',
                                User::STATUS_REJECTED => 'Rejected',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_admin')->label('Admin (can access this panel)'),
                        Forms\Components\DateTimePicker::make('approved_at'),
                        Forms\Components\TextInput::make('approved_by_user_id')->numeric()->label('Approved by user ID'),
                    ]),
                Forms\Components\Section::make('Telegram (optional)')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('telegram_chat_id'),
                        Forms\Components\TextInput::make('telegram_pairing_code'),
                        Forms\Components\Toggle::make('telegram_alerts_enabled'),
                    ]),
                Forms\Components\Section::make('Chambers profile')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('signature_block_en')->columnSpanFull(),
                        Forms\Components\Textarea::make('signature_block_hi')->columnSpanFull(),
                        Forms\Components\Textarea::make('chamber_address')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => User::STATUS_PENDING,
                        'success' => User::STATUS_APPROVED,
                        'danger' => User::STATUS_REJECTED,
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_admin')->boolean()->label('Admin'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        User::STATUS_PENDING => 'Pending',
                        User::STATUS_APPROVED => 'Approved',
                        User::STATUS_REJECTED => 'Rejected',
                    ])
                    ->default(User::STATUS_PENDING),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (User $record) => $record->status !== User::STATUS_APPROVED)
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record) => "Approve {$record->name} ({$record->email})? They'll be able to use all features immediately.")
                    ->action(function (User $record) {
                        $record->update([
                            'status' => User::STATUS_APPROVED,
                            'approved_at' => now(),
                            'approved_by_user_id' => auth()->id(),
                        ]);
                        try {
                            Mail::to($record->email)->send(new AccountApproved($record));
                        } catch (\Throwable $e) {
                            Log::warning('approve: email failed', ['user' => $record->id, 'err' => $e->getMessage()]);
                        }
                        Notification::make()
                            ->title("Approved {$record->name}")
                            ->body('They can now use all features. Notification email sent.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (User $record) => $record->status !== User::STATUS_REJECTED && ! $record->isAdmin())
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record) => "Reject {$record->name} ({$record->email})? They'll be unable to use any features.")
                    ->action(function (User $record) {
                        $record->update([
                            'status' => User::STATUS_REJECTED,
                            'approved_at' => null,
                            'approved_by_user_id' => null,
                        ]);
                        try {
                            Mail::to($record->email)->send(new AccountRejected($record));
                        } catch (\Throwable $e) {
                            Log::warning('reject: email failed', ['user' => $record->id, 'err' => $e->getMessage()]);
                        }
                        Notification::make()
                            ->title("Rejected {$record->name}")
                            ->body('Notification email sent.')
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
