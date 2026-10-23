<div class="panel-heading">
    <span class="panel-title">
        <a class="accordion-toggle" id="toggle{{ $name }}{{ $id }}" href="#collapse{{ $name }}{{ $id }}" data-cattype="{{ $name }}" data-catid="{{ $id }}" title="Click to toggle collapse. Shift+Click to toggle all.">
            <i class="evo-icon evo-icon-fw"></i>
            <span class="category_name">
                <strong>
                    {{ $title }}
                    @if($id > 0)
                        <small>({{ $id }})</small>
                    @endif
                </strong>
            </span>
        </a>
    </span>
</div>
<div id="collapse{{ $name }}{{ $id }}" class="panel-collapse collapse in" aria-expanded="true">
    {{ $slot }}
</div>
