<x-layouts.app title="Create account — Aarambhax Legal" description="Create your Aarambhax account">
    <section class="container-page py-16 max-w-md">
        <h1 class="text-3xl font-serif font-medium mb-2" style="color: var(--fg);">Create account</h1>
        <p class="mb-8" style="color: var(--fg-muted);">For advocates, by an advocate. Free during beta.</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-5" autocomplete="off">
            @csrf
            {{-- Honeypot — hidden field; bots fill it, humans never see it. Server rejects submissions where 'website' is non-empty. --}}
            <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;" tabindex="-1">
                <label for="website">Website (leave blank)</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>
            <div>
                <label for="name" class="block text-sm font-medium mb-1" style="color: var(--fg);">Full name</label>
                <input id="name" type="text" name="name" required autofocus value="{{ old('name') }}"
                       class="w-full px-4 py-3 rounded-md"
                       style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                @error('name')<p role="alert" class="text-sm mt-1" style="color: var(--danger);">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium mb-1" style="color: var(--fg);">Email</label>
                <input id="email" type="email" name="email" required value="{{ old('email') }}"
                       class="w-full px-4 py-3 rounded-md"
                       style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                @error('email')<p role="alert" class="text-sm mt-1" style="color: var(--danger);">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium mb-1" style="color: var(--fg);">Password</label>
                <input id="password" type="password" name="password" required
                       class="w-full px-4 py-3 rounded-md"
                       style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
                @error('password')<p role="alert" class="text-sm mt-1" style="color: var(--danger);">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium mb-1" style="color: var(--fg);">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       class="w-full px-4 py-3 rounded-md"
                       style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
            </div>
            <button type="submit" class="btn btn-primary w-full">Create account</button>
        </form>

        <p class="mt-6 text-sm" style="color: var(--fg-muted);">
            Already have one? <a href="{{ route('login') }}" style="color: var(--link);">Log in</a>
        </p>
    </section>
</x-layouts.app>
