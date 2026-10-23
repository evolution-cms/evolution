@foreach ($nodes as $node)
    <li id="{{ $node['id'] }}" class="evo-manager-item {{ $node['class'] }} {{ $node['divider'] ? 'evo-manager-item--divider' : '' }}"
        @if ($node['lazy']) data-evo-menu-lazy @endif>
        <div class="evo-manager-item__row">
            @if ($node['group'] && ($node['children'] || $node['lazy']))
                <button type="button" class="evo-manager-link" data-evo-menu-toggle aria-expanded="false"
                    aria-controls="evo-menu-{{ $node['id'] }}" title="{{ $node['title'] }}" aria-label="{{ $node['title'] }}">
                    @if (!$node['hasIcon']) <x-evo::icon name="circle" class="evo-manager-mobile-icon" /> @endif
                    {{ $node['label'] }}
                    @if ($node['parent'] !== 'main') <x-evo::icon name="chevron-down" class="evo-manager-group-caret" /> @endif
                </button>
            @else
                <a class="evo-manager-link" href="{{ $node['href'] }}" target="{{ $node['target'] }}"
                    @if ($node['target'] === '_blank') rel="noopener noreferrer" @endif
                    title="{{ $node['title'] }}" aria-label="{{ $node['title'] }}" onclick="{{ $node['onclick'] }}">
                    @if (!$node['hasIcon']) <x-evo::icon name="circle" class="evo-manager-mobile-icon" /> @endif
                    {{ $node['label'] }}
                </a>
            @endif
            @foreach ($node['optional'] as $button)
                @if ($button['tag'] === 'button')
                    <button type="button" class="evo-manager-extra {{ $button['class'] }}" data-evo-menu-extra="{{ $button['key'] }}" title="{{ $button['title'] }}"
                        onclick="{{ $button['onclick'] }}">{{ $button['label'] }}</button>
                @else
                    <a class="evo-manager-extra {{ $button['class'] }}" data-evo-menu-extra="{{ $button['key'] }}" href="{{ $button['href'] }}" title="{{ $button['title'] }}"
                        onclick="{{ $button['onclick'] }}">{{ $button['label'] }}</a>
                @endif
            @endforeach
            @if (($node['children'] || $node['lazy']) && !$node['group'])
                <button type="button" class="evo-manager-caret" data-evo-menu-toggle aria-expanded="false"
                    aria-controls="evo-menu-{{ $node['id'] }}" aria-label="{{ $node['title'] }}">
                    <x-evo::icon name="chevron-down" />
                </button>
            @endif
        </div>
        @if ($node['children'] || $node['lazy'])
            <ul id="evo-menu-{{ $node['id'] }}" class="evo-manager-submenu" @if ($node['lazy']) data-evo-menu-results @endif>
                @include('manager::frame.topbar-items', ['nodes' => $node['children']])
            </ul>
        @endif
    </li>
@endforeach
