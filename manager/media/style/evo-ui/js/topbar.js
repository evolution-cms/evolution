(function () {
    'use strict';

    const menu = document.querySelector('[data-evo-topbar]');
    if (!menu) return;
    const dialog = document.getElementById('evo-menu-cache-confirm');
    const modes = ['', 'lightness', 'light', 'dark', 'darkness'];
    const themes = ['', 'evolightness', 'evolight', 'evodark', 'evodarkness'];
    const triggers = '[data-evo-menu-toggle], #settings > .dropdown > a.dropdown-toggle';

    function ownerOf(trigger) {
        return trigger.closest('li');
    }

    function setOpen(owner, open) {
        if (!open) owner.querySelectorAll('li.is-open').forEach(child => setOpen(child, false));
        owner.classList.toggle('is-open', open);
        owner.querySelectorAll(':scope > .evo-manager-item__row > [data-evo-menu-toggle], :scope > a.dropdown-toggle')
            .forEach(button => button.setAttribute('aria-expanded', String(open)));
    }

    function closeExcept(owner) {
        menu.querySelectorAll('li.is-open').forEach(item => {
            if (!owner || (item !== owner && !item.contains(owner))) setOpen(item, false);
        });
    }

    function position(owner) {
        const panel = owner.querySelector(':scope > ul');
        if (!panel) return;
        const row = owner.querySelector(':scope > .evo-manager-item__row') || owner;
        const rect = row.getBoundingClientRect();
        const nested = !!owner.parentElement.closest('.evo-manager-submenu');
        panel.style.left = '0px';
        panel.style.top = '0px';
        panel.style.maxHeight = Math.max(120, window.innerHeight - (nested ? rect.top : rect.bottom) - 16) + 'px';
        const width = panel.getBoundingClientRect().width;
        let left = nested ? rect.right + 4 : rect.left;
        if (owner.closest('#settings')) left = rect.right - width;
        if (left + width > window.innerWidth - 8) left = nested ? rect.left - width - 4 : window.innerWidth - width - 8;
        panel.style.left = Math.max(8, left) + 'px';
        panel.style.top = Math.max(8, nested ? rect.top : rect.bottom + 5) + 'px';
    }

    async function loadItems(owner, filter) {
        const panel = owner.querySelector(':scope > [data-evo-menu-results]');
        const link = owner.querySelector(':scope > .evo-manager-item__row > a');
        if (!panel || !link || (filter === undefined && owner.dataset.loaded === 'true')) return;
        const params = new URLSearchParams(new URL(link.href, location.href).search);
        params.set('elements', owner.id);
        if (filter !== undefined) params.set('filter', filter);
        const request = String(Number(owner.dataset.request || 0) + 1);
        owner.dataset.request = request;
        panel.setAttribute('aria-busy', 'true');
        panel.querySelector('.evo-manager-load-error')?.remove();
        try {
            // Native transport; the endpoint retains the manager's ACL/session guards.
            const response = await fetch(window.evo.EVO_MANAGER_URL + 'media/style/' + window.evo.config.theme + '/ajax.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: params
            });
            if (!response.ok || response.redirected) throw new Error('Menu request failed');
            const html = await response.text();
            if (owner.dataset.request !== request) return;
            if (filter === undefined) {
                panel.innerHTML = html;
                owner.dataset.loaded = 'true';
                panel.querySelectorAll('input[name="filter"]').forEach(input => {
                    input.setAttribute('aria-label', link.title || link.textContent.trim());
                    let timer;
                    input.addEventListener('input', () => {
                        clearTimeout(timer);
                        timer = setTimeout(() => loadItems(owner, input.value), 180);
                    });
                });
            } else {
                panel.querySelectorAll(':scope > .item').forEach(item => item.remove());
                const more = panel.querySelector(':scope > .item-more');
                if (more) more.insertAdjacentHTML('beforebegin', html);
                else panel.insertAdjacentHTML('beforeend', html);
            }
            // Keyboard expansion may finish after the first ArrowDown.
            const trigger = owner.querySelector('[data-evo-menu-toggle]');
            if (owner.classList.contains('is-open') && document.activeElement === trigger) focusable(panel)[0]?.focus();
        } catch (error) {
            if (owner.dataset.request !== request) return;
            const item = document.createElement('li');
            item.className = 'evo-manager-load-error';
            item.setAttribute('role', 'alert');
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.textContent = '↻ ' + menu.dataset.evoMenuError;
            retry.addEventListener('click', () => {
                // Keep focus inside the bar before removing the retry button.
                (panel.querySelector('input') || owner.querySelector('[data-evo-menu-toggle]')).focus();
                loadItems(owner, filter);
            });
            item.appendChild(retry);
            panel.appendChild(item);
        } finally {
            if (owner.dataset.request === request) {
                panel.removeAttribute('aria-busy');
                if (owner.classList.contains('is-open')) position(owner);
            }
        }
    }

    function toggle(trigger, forceOpen) {
        const owner = ownerOf(trigger);
        const open = forceOpen === undefined ? !owner.classList.contains('is-open') : forceOpen;
        closeExcept(open ? owner : owner.parentElement.closest('li'));
        setOpen(owner, open);
        if (open) {
            if (owner.hasAttribute('data-evo-menu-lazy')) loadItems(owner);
            position(owner);
        }
    }

    function currentMode() {
        const root = document.documentElement;
        return [1, 4, 3, 2].find(mode => root.classList.contains(modes[mode])) || 3;
    }

    function syncTheme() {
        const mode = currentMode();
        menu.dataset.theme = themes[mode];
        dialog.dataset.theme = themes[mode];
        try {
            localStorage.setItem('evo.theme', themes[mode]);
            localStorage.setItem('evo.mode', mode > 2 ? 'dark' : 'light');
        } catch (ignore) {}
    }

    menu.addEventListener('click', event => {
        const trigger = event.target.closest(triggers);
        if (trigger) {
            event.preventDefault();
            toggle(trigger);
            return;
        }
        const link = event.target.closest('a');
        if (!link) return;
        // Closest() also covers clicks on SVG paths and rich plugin labels.
        if (!event.defaultPrevented && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && link.target === 'main') {
            window.evo.tabs({url: link.href, title: link.title || link.textContent.trim()});
            event.preventDefault();
        }
        closeExcept(null);
    });

    // Capture before the optional button's legacy inline popup handler executes.
    menu.addEventListener('click', event => {
        const extra = event.target.closest('#refresh_site [data-evo-menu-extra="refresh_site_in_window"]');
        if (!extra) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const link = document.querySelector('#refresh_site .evo-manager-link');
        dialog.dataset.url = link.href;
        closeExcept(null);
        dialog.showModal();
    }, true);
    dialog.querySelector('[data-evo-cache-cancel]').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-evo-cache-confirm]').addEventListener('click', () => {
        window.evo.tabs({url: dialog.dataset.url, title: dialog.querySelector('h2').textContent});
        dialog.close();
    });

    function focusable(panel) {
        return Array.from(panel.querySelectorAll('a, button, input')).filter(element => element.getClientRects().length && !element.disabled);
    }

    menu.addEventListener('keydown', event => {
        // Keep text editing keys inside search/filter fields; Escape still closes.
        if (event.key !== 'Escape' && event.target.matches('input, textarea, [contenteditable="true"]')) return;
        const trigger = event.target.closest(triggers);
        if (event.key === 'Escape' || event.key === 'ArrowLeft') {
            const owner = event.target.closest('li.is-open') || event.target.closest('ul').closest('li.is-open');
            if (owner) {
                setOpen(owner, false);
                owner.querySelector(triggers)?.focus();
                event.preventDefault();
            }
        } else if ((event.key === 'ArrowDown' || event.key === 'ArrowRight') && trigger) {
            toggle(trigger, true);
            const panel = ownerOf(trigger).querySelector(':scope > ul');
            focusable(panel)[0]?.focus();
            event.preventDefault();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            const panel = event.target.closest('ul');
            const items = focusable(panel);
            const index = items.indexOf(event.target);
            items[(index + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length]?.focus();
            event.preventDefault();
        }
    });
    menu.addEventListener('focusout', () => setTimeout(() => {
        if (!menu.contains(document.activeElement)) closeExcept(null);
    }, 0));
    document.addEventListener('pointerdown', event => {
        if (!menu.contains(event.target)) closeExcept(null);
    });
    window.addEventListener('resize', () => closeExcept(null));
    new MutationObserver(syncTheme).observe(document.documentElement, {attributes: true, attributeFilter: ['class']});
    syncTheme();
})();
