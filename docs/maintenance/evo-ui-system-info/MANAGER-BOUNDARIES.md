# Чи переносити manager/ у Composer

## Що є зараз

Інвентаризація tracked Git blobs у базі `ee341135a`, без vendor, local cache,
untracked файлів та нових файлів цього issue. Розміри незжаті; це не розміри ZIP.

| Частина | Файлів | Байти | Відповідальність |
|---|---:|---:|---|
| `manager/media/style` | 54 | 1 446 973 | Default/common CSS, scripts, images, іконки та theme hooks |
| `manager/media/browser` | 428 | 1 307 776 | KCFinder, свої endpoints, ACL, UI та файли тем |
| `manager/media/script` | 53 | 361 234 | Спільні legacy scripts і їхні тести |
| `manager/media/calendar` | 4 | 18 552 | Date picker |
| `manager/views` | 137 | 525 435 | Blade screens, shell/tree, форми та partials |
| `manager/actions` | 37 | 404 942 | Legacy action UI та частина обробки операцій |
| `manager/processors` | 39 | 117 081 | Mutating actions, збереження, видалення, packages/files |
| `manager/includes` | 78 | 393 306 | Шари bootstrap/compatibility, security, lexicon, AJAX |
| Увесь `manager/` | **835** | **4 584 169** | Приблизно **4,37 МіБ**, включно з root entry files |

Велика частина контролерів уже знаходиться у `core/src/Controllers`; Manager
не є автономним frontend-пакетом. Головна точка входу — `manager/index.php`,
диспетчеризація — `manager/routes.php` + `ManagerTheme::$actions`.

## Рішення для 3.6

Не переносити всю папку у vendor під час першого UI пілота. Composer relocation
сам по собі не прибирає ці байти з установленого сайту або full release: потрібні
runtime-файли все одно постачаються. Він також не очищає Git history.

Практичні ризики повного перенесення зараз:

- `MGR_DIR`, `EVO_MANAGER_PATH` і `EVO_MANAGER_URL` підтримують перейменовану папку;
  URL не повинні перетворитися на `/core/vendor/...`.
- Web entry point, AJAX та file-browser endpoints мають бути публічно досяжними,
  але `core/` і `vendor/` блокуються web-server rules.
- Installer, updater і release packaging копіюють/оновлюють публічний manager tree.
- Extras і plugin hooks звертаються до чинних files, shell IDs, callbacks та
  відносних media paths. Наявність Composer package не переписує ці контракти.
- Processors мають ACL/CSRF/path containment. Їх не можна пересунути разом із
  декоративною темою, не перевіривши всі способи виклику.

Тому межа першого кроку проста: **Evo UI володіє UI primitives і Livewire bridge;
core володіє CMS data/actions/permissions; theme володіє screen markup**.
Залишаємо один controller і дві презентації System Info, без копії Manager і
без нового compatibility runtime. Fallback тут означає лише ще не перенесений
core view; Font Awesome aliases або старі extras adapters не повертаються.

## Коли повертатися до package extraction

Після переведення достатньої кількості screens і явного реєстру залишкових
legacy endpoints можна окремим issue оцінити пакет **view/theme assets**. Навіть
тоді потрібні маленький публічний entry point, детерміноване asset publishing,
version coupling до core, update/delete manifest і перевірка renamed Manager.
Спочатку відділяємо відповідальності; потім переносимо файли. Це кандидат на
архітектурну роботу 5.x, а не додаткова умова для поточного System Info.

Цей аудит не дає підстав видаляти KCFinder або processors: вони використовуються.
Наступну економію варто шукати під час фактичної заміни компонентів, а не шляхом
перенесення того самого коду в іншу директорію.
