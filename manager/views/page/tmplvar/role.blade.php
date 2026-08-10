<?php /** @var EvolutionCMS\Models\SiteTemplate $item */ ?>
<li>
    <label>
        @include('manager::form.inputElement', [
            'type' => 'checkbox',
            'name' => 'role[]',
            'checked' => !empty($selected),
            'value' => $item->getKey(),
            'attributes' => 'onchange="documentDirty=true;"'
        ])
        {{ $item->name }}
        <small>({{ $item->getKey() }})</small>
        @if(!empty($item->description))
            - {{ sanitize_inline_html($item->description) }}
        @endif
    </label>
</li>
