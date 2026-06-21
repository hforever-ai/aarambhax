<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Models\PostPipelineRun;
use App\Pipelines\PipelineOrchestrator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Editorial';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')
                ->schema([
                    Forms\Components\TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\TextInput::make('slug')->required()->maxLength(255),
                    Forms\Components\Select::make('language')
                        ->options(['en' => 'English', 'hi' => 'Hindi (Devanagari)'])
                        ->default('en')->required(),
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name_en')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('archetype')
                        ->options([
                            'section_mapping' => 'Section Mapping',
                            'drafting_walkthrough' => 'Drafting Walkthrough',
                            'format_sample' => 'Format Sample',
                            'comparison' => 'Comparison',
                            'checklist' => 'Checklist',
                            'case_study' => 'Case Study',
                            'general' => 'General',
                        ])
                        ->default('general'),
                    Forms\Components\Select::make('author_id')
                        ->relationship('author', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                ])->columns(2),

            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\TextInput::make('subtitle')->maxLength(500)->columnSpanFull(),
                    Forms\Components\Textarea::make('excerpt')->rows(2)->columnSpanFull()->maxLength(500),
                    Forms\Components\Textarea::make('body')
                        ->required()
                        ->rows(20)
                        ->columnSpanFull()
                        ->helperText('Markdown. Cite as "BNSS §482". Use [VERIFY] for unsure citations.'),
                ]),

            Forms\Components\Section::make('SEO')
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('meta_title')->maxLength(70)->helperText('≤60 chars ideal'),
                    Forms\Components\TextInput::make('meta_description')->maxLength(160),
                    Forms\Components\TextInput::make('canonical_url')->maxLength(500),
                    Forms\Components\TextInput::make('hero_image_url')->maxLength(500)
                        ->helperText('Path or URL — generate via scripts/generate_images.py blog'),
                    Forms\Components\TextInput::make('hero_image_alt')->maxLength(255),
                    Forms\Components\TextInput::make('og_image_url')->maxLength(500),
                ])->columns(2),

            Forms\Components\Section::make('Publication')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'review' => 'Review',
                            'scheduled' => 'Scheduled',
                            'published' => 'Published',
                            'archived' => 'Archived',
                        ])
                        ->default('draft')
                        ->required(),
                    Forms\Components\DateTimePicker::make('published_at'),
                    Forms\Components\DateTimePicker::make('scheduled_at'),
                    Forms\Components\TextInput::make('reading_time_minutes')->numeric(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\BadgeColumn::make('language')
                    ->colors(['primary' => 'en', 'success' => 'hi']),
                Tables\Columns\TextColumn::make('category.name_en')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => ['review', 'scheduled'],
                        'success' => 'published',
                        'danger' => 'archived',
                    ]),
                Tables\Columns\TextColumn::make('currentPipelineRun.state')
                    ->label('Pipeline')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft', 'review' => 'Review',
                        'scheduled' => 'Scheduled', 'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
                Tables\Filters\SelectFilter::make('language')
                    ->options(['en' => 'English', 'hi' => 'Hindi']),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Action::make('startPipeline')
                        ->label('Start pipeline')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->visible(fn (Post $r) => ! $r->current_pipeline_run_id)
                        ->action(function (Post $record) {
                            PipelineOrchestrator::make()->startRun($record);
                            Notification::make()->title('Pipeline started')->success()->send();
                        }),

                    Action::make('generateOutline')
                        ->label('Generate outline (Gemini)')
                        ->icon('heroicon-o-sparkles')
                        ->color('warning')
                        ->visible(fn (Post $r) => $r->currentPipelineRun?->state === 'idea' || $r->currentPipelineRun?->state === 'outline_draft')
                        ->action(function (Post $record) {
                            try {
                                PipelineOrchestrator::make()->generateOutline($record->currentPipelineRun, [
                                    'topic' => $record->title,
                                    'archetype' => $record->archetype,
                                    'target_words' => 1800,
                                    'languages' => $record->language,
                                ]);
                                Notification::make()->title('Outline generated')->success()->send();
                            } catch (\Throwable $e) {
                                Notification::make()->title('Outline failed')->body($e->getMessage())->danger()->send();
                            }
                        }),

                    Action::make('approveOutline')
                        ->label('Approve outline')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (Post $r) => $r->currentPipelineRun?->state === 'outline_review')
                        ->requiresConfirmation()
                        ->action(function (Post $record) {
                            PipelineOrchestrator::make()->approveOutline($record->currentPipelineRun);
                            Notification::make()->title('Outline approved')->success()->send();
                        }),

                    Action::make('generateDraft')
                        ->label('Generate draft (Gemini Pro)')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->visible(fn (Post $r) => in_array($r->currentPipelineRun?->state, ['outline_approved', 'draft_en']))
                        ->action(function (Post $record) {
                            try {
                                PipelineOrchestrator::make()->generateDraftEn($record->currentPipelineRun);
                                Notification::make()->title('Draft generated')->success()->send();
                            } catch (\Throwable $e) {
                                Notification::make()->title('Draft failed')->body($e->getMessage())->danger()->send();
                            }
                        }),

                    Action::make('approveEn')
                        ->label('Approve English')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (Post $r) => $r->currentPipelineRun?->state === 'en_review')
                        ->form([
                            Forms\Components\Toggle::make('bilingual')->label('Translate to Hindi too')->default(false),
                        ])
                        ->action(function (Post $record, array $data) {
                            PipelineOrchestrator::make()->approveEn($record->currentPipelineRun, (bool) ($data['bilingual'] ?? false));
                            Notification::make()->title('English approved')->success()->send();
                        }),

                    Action::make('translateHi')
                        ->label('Translate to Hindi (Gemini Pro)')
                        ->icon('heroicon-o-language')
                        ->visible(fn (Post $r) => in_array($r->currentPipelineRun?->state, ['en_approved', 'draft_hi']))
                        ->action(function (Post $record) {
                            try {
                                PipelineOrchestrator::make()->translateHi($record->currentPipelineRun);
                                Notification::make()->title('Hindi translation generated')->success()->send();
                            } catch (\Throwable $e) {
                                Notification::make()->title('Translation failed')->body($e->getMessage())->danger()->send();
                            }
                        }),

                    Action::make('publish')
                        ->label('Approve & Publish')
                        ->icon('heroicon-o-rocket-launch')
                        ->color('success')
                        ->visible(fn (Post $r) => in_array($r->currentPipelineRun?->state, ['both_approved', 'assets_ready']))
                        ->requiresConfirmation()
                        ->action(function (Post $record) {
                            PipelineOrchestrator::make()->publish($record->currentPipelineRun);
                            Notification::make()->title('Post published — sitemap updated')->success()->send();
                        }),

                    Action::make('unpublish')
                        ->label('Unpublish')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->visible(fn (Post $r) => $r->status === 'published')
                        ->requiresConfirmation()
                        ->action(function (Post $record) {
                            if ($record->currentPipelineRun) {
                                PipelineOrchestrator::make()->unpublish($record->currentPipelineRun);
                            } else {
                                $record->update(['status' => 'draft']);
                            }
                            Notification::make()->title('Post unpublished')->warning()->send();
                        }),
                ])->label('Pipeline actions')->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['category', 'author', 'currentPipelineRun']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
