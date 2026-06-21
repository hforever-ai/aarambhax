@php $client = $client ?? null; @endphp

<div class="premium-form">
    <div class="premium-field">
        <label for="name">Name <span class="premium-field-required">*</span></label>
        <input id="name" type="text" name="name" required maxlength="255"
               value="{{ old('name', $client?->name) }}">
        @error('name')<p class="premium-field-help" style="color: var(--danger);" role="alert">{{ $message }}</p>@enderror
    </div>
    <div class="premium-grid-2">
        <div class="premium-field">
            <label for="phone">Phone</label>
            <input id="phone" type="tel" name="phone" maxlength="20"
                   value="{{ old('phone', $client?->phone) }}">
        </div>
        <div class="premium-field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" maxlength="255"
                   value="{{ old('email', $client?->email) }}">
        </div>
    </div>
    <div class="premium-field">
        <label for="address">Address</label>
        <textarea id="address" name="address" rows="2" maxlength="1000">{{ old('address', $client?->address) }}</textarea>
    </div>
    <div class="premium-field">
        <label for="notes">Notes <span class="premium-field-help" style="font-weight: 400;">(private)</span></label>
        <textarea id="notes" name="notes" rows="3" maxlength="5000">{{ old('notes', $client?->notes) }}</textarea>
    </div>
</div>
