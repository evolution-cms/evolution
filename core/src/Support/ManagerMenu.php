<?php

namespace EvolutionCMS\Support;

use Illuminate\Support\HtmlString;

/** Adapts the public indexed manager menu contract without rebuilding its producers. */
class ManagerMenu
{
    public function tree(array $items, callable $can): array
    {
        $groups = [];
        foreach ($items as $row) {
            if (!is_array($row) || !isset($row[0], $row[1], $row[2])) {
                continue;
            }
            // The shipped sidebar toggle historically carries this JS sentinel in column 6.
            $permission = trim((string) ($row[6] ?? ''));
            $permissions = $permission === 'return false;' ? [] : array_filter(array_map('trim', explode(',', $permission)));
            // Core entries list alternative operations, e.g. new_chunk,edit_chunk.
            if ($permissions !== [] && !array_any($permissions, $can)) {
                continue;
            }
            $groups[(string) $row[1]][] = $row;
        }
        foreach ($groups as &$rows) {
            // PHP's stable sort preserves plugin insertion order for equal positions.
            usort($rows, static fn (array $a, array $b) => ($a[9] ?? 0) <=> ($b[9] ?? 0));
        }
        unset($rows);

        return $this->children('main', $groups, ['main']);
    }

    private function children(string $parent, array $groups, array $ancestors): array
    {
        $nodes = [];
        foreach ($groups[$parent] ?? [] as $row) {
            $id = (string) $row[0];
            if ($id === '' || in_array($id, $ancestors, true)) {
                continue;
            }
            $classes = (string) ($row[10] ?? '');
            $children = $this->children($id, $groups, [...$ancestors, $id]);
            $label = (string) $row[2];
            // A separate, keyboard-operable button replaces the old inline arrow.
            $label = preg_replace('~<(svg|i|span)\b[^>]*\bclass=["\'][^"\']*\btoggle\b[^"\']*["\'][^>]*>.*?</\1>~is', '', $label);
            $href = (string) ($row[3] ?? 'javascript:;');
            $onclick = trim((string) ($row[5] ?? ''));
            $nodes[] = [
                'id' => $id,
                'parent' => $parent,
                'hasIcon' => (bool) preg_match('~<(svg|i|img)\b~i', $label),
                // Names and handlers are trusted plugin-authored HTML/code by the menu API contract.
                'label' => new HtmlString($label),
                'title' => html_entity_decode((string) ($row[4] ?? strip_tags($label)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'href' => $href,
                'onclick' => $onclick,
                'target' => (string) ($row[7] ?? ''),
                'divider' => !empty($row[8]),
                'class' => $classes,
                'children' => $children,
                'lazy' => $children === [] && in_array('dropdown-toggle', preg_split('/\s+/', $classes), true),
                'group' => in_array(strtolower(trim($href)), ['', '#', 'javascript:;'], true)
                    && in_array($onclick, ['', 'return false;', 'return false'], true),
                'optional' => $this->optional($row[11] ?? []),
            ];
        }
        return $nodes;
    }

    private function optional(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }
        if (isset($items[0]) && is_string($items[0])) {
            $items = [$items];
        }
        $buttons = [];
        foreach ($items as $key => $item) {
            if (!is_array($item) || count($item) < 6 || !in_array($item[0], ['a', 'button'], true)) {
                continue;
            }
            $buttons[] = [
                'key' => is_string($key) ? $key : '',
                'tag' => $item[0], 'href' => $item[1], 'class' => $item[2],
                'onclick' => $item[3], 'title' => $item[4], 'label' => new HtmlString($item[5]),
            ];
        }
        return $buttons;
    }
}
