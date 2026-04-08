<?php

use App\Http\Controllers\AdminPushNotificationController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminUserInvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\InfoNoteController;
use App\Http\Controllers\LeaderController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PodController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SectionPermissionController;
use App\Http\Controllers\TaskItemController;
use App\Http\Controllers\TipperTopperOpkomstController;
use App\Http\Controllers\YearThemeController;
use App\Models\UserSectionRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/', function () {
    return to_route('dashboard');
});

Route::middleware(['auth', 'section.role:admin'])->group(function () {
    Route::get('/admin/rollen', [AdminRoleController::class, 'index'])->name('admin.roles.index');
    Route::patch('/admin/rollen/{user}', [AdminRoleController::class, 'update'])->name('admin.roles.update');
    Route::patch('/admin/rollen/{user}/basis', [AdminRoleController::class, 'updateBasics'])->name('admin.roles.update-basics');
});

Route::middleware(['auth', 'verified', 'has.role', 'section.role:admin,bestuurslid'])->group(function () {
    Route::get('/admin/gebruikers', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/gebruikers/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::patch('/admin/gebruikers/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/gebruikers/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/admin/gebruikers/uitnodigen', [AdminUserInvitationController::class, 'store'])->name('admin.users.invite');
    Route::post('/admin/push-notifications', [AdminPushNotificationController::class, 'store'])->name('admin.push-notifications.store');
});

Route::middleware(['auth', 'verified', 'has.role', 'section.role:teamleider'])->group(function () {
    Route::get('/admin/rechten', [SectionPermissionController::class, 'index'])->name('permissions.index');
    Route::patch('/admin/rechten/{sectionPermission}', [SectionPermissionController::class, 'update'])->name('permissions.update');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/geen-toegang', function () {
        return inertia('Auth/NoAccess');
    })->name('no-access');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'has.role', 'section.role:teamleider,leiding,ouder_contact,bestuurslid,lid'])->group(function () {
    Route::post('/push/subscriptions', [PushSubscriptionController::class, 'store'])->name('push.subscriptions.store');
    Route::delete('/push/subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push.subscriptions.destroy');
    Route::post('/active-section', function (Request $request) {
        $data = $request->validate([
            'section' => ['required', 'string', Rule::in(UserSectionRole::ALL_SECTIONS)],
        ]);

        if (! $request->user() || ! $request->user()->hasRoleInSection($data['section'])) {
            abort(403, 'Je hebt geen toegang tot deze speltak.');
        }

        $request->session()->put('active_section', $data['section']);

        return back();
    })->name('active-section.update');

    Route::get('/dashboard', DashboardController::class)->middleware('section.permission:dashboard')->name('dashboard');
    Route::patch('/dashboard/komende-opkomst-aanwezigheid', [DashboardController::class, 'updateUpcomingAttendance'])->middleware('section.permission:dashboard')->name('dashboard.upcoming-attendance.update');
    Route::get('/jaar-thema', [YearThemeController::class, 'index'])->middleware('section.permission:year_theme')->name('jaar-thema');
    Route::patch('/jaar-thema/entries/{yearThemeEntry}', [YearThemeController::class, 'updateEntry'])->middleware('section.permission:year_theme')->name('jaar-thema.entries.update');
    Route::get('/tipper-topper-opkomst', TipperTopperOpkomstController::class)->middleware('section.permission:tipper_topper')->name('tipper-topper-opkomst.index');
    Route::patch('/events/{event}/theme', [EventController::class, 'updateTheme'])->middleware('section.permission:events')->name('events.update-theme');
    Route::patch('/events/{event}/fields', [EventController::class, 'quickUpdate'])->middleware('section.permission:events')->name('events.quick-update');
    Route::patch('/events/{event}/attendance', [EventController::class, 'updateOwnAttendance'])->name('events.attendance.update');
    Route::get('/events/archived', [EventController::class, 'archived'])->middleware('section.permission:events')->name('events.archived');
    Route::get('/events/{event}', [EventController::class, 'show'])->middleware('section.permission:events')->name('events.show');
    Route::resource('events', EventController::class)->middleware('section.permission:events')->except(['create', 'show', 'edit']);
    Route::patch('/members/{member}/installed', [MemberController::class, 'updateInstalled'])->middleware('section.permission:members')->name('members.update-installed');
    Route::patch('/members/{member}/fields', [MemberController::class, 'quickUpdate'])->middleware('section.permission:members')->name('members.quick-update');
    Route::patch('/members/{member}/tipper-topper-opkomst', [MemberController::class, 'updateTipperTopperOpkomst'])->middleware('section.permission:members')->name('members.tipper-topper-opkomst');
    Route::get('/members/bijzonderheden', [MemberController::class, 'indexBijzonderheden'])->middleware('section.permission:members')->name('members.bijzonderheden');
    Route::get('/members/{member}', [MemberController::class, 'show'])->middleware('section.permission:members')->name('members.show');
    Route::patch('/leaders/{leader}/fields', [LeaderController::class, 'quickUpdate'])->middleware('section.permission:leaders')->name('leaders.quick-update');
    Route::get('/leaders/{leader}', [LeaderController::class, 'show'])->middleware('section.permission:leaders')->name('leaders.show');
    Route::resource('members', MemberController::class)->middleware('section.permission:members')->except(['create', 'show', 'edit']);
    Route::resource('leaders', LeaderController::class)->middleware('section.permission:leaders')->except(['create', 'show', 'edit']);
    Route::resource('pods', PodController::class)->middleware('section.permission:pods')->except(['create', 'show', 'edit']);
    Route::post('/pods/{pod}/members', [PodController::class, 'addMember'])->middleware('section.permission:pods')->name('pods.members.store');
    Route::patch('/pod-memberships/{podMembership}', [PodController::class, 'moveMember'])->middleware('section.permission:pods')->name('pods.members.move');
    Route::delete('/pod-memberships/{podMembership}', [PodController::class, 'removeMember'])->middleware('section.permission:pods')->name('pods.members.destroy');
    Route::patch('/info-notes/{info_note}/fields', [InfoNoteController::class, 'quickUpdate'])->middleware('section.permission:info_notes')->name('info-notes.quick-update');
    Route::resource('info-notes', InfoNoteController::class)->middleware('section.permission:info_notes')->except(['create', 'show', 'edit']);
    Route::patch('/task-items/{taskItem}/fields', [TaskItemController::class, 'quickUpdate'])->middleware('section.permission:task_items')->name('task-items.quick-update');
    Route::patch('/task-items/{taskItem}/linked-events', [TaskItemController::class, 'updateLinkedEvents'])->middleware('section.permission:task_items')->name('task-items.linked-events.update');
    Route::resource('task-items', TaskItemController::class)->middleware('section.permission:task_items')->except(['create', 'show', 'edit']);
    Route::post('/task-categories', [TaskItemController::class, 'storeCategory'])->middleware('section.permission:task_items')->name('task-categories.store');

});

require __DIR__.'/auth.php';
