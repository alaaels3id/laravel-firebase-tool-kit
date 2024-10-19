<?php

use Alaaelsaid\LaravelFirebaseToolKit\Facade\FirebaseProcess;
use Illuminate\Support\ServiceProvider;

class FirebaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/firebase.php' => config_path('firebase.php'),
        ],'firebase');
    }

    public function register(): void
    {
        $this->app->singleton('Firebase', fn() => new FirebaseProcess());

        $this->mergeConfigFrom(__DIR__ . '/../../config/firebase.php','firebase');
    }
}