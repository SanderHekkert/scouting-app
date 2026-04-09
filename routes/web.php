<?php

use App\Http\Controllers\AdminPushNotificationController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminUserInvitationController;
use App\Http\Controllers\AgendaItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HealthFormController;
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

Route::middleware(['auth', 'verified', 'has.role', 'section.role:admin'])->group(function () {
    Route::get('/admin/rollen', [AdminRoleController::class, 'index'])->name('admin.roles.index');
    Route::patch('/admin/rollen/{user}', [AdminRoleController::class, 'update'])->name('admin.roles.update');
    Route::patch('/admin/rollen/{user}/basis', [AdminRoleController::class, 'updateBasics'])->name('admin.roles.update-basics');
});

Route::middleware(['auth', 'verified', 'has.role', 'section.role:admin,bestuurslid'])->group(function () {
    Route::get('/admin/pushmeldingen', [AdminPushNotificationController::class, 'index'])->name('admin.push-notifications.index');
    Route::get('/admin/gebruikers', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/gebruikers/uitnodigen', [AdminUserInvitationController::class, 'create'])->name('admin.users.invite.create');
    Route::get('/admin/gebruikers/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::patch('/admin/gebruikers/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/gebruikers/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/admin/gebruikers/uitnodigen', [AdminUserInvitationController::class, 'store'])->name('admin.users.invite');
    Route::post('/admin/push-notifications', [AdminPushNotificationController::class, 'store'])->name('admin.push-notifications.store');
});

Route::middleware(['auth', 'verified', 'has.role', 'section.role:admin'])->group(function () {
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

Route::middleware(['auth', 'verified', 'has.role', 'section.role:admin,bestuurslid'])->group(function () {
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
    Route::patch('/opkomsten/{event}/theme', [EventController::class, 'updateTheme'])->middleware('section.permission:events')->name('opkomsten.update-theme');
    Route::patch('/opkomsten/{event}/fields', [EventController::class, 'quickUpdate'])->middleware('section.permission:events')->name('opkomsten.quick-update');
    Route::get('/opkomsten/{event}/attachment', [EventController::class, 'downloadAttachment'])->middleware('section.permission:events')->name('opkomsten.attachment.download');
    Route::patch('/opkomsten/{event}/attendance', [EventController::class, 'updateOwnAttendance'])->name('opkomsten.attendance.update');
    Route::get('/opkomsten/archived', [EventController::class, 'archived'])->middleware('section.permission:events')->name('opkomsten.archived');
    Route::get('/opkomsten/nieuw', [EventController::class, 'create'])->middleware('section.permission:events')->name('opkomsten.create');
    Route::get('/opkomsten/{event}', [EventController::class, 'show'])->middleware('section.permission:events')->name('opkomsten.show');
    Route::resource('opkomsten', EventController::class)->middleware('section.permission:events')->parameters(['opkomsten' => 'event'])->except(['show', 'edit']);

    Route::patch('/members/{member}/installed', [MemberController::class, 'updateInstalled'])->middleware('section.permission:members')->name('members.update-installed');
    Route::patch('/members/{member}/gedoopt', [MemberController::class, 'updateGedoopt'])->middleware('section.permission:members')->name('members.update-gedoopt');
    Route::patch('/members/{member}/fields', [MemberController::class, 'quickUpdate'])->middleware('section.permission:members')->name('members.quick-update');
    Route::patch('/members/{member}/tipper-topper-opkomst', [MemberController::class, 'updateTipperTopperOpkomst'])->middleware('section.permission:members')->name('members.tipper-topper-opkomst');
    Route::patch('/members/{member}/transfer', [MemberController::class, 'transfer'])->middleware('section.permission:members')->name('members.transfer');
    Route::get('/members/bijzonderheden', [MemberController::class, 'indexBijzonderheden'])->middleware('section.permission:members')->name('members.bijzonderheden');
    Route::get('/members/{member}', [MemberController::class, 'show'])->middleware('section.permission:members')->name('members.show');
    Route::patch('/leaders/{leader}/fields', [LeaderController::class, 'quickUpdate'])->middleware('section.permission:leaders')->name('leaders.quick-update');
    Route::patch('/leaders/{leader}/installed', [LeaderController::class, 'updateInstalled'])->middleware('section.permission:leaders')->name('leaders.update-installed');
    Route::patch('/leaders/{leader}/gedoopt', [LeaderController::class, 'updateGedoopt'])->middleware('section.permission:leaders')->name('leaders.update-gedoopt');
    Route::get('/leaders/{leader}', [LeaderController::class, 'show'])->middleware('section.permission:leaders')->name('leaders.show');
    Route::resource('members', MemberController::class)->middleware('section.permission:members')->except(['create', 'show', 'edit']);
    Route::resource('leaders', LeaderController::class)->middleware('section.permission:leaders')->except(['create', 'show', 'edit']);
    Route::resource('pods', PodController::class)->middleware('section.permission:pods')->except(['create', 'show', 'edit']);
    Route::post('/pods/{pod}/members', [PodController::class, 'addMember'])->middleware('section.permission:pods')->name('pods.members.store');
    Route::patch('/pod-memberships/{podMembership}', [PodController::class, 'moveMember'])->middleware('section.permission:pods')->name('pods.members.move');
    Route::delete('/pod-memberships/{podMembership}', [PodController::class, 'removeMember'])->middleware('section.permission:pods')->name('pods.members.destroy');
    Route::patch('/info-notes/{info_note}/fields', [InfoNoteController::class, 'quickUpdate'])->middleware('section.permission:info_notes')->name('info-notes.quick-update');
    Route::get('/info-notes/{info_note}', [InfoNoteController::class, 'show'])->middleware('section.permission:info_notes')->name('info-notes.show');
    Route::resource('info-notes', InfoNoteController::class)->middleware('section.permission:info_notes')->except(['create', 'show', 'edit']);
    Route::patch('/task-items/{taskItem}/fields', [TaskItemController::class, 'quickUpdate'])->middleware('section.permission:task_items')->name('task-items.quick-update');
    Route::patch('/task-items/{taskItem}/linked-events', [TaskItemController::class, 'updateLinkedEvents'])->middleware('section.permission:task_items')->name('task-items.linked-events.update');
    Route::get('/task-items/nieuw', [TaskItemController::class, 'create'])->middleware('section.permission:task_items')->name('task-items.create');
    Route::resource('task-items', TaskItemController::class)->middleware('section.permission:task_items')->except(['create', 'show', 'edit']);
    Route::post('/task-categories', [TaskItemController::class, 'storeCategory'])->middleware('section.permission:task_items')->name('task-categories.store');
    Route::patch('/task-categories/order', [TaskItemController::class, 'reorderCategories'])->middleware('section.permission:task_items')->name('task-categories.reorder');
});

Route::middleware(['auth', 'verified', 'has.role'])->group(function () {
    Route::get('/agenda/archived', [AgendaItemController::class, 'archived'])->middleware('section.permission:events')->name('agenda.archived');
    Route::get('/agenda/nieuw', [AgendaItemController::class, 'create'])->middleware('section.permission:events')->name('agenda.create');
    Route::get('/agenda/{agendaItem}/attachment', [AgendaItemController::class, 'downloadAttachment'])->middleware('section.permission:events')->name('agenda.attachment.download');
    Route::get('/agenda/{agendaItem}/ics', [AgendaItemController::class, 'ics'])->middleware('section.permission:events')->name('agenda.ics');
    Route::patch('/agenda/{agendaItem}/schedule', [AgendaItemController::class, 'updateSchedule'])->middleware('section.permission:events')->name('agenda.schedule.update');
    Route::get('/agenda/opkomsten/{event}', [AgendaItemController::class, 'showOpkomst'])->middleware('section.permission:events')->name('agenda.opkomsten.show');
    Route::get('/agenda/{agendaItem}', [AgendaItemController::class, 'show'])->middleware('section.permission:events')->name('agenda.show');
    Route::resource('agenda', AgendaItemController::class)->middleware('section.permission:events')->parameters(['agenda' => 'agendaItem'])->except(['create', 'show', 'edit']);

    Route::get('/admin/gezondheidsformulieren', [HealthFormController::class, 'index'])->name('admin.health-forms.index');
    Route::get('/admin/gezondheidsformulieren/nieuw', [HealthFormController::class, 'create'])->name('admin.health-forms.create');
    Route::post('/admin/gezondheidsformulieren', [HealthFormController::class, 'store'])->name('admin.health-forms.store');
    Route::post('/admin/gezondheidsformulieren/bevestigen', [HealthFormController::class, 'confirm'])->name('admin.health-forms.confirm');
    Route::get('/admin/gezondheidsformulieren/{health_form}', [HealthFormController::class, 'show'])->name('admin.health-forms.show');
    Route::get('/admin/gezondheidsformulieren/{health_form}/download', [HealthFormController::class, 'download'])->name('admin.health-forms.download');
    Route::delete('/admin/gezondheidsformulieren/{health_form}', [HealthFormController::class, 'destroy'])->name('admin.health-forms.destroy');
});

require __DIR__.'/auth.php';
