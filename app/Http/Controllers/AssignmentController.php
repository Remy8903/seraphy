<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignmentRequest;
use App\Models\Activity;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AssignmentController extends Controller
{
    /**
     * Add an activity to the client's therapy plan.
     */
    public function store(AssignmentRequest $request, Client $client): RedirectResponse
    {
        $client->activities()->attach($request->integer('activity_id'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity assigned.')]);

        return to_route('clients.show', $client);
    }

    /**
     * Remove an activity from the client's therapy plan. Past sessions are kept.
     */
    public function destroy(Client $client, Activity $activity): RedirectResponse
    {
        Gate::authorize('update', $client);

        $client->activities()->detach($activity->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity removed from plan.')]);

        return to_route('clients.show', $client);
    }
}
