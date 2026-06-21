<x-layouts.app title="Contact — Aarambhax Legal" description="Get in touch with the Aarambhax team.">
    <section class="container-page py-12 max-w-2xl">
        <h1 class="text-4xl sm:text-5xl font-serif font-medium mb-4" style="color: var(--fg);">Contact</h1>
        <p class="text-lg mb-8" style="color: var(--fg-muted);">
            Questions, feedback, or want to be a beta tester? Send us a message.
        </p>

        @if(session('contact_success'))
            <div class="card mb-6" style="border-color: var(--success); background: color-mix(in srgb, var(--success) 8%, var(--surface));" role="status">
                <p style="color: var(--success);"><strong>Thank you!</strong> We'll get back to you within 1-2 business days.</p>
            </div>
        @endif

        <form action="{{ route('contact.submit') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium mb-1" style="color: var(--fg);">Your name <span aria-hidden="true">*</span><span class="sr-only">required</span></label>
                <input id="name" type="text" name="name" required maxlength="200"
                       class="w-full px-4 py-3 rounded-md"
                       style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);"
                       value="{{ old('name') }}"
                       aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                       @if($errors->has('name')) aria-describedby="name-error" @endif>
                @error('name')<p id="name-error" role="alert" class="text-sm mt-1" style="color: var(--danger);">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium mb-1" style="color: var(--fg);">Email <span aria-hidden="true">*</span><span class="sr-only">required</span></label>
                <input id="email" type="email" name="email" required
                       class="w-full px-4 py-3 rounded-md"
                       style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);"
                       value="{{ old('email') }}"
                       aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                       @if($errors->has('email')) aria-describedby="email-error" @endif>
                @error('email')<p id="email-error" role="alert" class="text-sm mt-1" style="color: var(--danger);">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium mb-1" style="color: var(--fg);">Phone (optional)</label>
                <input id="phone" type="tel" name="phone" maxlength="20"
                       class="w-full px-4 py-3 rounded-md"
                       style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);"
                       value="{{ old('phone') }}">
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium mb-1" style="color: var(--fg);">Subject</label>
                <input id="subject" type="text" name="subject" maxlength="255"
                       class="w-full px-4 py-3 rounded-md"
                       style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);"
                       value="{{ old('subject') }}">
            </div>

            <div>
                <label for="message" class="block text-sm font-medium mb-1" style="color: var(--fg);">Message <span aria-hidden="true">*</span><span class="sr-only">required</span></label>
                <textarea id="message" name="message" required rows="6"
                          class="w-full px-4 py-3 rounded-md"
                          style="background: var(--surface); border: 1.5px solid var(--border); color: var(--fg);"
                          aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}"
                          @if($errors->has('message')) aria-describedby="message-error" @endif>{{ old('message') }}</textarea>
                @error('message')<p id="message-error" role="alert" class="text-sm mt-1" style="color: var(--danger);">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn btn-primary">Send message</button>
        </form>
    </section>
</x-layouts.app>
