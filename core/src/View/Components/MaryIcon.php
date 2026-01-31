<?php namespace EvolutionCMS\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\View\Component;

class MaryIcon extends Component
{
    public string $uuid;

    public function __construct(
        public string $name,
        public ?string $id = null,
        public ?string $label = null
    ) {
        $this->uuid = 'mary' . md5(serialize($this)) . $id;
    }

    public function icon(): string|Stringable
    {
        $name = Str::of($this->name);

        if ($name->contains('.')) {
            return $name->replace('.', '-');
        }

        $normalized = (string) $name;
        $normalized = preg_replace('/^(o|s)-/', '', $normalized) ?? $normalized;

        $map = [
            'x-mark' => 'x',
            'x-circle' => 'circle-x',
        ];

        $normalized = $map[$normalized] ?? $normalized;

        return "tabler-{$normalized}";
    }

    public function labelClasses(): ?string
    {
        return Str::replaceMatches('/(w-\w*)|(h-\w*)/', '', $this->attributes->get('class') ?? '');
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                @if(strlen($label ?? '') > 0)
                    <div class="inline-flex items-center gap-1">
                @endif
                    <x-svg
                        :name="$icon()"
                        {{ $attributes->class(['inline flex-shrink-0', 'w-5 h-5' => !Str::contains($attributes->get('class') ?? '', ['w-', 'h-']) ]) }}
                    />

                @if(strlen($label ?? '') > 0)
                        <div class="{{ $labelClasses() }}" {{ $attributes->whereStartsWith('@') }}>
                            {{ $label }}
                        </div>
                    </div>
                @endif
            BLADE;
    }
}
