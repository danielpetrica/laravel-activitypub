@extends('activitypub::fediverse.layout')

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Profile</h2>

    <div class="max-w-2xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-start gap-5 mb-6 pb-6 border-b border-gray-100">
                <div class="flex-shrink-0">
                    @if ($localActor->icon_url)
                        <img src="{{ $localActor->icon_url }}" alt="" class="w-20 h-20 rounded-full">
                    @else
                        <div class="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-3xl font-bold text-indigo-600">{{ strtoupper(substr($localActor->name ?? $localActor->username, 0, 1)) }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="text-xl font-bold text-gray-900">{{ $localActor->name ?? $localActor->username }}</h3>
                    <p class="text-sm text-gray-500">{{ $localActor->username.'@'.$actorDomain }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        <a href="{{ $localActor->actor_id }}" class="hover:text-indigo-600" target="_blank">{{ $localActor->actor_id }}</a>
                    </p>
                </div>
            </div>

            <form action="{{ route('fediverse.profile.update') }}" method="POST">
                @csrf

                <div class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Display name</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $localActor->name ?? '') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            maxlength="255"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="summary" class="block text-sm font-medium text-gray-700 mb-1">Bio / Summary</label>
                        <textarea
                            name="summary"
                            id="summary"
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            maxlength="5000"
                        >{{ old('summary', $localActor->summary ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-gray-400">HTML is allowed.</p>
                        @error('summary')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="icon_url" class="block text-sm font-medium text-gray-700 mb-1">Avatar URL</label>
                        <input
                            type="url"
                            name="icon_url"
                            id="icon_url"
                            value="{{ old('icon_url', $localActor->icon_url ?? '') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            maxlength="2048"
                            placeholder="https://example.com/avatar.jpg"
                        >
                        @error('icon_url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Header image URL</label>
                        <input
                            type="url"
                            name="image_url"
                            id="image_url"
                            value="{{ old('image_url', $localActor->image_url ?? '') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            maxlength="2048"
                            placeholder="https://example.com/banner.jpg"
                        >
                        @error('image_url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-gray-100">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
