<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\ChecklistDocument;
use App\Models\ChecklistItem;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Trip;
use App\Policies\ActivityPolicy;
use App\Policies\ChecklistDocumentPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\TripPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Trip::class => TripPolicy::class,
        Activity::class => ActivityPolicy::class,
        Expense::class => ExpensePolicy::class,
        Document::class => DocumentPolicy::class,
        ChecklistItem::class => ChecklistDocumentPolicy::class,
        ChecklistDocument::class => ChecklistDocumentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
