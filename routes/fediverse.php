<?php

use DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse\DashboardController;
use DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse\DiscoverController;
use DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse\FollowingController;
use DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse\InboxController;
use DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse\InteractController;
use DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse\OutboxController;
use DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse\ProfileController;
use DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse\TimelineController;
use Illuminate\Support\Facades\Route;

Route::get(uri: '/', action: DashboardController::class)->name(name: 'dashboard');

Route::get(uri: '/timeline', action: TimelineController::class)->name(name: 'timeline');

Route::get(uri: '/inbox', action: InboxController::class)->name(name: 'inbox');

Route::get(uri: '/following', action: FollowingController::class)->name(name: 'following');

Route::get(uri: '/discover', action: [DiscoverController::class, 'index'])->name(name: 'discover');
Route::post(uri: '/discover/resolve', action: [DiscoverController::class, 'resolve'])->name(name: 'discover.resolve');

Route::post(uri: '/follow', action: [InteractController::class, 'follow'])->name(name: 'follow');
Route::post(uri: '/unfollow', action: [InteractController::class, 'unfollow'])->name(name: 'unfollow');
Route::post(uri: '/like', action: [InteractController::class, 'like'])->name(name: 'like');
Route::post(uri: '/boost', action: [InteractController::class, 'boost'])->name(name: 'boost');
Route::post(uri: '/reply', action: [InteractController::class, 'reply'])->name(name: 'reply');

Route::get(uri: '/outbox', action: OutboxController::class)->name(name: 'outbox');

Route::get(uri: '/profile', action: [ProfileController::class, 'edit'])->name(name: 'profile');
Route::post(uri: '/profile', action: [ProfileController::class, 'update'])->name(name: 'profile.update');
