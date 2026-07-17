<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->loadMissing('roles:id,name'),
            ],
            'notifications' => fn () => $request->user() ? [
                'unread' => $request->user()->unreadNotifications()->count(),
                'latest' => $request->user()->notifications()->limit(5)->get(),
            ] : ['unread' => 0, 'latest' => []],
            'taskComposer' => fn () => $request->user() ? [
                'projects' => Project::visibleTo($request->user())
                    ->where('status', 'active')
                    ->with(['statuses:id,project_id,name,color,order', 'members:id,name,email,avatar', 'labels:id,project_id,name,color'])
                    ->select('id', 'name', 'color', 'prefix')->orderBy('name')->get(),
            ] : ['projects' => []],
            'projectNavigation' => fn () => $request->user() ? [
                'recent' => Project::visibleTo($request->user())->where('status', 'active')
                    ->select('id', 'name', 'color', 'prefix')->latest('updated_at')->limit(2)->get(),
                'workspace' => $request->user()->currentWorkspace(),
                'canCreate' => $request->user()->hasRole('admin') || $request->user()->ownedWorkspaces()->exists(),
            ] : ['recent' => [], 'workspace' => null, 'canCreate' => false],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
