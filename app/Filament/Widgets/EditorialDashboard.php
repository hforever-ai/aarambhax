<?php

namespace App\Filament\Widgets;

use App\Models\Faq;
use App\Models\Post;
use App\Models\PostPipelineRun;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EditorialDashboard extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $awaitingReview = PostPipelineRun::whereIn('state', PostPipelineRun::REVIEW_STATES)->count();
        $inProgress = PostPipelineRun::whereNotIn('state', ['published', 'archived'])->count();
        $publishedThisMonth = Post::published()->where('published_at', '>=', now()->startOfMonth())->count();
        $totalPosts = Post::count();
        $totalFaqs = Faq::count();

        return [
            Stat::make('Awaiting your review', $awaitingReview)
                ->description($awaitingReview > 0 ? 'Open the Posts list and look for "review" pipeline state' : 'Nothing waiting — clear queue')
                ->color($awaitingReview > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-eye'),

            Stat::make('Pipelines in progress', $inProgress)
                ->description('Drafts not yet published')
                ->color('primary')
                ->icon('heroicon-o-arrow-path'),

            Stat::make('Published this month', $publishedThisMonth)
                ->description("Total posts: {$totalPosts}")
                ->color('success')
                ->icon('heroicon-o-rocket-launch'),

            Stat::make('FAQs', $totalFaqs)
                ->description('Knowledge base entries')
                ->color('gray')
                ->icon('heroicon-o-question-mark-circle'),
        ];
    }
}
