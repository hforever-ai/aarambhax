<x-layouts.app title="Login — Aarambhax Legal" description="Log in to Aarambhax">
    <section class="container-page py-16 max-w-md">
        <h1 class="text-3xl font-serif font-medium mb-2" style="color: var(--fg);">Log in</h1>
        <p class="mb-8" style="color: var(--fg-muted);">Use your advocate account to access the drafting tool.</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium mb-1" style="color: var(--fg);">Email</label>
                <input id="email" type="email" name="email" required autofocus
                       value="{{ old('email') }}"
                       class="w-full px-4 py-3 rounded-md"
                       style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);"
                       aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                @error('email')<p role="alert" class="text-sm mt-1" style="color: var(--danger);">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium mb-1" style="color: var(--fg);">Password</label>
                <input id="password" type="password" name="password" required
                       class="w-full px-4 py-3 rounded-md"
                       style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);">
            </div>
            <button type="submit" class="btn btn-primary w-full">Log in</button>
        </form>

        <p class="mt-6 text-sm" style="color: var(--fg-muted);">
            New here? <a href="{{ route('register') }}" style="color: var(--link);">Create an advocate account</a>
        </p>
    </section>
</x-layouts.app>
