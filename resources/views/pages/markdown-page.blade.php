<x-layouts.app :title="$title" :description="$description">
    <article class="container-page py-12 max-w-3xl">
        <div class="prose-aarambhax" style="color: var(--fg);">
            {!! $html !!}
        </div>

        @if($show_contact_form)
            @include('partials.contact-form')
        @endif
    </article>
</x-layouts.app>
