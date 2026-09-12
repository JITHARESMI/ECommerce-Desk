<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
class AppServiceProvider extends ServiceProvider {
 public function boot():void {
  RateLimiter::for('login',fn($r)=>Limit::perMinute(5)->by($r->ip()));
  RateLimiter::for('admin',fn($r)=>Limit::perMinute(120)->by($r->user()?->id?:$r->ip()));
  RateLimiter::for('ai',fn($r)=>Limit::perMinute(5)->by($r->user()?->id?:$r->ip()));
 }
}

