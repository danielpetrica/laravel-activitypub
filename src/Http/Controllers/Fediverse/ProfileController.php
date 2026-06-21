<?php

namespace DanielPetrica\LaravelActivityPub\Http\Controllers\Fediverse;

use DanielPetrica\LaravelActivityPub\Http\Requests\ProfileUpdateRequest;
use DanielPetrica\LaravelActivityPub\Traits\ResolvesLocalActor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    use ResolvesLocalActor;

    public function edit(): View
    {
        $user = auth()->user();
        $localActor = $this->resolveLocalActor();

        $parsedUrl = parse_url(url: $localActor->actor_id);

        return view(view: 'activitypub::fediverse.profile', data: [
            'actor' => $user,
            'localActor' => $localActor,
            'actorDomain' => $parsedUrl['host'].(isset($parsedUrl['port']) ? ':'.$parsedUrl['port'] : ''),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $localActor = $this->resolveLocalActor();

        $localActor->update(attributes: [
            'name' => $request->input(key: 'name'),
            'summary' => $request->input(key: 'summary'),
            'icon_url' => $request->input(key: 'icon_url'),
            'image_url' => $request->input(key: 'image_url'),
        ]);

        return redirect()
            ->route(route: 'fediverse.profile')
            ->with(key: 'success', value: 'Profile updated successfully.');
    }
}
