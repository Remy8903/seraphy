<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ClientArchiveController extends Controller
{
    /**
     * Archive the client, or restore them if they are already archived.
     */
    public function __invoke(Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        $wasArchived = $client->isArchived();

        $client->forceFill(['archived_at' => $wasArchived ? null : now()])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $wasArchived ? __('Client restored.') : __('Client archived.'),
        ]);

        return to_route('clients.show', $client);
    }
}
