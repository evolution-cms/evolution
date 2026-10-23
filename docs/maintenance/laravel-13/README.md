# Laravel 13 — залежності Evolution CMS 3.6 (#2473)

Дата перевірки: 2026-10-23. База: `3.6.x`, commit `ce5f18b62`.
Робоча гілка: `middleDuck/evo-2473-laravel13`.
Задача: [evolution-cms/evolution#2473](https://github.com/evolution-cms/evolution/issues/2473).

Оновлено весь Composer dependency graph, включно з major-версіями, які сумісні з
PHP 8.4/8.5/8.6 та поточним ядром. Мінімум уточнено до **PHP 8.4.1**:
цього вимагає Symfony 8.1; PHPUnit 13 теж виключає 8.4.0.
Одна версія `core/composer.lock` використовується для всіх підтримуваних PHP.
Пакети для окремих сайтів у `core/custom/composer.json` тут не оновлювалися.

## Результат аудиту

- `composer outdated --format=json` і `composer outdated --direct --format=json`:
  `{"installed": []}` після повного оновлення — перевірено також транзитивні пакети.
- `composer audit --locked`: відомих вразливостей не знайдено на дату перевірки.
- Production-пакетів у lock: **119 → 116**. Dev-пакетів: **44 → 47**;
  разом **163 → 163**. PHPUnit додав два Sebastian-пакети; `doctrine/deprecations`
  перейшов із production у dev. Новий PHP 8.6 polyfill — production dependency.
- У Git немає нового `vendor/`. Release builder має встановлювати залежності з lock
  через **standalone Composer** з `--no-dev` і включати їх у готовий артефакт.
- Кореневий `phpstan/phpstan` оновлено локально до **2.3.1** в межах `^2.1`.
  Кореневий `composer.lock` і `vendor/` залишаються ігнорованими, як у базовій гілці.

## Що прибрано

| Пакет / вимога | Рішення | Підстава |
| --- | --- | --- |
| `doctrine/dbal` 4.5.0 | Прибрано пряму вимогу та пакет з lock | У `core/`, `manager/`, `install/` немає викликів DBAL. Laravel виконує інспекцію та зміну схем нативно. SQLite install/update і тест rename/drop column проходять без DBAL. |
| `psr/cache` 3.0.0 | Прибрано транзитивно | Був потрібен DBAL; після його видалення інших споживачів немає. |
| `doctrine/deprecations` 1.1.6 | Перенесено транзитивно в dev | Після видалення DBAL потрібен лише інструментам PHPDoc/Pest; у production vendor його більше немає. |
| `phpoption/phpoption` | Прибрано лише пряму вимогу | Залишається транзитивно через `vlucas/phpdotenv` / `graham-campbell/result-type`. Не видаляли потрібну реалізацію. |
| `ralouphie/getallheaders` 3.0.3 | Прибрано транзитивно | Новий Guzzle PSR-7 більше його не потребує. |

Redis, черги, mail, RSS, image conversion, Composer і файлові абстракції не є
зайвими лише тому, що конкретна SQLite-інсталяція не використовує всі ці режими.
Вони мають споживачів у штатних сервісах або підтримуваних конфігураціях, тому залишені.
Extras, яким потрібен DBAL напряму, мають декларувати його у власному Composer manifest.

## Прямі PHP-пакети

Усі `illuminate/*` — компоненти **13.38.0**, пряма вимога `^13.0`.
Транзитивні компоненти того самого набору теж оновлені узгоджено.

| Пакет | Було | Стало | Чому залишено |
| --- | --- | --- | --- |
| `composer/composer` | 2.10.3 | **2.10.3** | Composer API/fallback для встановлення пакетів; semver для контролю оновлень |
| `dg/rss-php` | v1.7 | **v1.7** | RSS-панелі dashboard (`manager/views/page/2.blade.php`) |
| `dmitry-suffi/redis-session-handler` | 1.0.4 | **1.0.4** | Redis session handler (`core/functions/preload.php`) |
| `dragonmantank/cron-expression` | v3.6.1 | **v3.6.1** | Планувальник і `schedule:list` |
| `evolutioncms-services/document-manager` | 1.0.9 | **1.0.9** | Сервіс документів, дублювання ресурсів |
| `evolutioncms-services/user-manager` | 1.0.15 | **1.0.15** | Вхід, користувачі, service provider |
| `guzzlehttp/guzzle` | 7.16.0 | **8.3.0** | HTTP-клієнт Illuminate, PSR-7 URI для посилань RSS |
| `illuminate/cache` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/config` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/console` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/container` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/database` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/events` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/filesystem` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/http` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/log` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/pagination` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/queue` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/redis` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/routing` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/support` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/translation` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/validation` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `illuminate/view` | v12.70.0 | **v13.38.0** | Компонент Laravel, який використовує CMS |
| `james-heinrich/phpthumb` | v1.7.24 | **v1.7.24** | Обробка зображень (`HelperProcessor`) |
| `league/flysystem` | 3.36.0 | **3.36.0** | Файлова абстракція та `VendorPublishCommand` |
| `mockery/mockery` | 1.6.15 | **1.6.15** | Моки в тестах (dev) |
| `pestphp/pest` | v4.7.8 | **v5.3.3** | Набір тестів (dev) |
| `phpmailer/phpmailer` | v7.1.1 | **v7.1.1** | Надсилання пошти |
| `predis/predis` | v3.7.0 | **v3.7.0** | Підтримка Redis у зареєстрованому provider |
| `roave/security-advisories` | dev-master | **dev-master** | Заборона відомих вразливих версій під час resolution (dev) |
| `rosell-dk/exec-with-fallback` | 1.2.0 | **1.2.0** | Запуск CLI-команд у різних hosting/portable середовищах |
| `rosell-dk/webp-convert` | 2.9.5 | **2.9.5** | Конвертація зображень (`HelperProcessor`) |
| `secondnetwork/blade-tabler-icons` | v3.49.0 | **v3.49.0** | Поточні SVG-іконки менеджера |
| `symfony/process` | v7.4.21 | **v8.1.9** | CLI/Composer та виконання процесів |
| `tracy/tracy` | v2.12.1 | **v2.12.2** | Обробка помилок, debug bar та панелі |
| `vlucas/phpdotenv` | v5.7.0 | **v5.7.0** | Завантаження конфігурації середовища |
| `wikimedia/composer-merge-plugin` | v2.1.0 | **v2.1.0** | Підключення `core/custom/composer.json` |

## Зміни сумісності, які перевіряли

- [Laravel 13 upgrade guide](https://laravel.com/docs/13.x/upgrade): власний контейнер
  `AbstractLaravel`, роутер Evo і його parser fallback, Blade compiler/provider,
  моделі та міграції, cache/session defaults, pagination, queue/event contracts.
  Штатні cache/session prefix залишені явними. Власні cache/session config Evo
  зберігають попередню поведінку; нові skeleton-defaults `serializable_classes=false`
  та JSON sessions не підміняють конфігурацію існуючих сайтів автоматично.
  Перехід на allow-list об’єктів cache чи JSON sessions потребує окремого рішення
  для payload-контрактів і активних сесій.
- [Guzzle 8 upgrade guide](https://github.com/guzzle/guzzle/blob/8.3.0/UPGRADING.md):
  строгі значення заголовків і методи, нові PSR-7 та Promises. Додано перевірку
  Illuminate HTTP client через справжній Guzzle handler і резолвінгу RSS URI.
- Tracy **2.12.2** та ParaTest **7.27.4** декларують підтримку PHP 8.6.
  `--ignore-platform-req=php` для перевірок не використовувався.
  ParaTest залишається транзитивним інструментом Pest; усі локальні запуски виконані
  послідовно, без `--parallel`.
- Наявні standalone CLI harness-тести виключені з загального PHPUnit discovery і
  запускаються окремими PHP-процесами через `StandaloneHarnessTest`.
- Skip-виклики у функціональних Pest-тестах звертаються напряму до статичного
  `PHPUnit\Framework\Assert::markTestSkipped()`, щоб не потрапляти в reflection
  proxy Pest, який викликає deprecation на PHP 8.6. Пропуски не приховуються.
- Виправлено macOS case-only rename у file manager: перевірка inode/device доповнює
  `realpath`, зберігаючи заборону заміни іншого файлу, hard link та виходу через symlink.
  Тест ZIP guard більше не сприймає системний `/private/var` як заборонену fixture-папку.

Під час browser smoke знайдено наявну помилку `ManagerActions::makeUrl()`:
модельні посилання Run Module та інші mutating actions не мали CSRF-токена.
Генератор тепер додає поточний токен для save/enable/disable/delete/duplicate/run;
сім тестів перевіряють поточний токен, заміну stale token та read-only/unknown URL.
CSRF middleware і перевірка прав не послаблювалися.

## Повний diff версій у core lock

`—` означає додавання або видалення. Незмінені транзитивні пакети не дублюються.
Для `roave/security-advisories` важливий reference, а не назва `dev-master`.

| Пакет | Було | Стало |
| --- | --- | --- |
| `brianium/paratest` | v7.20.0 | v7.27.4 |
| `brick/math` | 0.14.8 | 1.0.0 |
| `composer/semver` | 3.5.1 | 3.5.2 |
| `doctrine/dbal` | 4.5.0 | — |
| `guzzlehttp/guzzle` | 7.16.0 | 8.3.0 |
| `guzzlehttp/promises` | 2.6.0 | 3.1.0 |
| `guzzlehttp/psr7` | 2.13.2 | 3.2.0 |
| `guzzlehttp/uri-template` | v1.0.11 | v2.0.1 |
| `illuminate/bus` | v12.70.0 | v13.38.0 |
| `illuminate/cache` | v12.70.0 | v13.38.0 |
| `illuminate/collections` | v12.70.0 | v13.38.0 |
| `illuminate/conditionable` | v12.70.0 | v13.38.0 |
| `illuminate/config` | v12.70.0 | v13.38.0 |
| `illuminate/console` | v12.70.0 | v13.38.0 |
| `illuminate/container` | v12.70.0 | v13.38.0 |
| `illuminate/contracts` | v12.70.0 | v13.38.0 |
| `illuminate/database` | v12.70.0 | v13.38.0 |
| `illuminate/events` | v12.70.0 | v13.38.0 |
| `illuminate/filesystem` | v12.70.0 | v13.38.0 |
| `illuminate/http` | v12.70.0 | v13.38.0 |
| `illuminate/log` | v12.70.0 | v13.38.0 |
| `illuminate/macroable` | v12.70.0 | v13.38.0 |
| `illuminate/pagination` | v12.70.0 | v13.38.0 |
| `illuminate/pipeline` | v12.70.0 | v13.38.0 |
| `illuminate/queue` | v12.70.0 | v13.38.0 |
| `illuminate/redis` | v12.70.0 | v13.38.0 |
| `illuminate/reflection` | v12.70.0 | v13.38.0 |
| `illuminate/routing` | v12.70.0 | v13.38.0 |
| `illuminate/session` | v12.70.0 | v13.38.0 |
| `illuminate/support` | v12.70.0 | v13.38.0 |
| `illuminate/translation` | v12.70.0 | v13.38.0 |
| `illuminate/validation` | v12.70.0 | v13.38.0 |
| `illuminate/view` | v12.70.0 | v13.38.0 |
| `pestphp/pest` | v4.7.8 | v5.3.3 |
| `pestphp/pest-plugin` | v4.0.0 | v5.0.0 |
| `pestphp/pest-plugin-arch` | v4.0.2 | v5.0.0 |
| `pestphp/pest-plugin-mutate` | v4.0.1 | v5.0.2 |
| `pestphp/pest-plugin-profanity` | v4.2.1 | v5.0.1 |
| `phpunit/php-code-coverage` | 12.5.8 | 14.4.1 |
| `phpunit/php-file-iterator` | 6.0.2 | 7.0.2 |
| `phpunit/php-invoker` | 6.0.0 | 7.0.0 |
| `phpunit/php-text-template` | 5.0.1 | 6.0.1 |
| `phpunit/php-timer` | 8.0.0 | 9.0.0 |
| `phpunit/phpunit` | 12.5.33 | 13.4.3 |
| `psr/cache` | 3.0.0 | — |
| `ralouphie/getallheaders` | 3.0.3 | — |
| `sebastian/cli-parser` | 4.2.1 | 5.0.1 |
| `sebastian/comparator` | 7.1.8 | 8.4.0 |
| `sebastian/complexity` | 5.0.0 | 6.0.0 |
| `sebastian/diff` | 7.0.1 | 9.0.1 |
| `sebastian/environment` | 8.1.2 | 9.3.3 |
| `sebastian/exporter` | 7.0.3 | 8.2.1 |
| `sebastian/file-filter` | — | 1.0.0 |
| `sebastian/git-state` | — | 1.1.0 |
| `sebastian/global-state` | 8.0.3 | 9.0.1 |
| `sebastian/lines-of-code` | 4.0.1 | 5.0.2 |
| `sebastian/object-enumerator` | 7.0.0 | 8.1.0 |
| `sebastian/object-reflector` | 5.0.0 | 6.1.0 |
| `sebastian/recursion-context` | 7.0.1 | 8.0.1 |
| `sebastian/type` | 6.0.4 | 7.0.2 |
| `sebastian/version` | 6.0.1 | 7.0.1 |
| `symfony/clock` | v7.4.21 | v8.1.9 |
| `symfony/console` | v7.4.21 | v8.1.9 |
| `symfony/error-handler` | v7.4.20 | v8.1.8 |
| `symfony/event-dispatcher` | v7.4.17 | v8.1.5 |
| `symfony/filesystem` | v7.4.21 | v8.1.9 |
| `symfony/finder` | v7.4.20 | v8.1.8 |
| `symfony/http-foundation` | v7.4.21 | v8.1.9 |
| `symfony/http-kernel` | v7.4.21 | v8.1.9 |
| `symfony/mime` | v7.4.19 | v8.1.7 |
| `symfony/polyfill-php86` | — | v1.43.0 |
| `symfony/process` | v7.4.21 | v8.1.9 |
| `symfony/routing` | v7.4.21 | v8.1.9 |
| `symfony/string` | v7.4.21 | v8.1.9 |
| `symfony/translation` | v7.4.17 | v8.1.5 |
| `symfony/var-dumper` | v7.4.21 | v8.1.9 |
| `tracy/tracy` | v2.12.1 | v2.12.2 |
| `roave/security-advisories` reference | `1d3860eceb9c8a3c76ade46a8ef7896a63802048` | `02ec6ff16e002cc53135c7b1549df9625019f6ff` |

Результати виконання та межі перевірки: [VALIDATION.md](VALIDATION.md).
