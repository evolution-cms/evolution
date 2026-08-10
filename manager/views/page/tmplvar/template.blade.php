<?php /** @var EvolutionCMS\Models\SiteTemplate $item */ ?>
<li>
    <label @if(!$item->selectable) class="disabled" @endif>
        @include('manager::form.inputElement', [
            'type' => 'checkbox',
            'name' => 'template[]',
            'checked' => !empty($selected),
            'value' => $item->getKey(),
            'attributes' => 'onchange="documentDirty=true;"'
        ])
        {{ $item->name }}
        <small>({{ $item->getKey() }})</small>
        @if(!empty($item->description))
            - {{ sanitize_inline_html($item->description) }}
        @endif
        @if(!empty($item->locked))
            <em>({{ ManagerTheme::getLexicon('locked') }})</em>
        @endif
        @if($item->getKey() == get_by_key(EvolutionCMS()->config, 'default_template'))
            <em>({{ ManagerTheme::getLexicon('defaulttemplate_title') }})</em>
        @endif
    </label>
</li>
