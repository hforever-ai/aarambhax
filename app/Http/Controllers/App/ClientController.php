<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::where('user_id', auth()->id())
            ->withCount('cases')
            ->orderBy('name')
            ->paginate(30);
        return view('app.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('app.clients.new');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'email'   => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes'   => ['nullable', 'string', 'max:5000'],
        ]);
        Client::create(array_merge($data, ['user_id' => auth()->id()]));
        return redirect()->route('app.clients.index')->with('edit_success', 'Client added.');
    }

    public function show(Client $client)
    {
        $this->authorise($client);
        $client->load(['cases' => fn ($q) => $q->latest('updated_at')]);
        return view('app.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        $this->authorise($client);
        return view('app.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $this->authorise($client);
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'email'   => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes'   => ['nullable', 'string', 'max:5000'],
        ]);
        $client->update($data);
        return redirect()->route('app.clients.show', $client)->with('edit_success', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        $this->authorise($client);
        $client->delete();
        return redirect()->route('app.clients.index')->with('edit_success', 'Client deleted.');
    }

    private function authorise(Client $client): void
    {
        abort_if((int) $client->user_id !== (int) auth()->id(), 403);
    }
}
