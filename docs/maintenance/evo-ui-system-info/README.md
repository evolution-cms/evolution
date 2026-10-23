# Evo UI у ядрі 3.6: System Info і вибір інтерфейсу (#2515)

Issue: https://github.com/evolution-cms/evolution/issues/2515.
База: `3.6.x`, `ee341135a` (Laravel 13 уже merged). Окрема гілка:
`middleDuck/evo-2515-ui-system-info`.

## Мінімальний склад

Єдина нова пряма вимога ядра — `evolution-cms/evo-ui:^1.3`, locked **1.3.0**.
Він постачає Livewire, locked **4.4.8**, як свою runtime-залежність. Не дублюємо
ту саму вимогу в core без окремої причини. Обидва доступні після звичайного core
`composer install`, включно з `--no-dev`.

Інші нові entries — лише обов'язкові транзитивні залежності Evo UI / CommonMark:
`league/commonmark` 2.10.4, `league/config` 1.2.0, `dflydev/dot-access-data` 3.0.3,
`nette/schema` 1.4.0, `nette/utils` 4.1.6. Production graph: **116 → 123**;
попередній Laravel 13 graph не оновлювали вдруге.

sTask, sGallery, editors та інші extras не додаються. Їхній майбутній склад
узгоджується окремо. Тут немає нових CLI workers чи сторонніх інтеграцій.

`package:discover` обробляє custom Composer root, тому штатний core provider
зареєстрований явно в `core/config/app.php`, поряд з іншими core providers.
Provider і Foundation/Livewire bridge належать самому Evo UI; копії bridge у core
не створюються. Клас provider не реєструється двічі навіть коли його має custom extra:
контейнер уже дедуплікує завантажені providers за класом.

## Як співіснують інтерфейси

Використано чинний `manager_theme`, без другого перемикача чи нового реєстру екранів:

| Вибір | System Info (`a=53`) | Решта Manager |
|---|---|---|
| `default` | Чинний `manager/views/page/sysinfo.blade.php` | Чинний UI |
| `evo-ui` | Override `manager/media/style/evo-ui/views/page/sysinfo.blade.php` | Наявні views через штатний namespace fallback |

Перемикання: **System configuration → Interface & Features → Manager Theme**,
Save, потім reload усього Manager. Щоб повернути старий інтерфейс, вибрати `default`.
Чинні сайти й нові установки зберігають `default`; автоматичної міграції налаштування немає.

`getTheme()` повертає логічний вибір. `getThemeDir()` / `getThemeUrl()` — фізичну
директорію assets, спільну з `default` для `evo-ui`. `resolveAssetTheme()` містить
одне явне правило для цієї вбудованої теми; довільні сторонні теми не змінюються.
Через той самий mapping завантажуються snippets/chunks, AJAX tree endpoint, TV name
helper, favicon/login media, CSS cache, scripts і системне повідомлення.
Новий theme folder містить лише документацію та override: копій CSS, JS, images,
processors або shell немає.

Native iframe використовує layout, buttons, modal та icons із випущеного Evo UI
й локальні assets пакета. Тут немає legacy CSS/Bootstrap/jQuery/main.js. Решта
сторінок не одержують Evo UI assets лише через встановлення пакета.

Зареєстровано один core Livewire-компонент `evo-core.system-info` через публічний
registry Evo UI. Таблиця не має polling. Реактивні лише дві операції:

- Кнопка з версією PHP ліниво завантажує PHP Info в `<x-evo::modal>`, не змінюючи
  URL або вкладку. Документ ізольований у sandbox iframe без script/same-origin
  дозволів. Поточна тема браузера передається під час відкриття; сервер приймає
  лише чотири штатні назви тем. Вміст не зберігається в public Livewire state.
- Окрема кнопка Reset OPcache виконує reset і показує фактичний результат у тій
  самій кнопці протягом 2,5 с. Є loading/disabled стан; ширина кнопки стабільна,
  окремий рядок повідомлення не створюється. Enabled — звичайний текст.

Візуальні значення використовують кольори Evo UI: enabled — success, disabled —
danger, версії CMS/БД — primary. Дата виділена, час має semantic `<time>` та
Tabler clock. OPcache показує стан, hit rate та used/total memory в одному рядку,
коли вистачає ширини, і переноситься на вузьких екранах. Повна статистика є у
підказці. Reset має primary tone, PHP-кнопка явно підписана `phpinfo`.

Шляхи та URL показують початок і кінець зі скороченням середини через CSS.
Повне значення збережене без змін і доступне в title. Спільний partial додає
маленьку кнопку праворуч для БД, префікса таблиць і всіх EVO path/URL значень.
Копіювання передає саме повне значення конкретної кнопки: синхронна clipboard
подія та Clipboard API забезпечують роботу у вбудованому браузері менеджера.
Після успіху з'являється галочка; є тимчасова підказка й доступний статус.
Новий server endpoint не потрібний.

Вкладки зберігають початкову action icon, навіть якщо сторінка не містить h1.
Для System Info серіалізовано штатний `icon_info_circle`; великий заголовок у
контент не повертається.

## Endpoint і захист

Обидві форми package endpoint (`evo-ui/{path?}` і manager-prefixed PATH_INFO alias)
перевизначені всередині чинної групи `mgr`: login, access та CSRF перевірки діють
і для Livewire. Без сесії обидві форми повертають login; POST без manager token
відхиляється чинним middleware. Не додаємо окрему систему токенів.

Під час реального Livewire запиту на PHP 8.5 знайдено null array-offset deprecation
в умовному GET-CSRF guard; тепер guard перевіряє наявність numeric action перед
індексацією. Mutating checks збережені та покриті тестами.

## Дані, права й responsive

Чинний `SystemInfo` controller і всі значення збережені; business logic не
дублюється в theme. Потрібне право `settings`, а не `logs`. Livewire перевіряє його
на mount, кожну дію та render, public modal state заблокований від прямого запису.
Спільний reset повертає complete/failed/unavailable, включно з `opcache.restrict_api`.
Native render використовує read-only дані й не виконує reset через GET. Старий
інтерфейс зберігає штатний token-protected reset link.

Звичайні значення проходять Blade escaping. PHP Info — контрольований HTML від
`phpinfo()`, який передається як `HtmlString` у sandbox iframe через escaped
`srcdoc`. Помилки прав повертають HTTP 403, а не залежать від відсутнього в цьому
subset Laravel helper `abort()`.

System Info використовує semantic row headers (`scope=row`). Для цієї двоколонкової
таблиці скасовано стандартний `min-width:760px` Evo UI table; fixed layout і переноси
довгих paths не дозволяють їй розширити iframe. CSS обмежений `.evo-system-info`. Таблиця прозора й використовує фон сторінки;
зовнішньої card/frame і повторного великого заголовка немає. Назва залишається у
manager tab/document title. Фінальний рядок не має зайвої нижньої межі.

Деталі виконаних перевірок і обмеження: [VALIDATION.md](VALIDATION.md).
Аудит папки Manager і рекомендовані межі пакета: [MANAGER-BOUNDARIES.md](MANAGER-BOUNDARIES.md).
