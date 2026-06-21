<section class="mt-12 pt-8" style="border-top: 1px solid var(--border);" aria-labelledby="contact-form-heading">
    <h2 id="contact-form-heading" class="text-2xl font-serif font-medium mb-2" style="color: var(--fg);">Send us a message</h2>
    <p class="mb-6 text-sm" style="color: var(--fg-muted);">
        Or fill out the form below — we read every message and reply within 1–2 working days.
    </p>

    @if(session('contact_success'))
        <div class="card mb-6" style="border-color: var(--success); background: color-mix(in srgb, var(--success) 8%, var(--surface));" role="status">
            <p style="color: var(--success);"><strong>Thank you!</strong> We'll get back to you within 1–2 working days.</p>
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
