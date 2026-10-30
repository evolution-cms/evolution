<?php
/**
 * Evolution CMS validation.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 23:20:04
 *
 * @language Latvian
 * @package Evolution CMS
 * @subpackage core
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Lauks :attribute ir jāapstiprina.',
    'active_url' => 'Lauks :attribute nav derīgs URL.',
    'after' => 'Lauka :attribute datumam jābūt pēc :date.',
    'after_or_equal' => 'Lauka :attribute datumam jābūt :date vai vēlākam.',
    'alpha' => 'Lauks :attribute drīkst saturēt tikai burtus.',
    'alpha_dash' => 'Lauks :attribute drīkst saturēt tikai burtus, ciparus, defises un pasvītrojumus.',
    'alpha_num' => 'Lauks :attribute drīkst saturēt tikai burtus un ciparus.',
    'array' => 'Laukam :attribute jābūt masīvam.',
    'before' => 'Lauka :attribute datumam jābūt pirms :date.',
    'before_or_equal' => 'Lauka :attribute datumam jābūt :date vai agrākam.',
    'between' => [
        'numeric' => 'Lauka :attribute vērtībai jābūt no :min līdz :max.',
        'file' => 'Faila :attribute lielumam jābūt no :min līdz :max kilobaitiem.',
        'string' => 'Laukam :attribute jāsatur no :min līdz :max rakstzīmēm.',
        'array' => 'Laukam :attribute jāsatur no :min līdz :max elementiem.',
    ],
    'boolean' => 'Lauka :attribute vērtībai jābūt patiesai vai aplamai.',
    'confirmed' => 'Lauka :attribute apstiprinājums nesakrīt.',
    'date' => 'Laukā :attribute nav derīgs datums.',
    'date_equals' => 'Lauka :attribute datumam jābūt :date.',
    'date_format' => 'Lauks :attribute neatbilst formātam :format.',
    'different' => 'Laukiem :attribute un :other jābūt atšķirīgiem.',
    'digits' => 'Laukam :attribute jāsatur :digits cipari.',
    'digits_between' => 'Laukam :attribute jāsatur no :min līdz :max cipariem.',
    'dimensions' => 'Attēla :attribute izmēri nav derīgi.',
    'distinct' => 'Laukā :attribute ir atkārtota vērtība.',
    'email' => 'Laukam :attribute jābūt derīgai e-pasta adresei.',
    'ends_with' => 'Laukam :attribute jābeidzas ar vienu no šīm vērtībām: :values.',
    'exists' => 'Atlasītā lauka :attribute vērtība nav derīga.',
    'file' => 'Laukam :attribute jābūt failam.',
    'filled' => 'Laukā :attribute jābūt vērtībai.',
    'gt' => [
        'numeric' => 'Lauka :attribute vērtībai jābūt lielākai par :value.',
        'file' => 'Faila :attribute lielumam jābūt lielākam par :value kilobaitiem.',
        'string' => 'Laukam :attribute jāsatur vairāk par :value rakstzīmēm.',
        'array' => 'Laukam :attribute jāsatur vairāk par :value elementiem.',
    ],
    'gte' => [
        'numeric' => 'Lauka :attribute vērtībai jābūt vismaz :value.',
        'file' => 'Faila :attribute lielumam jābūt vismaz :value kilobaitiem.',
        'string' => 'Laukam :attribute jāsatur vismaz :value rakstzīmes.',
        'array' => 'Laukam :attribute jāsatur vismaz :value elementi.',
    ],
    'image' => 'Laukam :attribute jābūt attēlam.',
    'in' => 'Atlasītā lauka :attribute vērtība nav derīga.',
    'in_array' => 'Lauks :attribute nepastāv laukā :other.',
    'integer' => 'Laukam :attribute jābūt veselam skaitlim.',
    'ip' => 'Laukam :attribute jābūt derīgai IP adresei.',
    'ipv4' => 'Laukam :attribute jābūt derīgai IPv4 adresei.',
    'ipv6' => 'Laukam :attribute jābūt derīgai IPv6 adresei.',
    'json' => 'Laukam :attribute jābūt derīgai JSON virknei.',
    'lt' => [
        'numeric' => 'Lauka :attribute vērtībai jābūt mazākai par :value.',
        'file' => 'Faila :attribute lielumam jābūt mazākam par :value kilobaitiem.',
        'string' => 'Laukam :attribute jāsatur mazāk par :value rakstzīmēm.',
        'array' => 'Laukam :attribute jāsatur mazāk par :value elementiem.',
    ],
    'lte' => [
        'numeric' => 'Lauka :attribute vērtībai jābūt ne lielākai par :value.',
        'file' => 'Faila :attribute lielumam jābūt ne lielākam par :value kilobaitiem.',
        'string' => 'Laukam :attribute jāsatur ne vairāk par :value rakstzīmēm.',
        'array' => 'Lauks :attribute nedrīkst saturēt vairāk par :value elementiem.',
    ],
    'max' => [
        'numeric' => 'Lauka :attribute vērtība nedrīkst pārsniegt :max.',
        'file' => 'Faila :attribute lielums nedrīkst pārsniegt :max kilobaitus.',
        'string' => 'Lauks :attribute nedrīkst saturēt vairāk par :max rakstzīmēm.',
        'array' => 'Lauks :attribute nedrīkst saturēt vairāk par :max elementiem.',
    ],
    'mimes' => 'Laukam :attribute jābūt šāda tipa failam: :values.',
    'mimetypes' => 'Laukam :attribute jābūt šāda tipa failam: :values.',
    'min' => [
        'numeric' => 'Lauka :attribute vērtībai jābūt vismaz :min.',
        'file' => 'Faila :attribute lielumam jābūt vismaz :min kilobaitiem.',
        'string' => 'Laukam :attribute jāsatur vismaz :min rakstzīmes.',
        'array' => 'Laukam :attribute jāsatur vismaz :min elementi.',
    ],
    'not_in' => 'Atlasītā lauka :attribute vērtība nav derīga.',
    'not_regex' => 'Lauka :attribute formāts nav derīgs.',
    'numeric' => 'Laukam :attribute jābūt skaitlim.',
    'password' => 'Parole nav pareiza.',
    'present' => 'Laukam :attribute jābūt norādītam.',
    'regex' => 'Lauka :attribute formāts nav derīgs.',
    'required' => 'Lauks :attribute ir obligāts.',
    'required_if' => 'Lauks :attribute ir obligāts, ja lauka :other vērtība ir :value.',
    'required_unless' => 'Lauks :attribute ir obligāts, ja vien :other nav starp :values.',
    'required_with' => 'Lauks :attribute ir obligāts, ja ir norādīts :values.',
    'required_with_all' => 'Lauks :attribute ir obligāts, ja ir norādītas visas :values vērtības.',
    'required_without' => 'Lauks :attribute ir obligāts, ja nav norādīts :values.',
    'required_without_all' => 'Lauks :attribute ir obligāts, ja nav norādīta neviena no :values vērtībām.',
    'same' => ':attribute un :other ir jāsakrīt.',
    'size' => [
        'numeric' => ':attribute jābūt :size.',
        'file' => ':attribute lielumam jābūt :size kilobaitiem.',
        'string' => ':attribute garumam jābūt :size rakstzīmēm.',
        'array' => ':attribute jāsatur :size elementi.',
    ],
    'starts_with' => ':attribute jāsākas ar kādu no šīm vērtībām: :values.',
    'string' => ':attribute jābūt virknei.',
    'timezone' => ':attribute jābūt derīgai laika joslai.',
    'unique' => ':attribute jau tiek izmantots.',
    'uploaded' => 'Neizdevās augšupielādēt :attribute.',
    'url' => ':attribute formāts nav derīgs.',
    'uuid' => ':attribute jābūt derīgam UUID.',

    /*
    |--------------------------------------------------------------------------
    | Pielāgoti validācijas paziņojumi
    |--------------------------------------------------------------------------
    |
    | Šeit var norādīt pielāgotus atribūtu validācijas paziņojumus, to
    | nosaukumiem izmantojot formu "attribute.rule". Tas ļauj ātri
    | norādīt pielāgotu paziņojumu konkrētam atribūta noteikumam.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'pielāgots-paziņojums',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pielāgoti validācijas atribūti
    |--------------------------------------------------------------------------
    |
    | Šīs valodas rindas izmanto, lai aizstātu atribūta vietturi
    | ar lasītājam saprotamāku tekstu, piemēram, "E-pasta adrese"
    | vārda "email" vietā. Tas palīdz padarīt paziņojumus izteiksmīgākus.
    |
    */

    'attributes' => [],

];
