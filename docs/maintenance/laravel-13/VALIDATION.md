# Перевірка Laravel 13 / PHP 8.4–8.6 (#2473)

Локальна перевірка на macOS arm64, 2026-10-23. Один checkout для змін і **одна
ізольована встановлена CMS з однією SQLite БД** для виконання тестів. Основний
репозиторій не отримав `core/vendor/` і не використовувався як тестовий сайт.

## Середовище

Використані власні runtime та інструменти Portable EVO, без Homebrew PHP:

| PHP | Відносний шлях у Portable EVO |
| --- | --- |
| 8.4.27 | `runtimes/macos-arm64/php/php` |
| 8.5.12 | `runtimes/macos-arm64/php-8.5.12/php` |
| 8.6.0RC4 | `runtimes/macos-arm64/php-8.6.0RC4/php` |

Composer: `tools/composer/composer.phar`; Git: `tools/git/bin/git`;
CA: `tools/certs/cacert.pem`. Застосовано portable ini і окремі ini/log files
для кожного PHP, з `E_ALL`. OPcache extension з runtime 8.4 не завантажувався
в 8.5/8.6, де OPcache вже зібраний у binary. Сервер слухав лише loopback.

Залежності резолвилися на мінімальній гілці 8.4, standalone Composer,
`composer update --with-all-dependencies --prefer-stable --no-scripts --no-progress --prefer-dist`.
Окремо перевірено звичайний `composer install` з виконанням `package:discover`.
Під час уточнення manifest minimum до 8.4.1 оновлено лише content hash lock;
масиви `packages` і `packages-dev` залишилися ідентичними.
Фінальна матриця виконана вже з остаточним lock і виправленими URL.

## Матриця

| Portable PHP | Pest | PHPStan | Platform / SQLite update |
| --- | --- | --- | --- |
| **8.4.27** | **1 954 passed**, 11 skipped; 19 891 assertions; 27.23s | **0 errors** | **passed** |
| **8.5.12** | **1 954 passed**, 11 skipped; 19 891 assertions; 22.72s | **0 errors** | **passed** |
| **8.6.0RC4** | **1 954 passed**, 11 skipped; 19 891 assertions; 22.64s | **0 errors** | **passed** |

У кожному проході — 0 warnings, 0 risky, 0 deprecations. Lock SHA-256:
`6d5b2dd5d643c6d14d84d0082c2f50a1683975509318b727df0d9cb9042cf8bd`.


Порядок кожного проходу: **8.4 → 8.5 → 8.6**. БД не перевстановлювалася і vendor
не оновлювався між PHP. Повторний прохід робився після виправлень тестових skip API
та знайденої помилки URL, а не для отримання випадково зеленого результату.

Для кожного PHP:

```sh
cd core
composer validate --no-check-publish
composer check-platform-reqs
php vendor/bin/pest --compact --fail-on-warning --fail-on-risky --fail-on-deprecation --display-warnings --display-deprecations
cd ..
composer analyze
php .github/docker/ci/smoke.php "$PWD"
cd install
php cli-install.php --typeInstall=2 --removeInstall=n --skipComposer=y < /dev/null
cd ..
php .github/docker/ci/smoke.php "$PWD"
php core/artisan list --raw
```

`php` і `composer` у цих командах мають посилатися на вибраний portable PHP і
standalone PHAR, а не на системні binaries. `smoke.php` потребує тих самих
`EVO_ADMIN`, `EVO_ADMIN_EMAIL`, `EVO_LANGUAGE`, які передані інсталятору;
`EVO_SMOKE_BASELINE` — окремий локальний файл для цієї БД.

## Інсталяція, update, production

- Чиста SQLite-інсталяція на 8.4.27 завершилася успішно: міграції, seeders,
  користувач, ролі, шаблон, документ, bundled plugins/module, cache.
- Повторні CLI updates на всіх трьох PHP пройшли без PHP diagnostics; `smoke.php`
  підтвердив схему, встановлені дані та стабільні counts 16 таблиць до/після update.
- Перший вхід у Manager штатно створює `sys_files_checksum`: settings 50 → 51.
  Це помітив count guard. Після підтвердження джерела запису зафіксована нова
  baseline для подальших перевірок; update сам по собі counts не змінював.
- Окремо встановлено `composer install --no-dev` з того самого lock.
  На 8.4, 8.5 і 8.6 перевірено `cache:clear-full`, `route:list --json`, `doc:list`,
  `tpl:list`, `tv:list`, `schedule:list`, front page HTTP 200 з install-success
  document та Manager login HTTP 200. Dev tooling для роботи над CMS потім відновлено.
- Browser smoke: реальний вхід на 8.4, збереження шаблону на 8.5, збереження сесії
  після перемикання PHP, перегляд users, settings, modules та System Info на 8.6.
  Додаткові результати фінального browser smoke наведено нижче.
- JavaScript suite: **102 passed**, 0 failures (`node --test` для двох штатних
  директорій `manager/media/script/tests` і `manager/media/browser/mcpuk/js/tests`).
- `composer outdated` і `composer outdated --direct`: немає новіших пакетів у результаті,
  включно з транзитивними;
  `composer audit --locked`: відомих вразливостей не знайдено на дату перевірки.
- Workflow YAML розібраний PyYAML; shell scripts пройшли `bash -n`; `git diff --check` чистий.

## Явні пропуски й межі доказу

11 skipped у локальному Pest:

- 5 Windows junction cases — macOS не має Windows junction API;
- 3 case-sensitivity cases — поточний filesystem не дає потрібної комбінації
  окремих differently-cased entries або позначеного Windows/case-insensitive сценарію;
- 1 `.htaccess` Git comparison — тестова копія створена з source archive без `.git`;
- 2 старі committed-classmap checks — committed `core/vendor` більше не існує.

`ng.inx`, який має `export-ignore`, додано у тестову копію з checkout, щоб
security test конфігурації сервера не пропускав файл через спосіб копіювання.
Standalone CLI harness scripts виконувалися, а не були виключені з покриття:
їх запускає `StandaloneHarnessTest` окремими PHP-процесами.

Це не перевірка MySQL/PostgreSQL, Windows/Linux binaries, реального Redis/SMTP,
зовнішніх image-converter executables чи сторонніх extras. Такі інтеграції не
позначені перевіреними лише на підставі SQLite suite. PHP 8.6.0RC4 — prerelease,
а не доказ сумісності з майбутньою GA-версією. Ці результати отримано локально до публікації PR. GitHub Actions і Docker
build не є джерелом наведеної матриці; їхній статус перевіряється окремо в PR.

## CI та release bootstrap

- Analysis job тепер встановлює core dependencies перед root PHPStan.
- Install/upgrade jobs готують production vendor із lock до installer/update.
- CI Docker image використовує standalone Composer з офіційного Composer image;
  prerelease test не запускає Composer з vendor, який він оновлює.
- PHP 8.6 RC job не ігнорує platform requirements і більше не перетворює невдалий
  build/test на успішний workflow. Pest jobs відхиляють warning/risky/deprecation.
- Release build уже використовує `composer install --no-dev`; цей контракт збережено.

## Локальні докази

Логи і screenshots знаходяться в локально ігнорованій `output/laravel-2473/`
цього checkout (правило у `.git/info/exclude`).
Вони не потрапляють у release чи Git. Сам disposable site і його БД збережені
в тимчасовій директорії; шлях є в локальному `evidence.json`.

Фінальний browser smoke з production vendor на **PHP 8.6.0RC4**:

- Сесія з 8.4/8.5 залишилася чинною після перемикання PHP.
- Users показує встановленого адміністратора; settings і module list рендеряться.
- Run Module містить поточний CSRF token і відкриває **Package Management**;
  сервер підтвердив HTTP 200. Пакети з каталогу не встановлювалися.
- System Info показує **3.6.0 / PHP 8.6.0RC4 / SQLite 3.51.2**.
- У фінальному browser-server log немає PHP diagnostics, HTTP 403 чи 500.
- Докази: `system-info-php86.png`, `extras-php86.png`, `template-php85.png`,
  `manager-php84.png` у локальній папці evidence.

Після початкової перевірки HTTP-сервер зупиняли, а dev dependencies відновлено
на цій самій інсталяції. На наступний запит користувача сервер запущений знову
на PHP 8.6 RC4 з Tracy і залишений для ручного перегляду (див. нижче).
Локальна кваліфікація завершена в окремій гілці перед публікацією PR у `3.6.x`.
Issue: [#2473](https://github.com/evolution-cms/evolution/issues/2473).


## Фінальний прохід із Tracy

На окремий запит користувача увімкнено **Tracy 2.12.2** на тій самій тестовій
інсталяції, **PHP 8.6.0RC4**. Production config у репозиторії не змінювався.
Локальні overrides: `core/custom/config/tracy/active.php` повертає `'manager'`,
`hidden.php` — `false`. Штатні сім панелей успадковуються з core config.
Після зміни config виконано `php core/artisan cache:clear-full`.

Tracy ставить `E_ALL`; warning/deprecation не приховувалися зміною error_reporting.
На тимчасовому автентифікованому маршруті навмисно створені `E_USER_WARNING`
і `E_USER_DEPRECATED`. Обидва видно у Warnings з файлом і рядком; після перевірки
маршрут видалено, cache очищено. Це штучні діагностичні повідомлення, а не дефекти CMS.

| Перевірка | Результат |
| --- | --- |
| Front page, документ 1 | Rendered, Tracy без warnings/deprecations |
| Dashboard | Без діагностики |
| Редактор ресурсу і збереження незміненого контенту | POST action 5: HTTP 200, без діагностики |
| Templates, TVs, Chunks, Snippets, Plugins, Modules | Всі шість вкладок відкриті, без діагностики |
| Редактор шаблону | Без діагностики |
| Users, Roles, Access permissions | Без діагностики |
| Сім секцій System configuration | Відкриті, без діагностики; налаштування не зберігалися |
| Package Management / Extras | Відкрився, без діагностики; пакети не інсталювалися |
| Classic Manage Files | Без діагностики; файли не змінювалися |
| KCFinder | Відкриття й AJAX Refresh без діагностики |
| Backup, Search, Schedule, System Events, Manager Actions, System Info | Без діагностики; backup/jobs не запускалися |
| Tracy SQL, Routing, Request, Session, Event, View, Auth | Всі сім панелей розкриваються коректно |

Фінальний AX state містить **16 Tracy bars**, без diagnostic links/messages.
Server log не містить PHP diagnostics і відповідей 403/500. Додаткових дефектів
у цьому проході не знайдено; нові зміни production-коду не знадобилися.

Локальний менеджер залишено відкритим: `http://localhost:8895/manager/`.
Докази: `tracy-manager-php86.png`, `tracy-warning-deprecation-probe.png`,
`tracy-final-check.json`, `tracy-http-8.6.log` у локальному `output/laravel-2473/`.
Цей ручний Tracy-прохід зроблено на PHP 8.6 RC4; попередня повна автоматична
матриця 8.4/8.5/8.6 наведена вище. Межі SQLite/macOS та неперевірених інтеграцій
залишаються чинними.
