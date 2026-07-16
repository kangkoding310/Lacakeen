<?php

use App\Http\Controllers\BulkTaskController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskStatusController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('tasks', TaskController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::patch('/tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    Route::post('/tasks/bulk', BulkTaskController::class)->name('tasks.bulk');
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
    Route::post('/tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('tasks.attachments.store');

    Route::post('/projects/{project}/statuses', [TaskStatusController::class, 'store'])->name('statuses.store');
    Route::patch('/statuses/{status}', [TaskStatusController::class, 'update'])->name('statuses.update');
    Route::delete('/statuses/{status}', [TaskStatusController::class, 'destroy'])->name('statuses.destroy');

    Route::post('/projects/{project}/members', [ProjectController::class, 'addMember'])->name('projects.members.store');
    Route::patch('/projects/{project}/members/{user}', [ProjectController::class, 'updateMember'])->name('projects.members.update');
    Route::delete('/projects/{project}/members/{user}', [ProjectController::class, 'removeMember'])->name('projects.members.destroy');
    Route::resource('projects', ProjectController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::post('/calendar/events', [CalendarController::class, 'store'])->name('calendar.events.store');

    Route::get('/chat/{conversation?}', [ChatController::class, 'index'])->name('chat');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'store'])->name('chat.messages.store');
    Route::get('/reporting', ReportingController::class)->name('reporting');

    Route::get('/workflow', [WorkflowController::class, 'index'])->name('workflow');
    Route::post('/workflow', [WorkflowController::class, 'store'])->name('workflow.store');
    Route::patch('/workflow/{workflow}', [WorkflowController::class, 'update'])->name('workflow.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/members', [MemberController::class, 'index'])->name('members');
    Route::post('/members/invite', [MemberController::class, 'invite'])->name('members.invite');
    Route::patch('/members/{user}', [MemberController::class, 'update'])->name('members.update');

    Route::get('/inbox', [PageController::class, 'inbox'])->name('inbox');
    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations');
    Route::patch('/integrations/{integration}', [IntegrationController::class, 'update'])->name('integrations.update');
    Route::get('/help-center', [PageController::class, 'help'])->name('help-center');

    Route::get('/settings/{tab?}', [SettingsController::class, 'index'])->name('settings');
    Route::patch('/settings/preferences', [SettingsController::class, 'preferences'])->name('settings.preferences');
    Route::patch('/settings/workspace', [SettingsController::class, 'workspace'])->name('settings.workspace');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
