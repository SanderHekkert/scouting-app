<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\InfoNoteController;
use App\Http\Controllers\LeaderController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PodController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskItemController;
use App\Http\Controllers\TipperTopperOpkomstController;
use App\Http\Controllers\YearThemeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/jaar-thema', YearThemeController::class)->name('jaar-thema');
    Route::get('/tipper-topper-opkomst', TipperTopperOpkomstController::class)->name('tipper-topper-opkomst.index');
    Route::resource('events', EventController::class)->except(['create', 'show', 'edit']);
    Route::patch('/members/{member}/tipper-topper-opkomst', [MemberController::class, 'updateTipperTopperOpkomst'])->name('members.tipper-topper-opkomst');
    Route::get('/members/bijzonderheden', [MemberController::class, 'indexBijzonderheden'])->name('members.bijzonderheden');
    Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::get('/leaders/{leader}', [LeaderController::class, 'show'])->name('leaders.show');
    Route::resource('members', MemberController::class)->except(['create', 'show', 'edit']);
    Route::resource('leaders', LeaderController::class)->except(['create', 'show', 'edit']);
    Route::resource('pods', PodController::class)->except(['create', 'show', 'edit']);
    Route::post('/pods/{pod}/members', [PodController::class, 'addMember'])->name('pods.members.store');
    Route::delete('/pod-memberships/{podMembership}', [PodController::class, 'removeMember'])->name('pods.members.destroy');
    Route::resource('info-notes', InfoNoteController::class)->except(['create', 'show', 'edit']);
    Route::resource('task-items', TaskItemController::class)->except(['create', 'show', 'edit']);
    Route::post('/task-categories', [TaskItemController::class, 'storeCategory'])->name('task-categories.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
