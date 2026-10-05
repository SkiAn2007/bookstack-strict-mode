<?php

namespace BookStack\App\Providers;

use BookStack\Util\MigrationSqlMode;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SocialiteProviders\Azure\AzureExtendSocialite;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\GitLab\GitLabExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Okta\OktaExtendSocialite;
use SocialiteProviders\Twitch\TwitchExtendSocialite;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, string>>
     */
    protected $listen = [
        SocialiteWasCalled::class => [
            AzureExtendSocialite::class . '@handle',
            OktaExtendSocialite::class . '@handle',
            GitLabExtendSocialite::class . '@handle',
            TwitchExtendSocialite::class . '@handle',
            DiscordExtendSocialite::class . '@handle',
        ],
        MigrationsStarted::class => [
            MigrationSqlMode::class . '@onMigrationsStarted',
        ],
        MigrationsEnded::class => [
            MigrationSqlMode::class . '@onMigrationsEnded',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * Overrides the registration of Laravel's default email verification system
     */
    protected function configureEmailVerification(): void
    {
        //
    }
}
