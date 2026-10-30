<?php
/**
 * Evolution CMS validation.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 23:20:04
 *
 * @language Lithuanian
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

    'accepted' => 'Laukas :attribute turi būti priimtas.',
    'active_url' => 'Laukas :attribute nėra tinkamas URL adresas.',
    'after' => 'Lauko :attribute data turi būti vėlesnė nei :date.',
    'after_or_equal' => 'Lauko :attribute data turi būti :date arba vėlesnė.',
    'alpha' => 'Lauke :attribute gali būti tik raidės.',
    'alpha_dash' => 'Lauke :attribute gali būti tik raidės, skaitmenys, brūkšneliai ir pabraukimo ženklai.',
    'alpha_num' => 'Lauke :attribute gali būti tik raidės ir skaitmenys.',
    'array' => 'Laukas :attribute turi būti masyvas.',
    'before' => 'Lauko :attribute data turi būti ankstesnė nei :date.',
    'before_or_equal' => 'Lauko :attribute data turi būti :date arba ankstesnė.',
    'between' => [
        'numeric' => 'Lauko :attribute reikšmė turi būti tarp :min ir :max.',
        'file' => 'Failo :attribute dydis turi būti nuo :min iki :max kilobaitų.',
        'string' => 'Lauke :attribute turi būti nuo :min iki :max simbolių.',
        'array' => 'Lauke :attribute turi būti nuo :min iki :max elementų.',
    ],
    'boolean' => 'Lauko :attribute reikšmė turi būti „true“ arba „false“.',
    'confirmed' => 'Lauko :attribute patvirtinimas nesutampa.',
    'date' => 'Lauke :attribute nurodyta netinkama data.',
    'date_equals' => 'Lauko :attribute data turi būti :date.',
    'date_format' => 'Laukas :attribute neatitinka formato :format.',
    'different' => 'Laukai :attribute ir :other turi skirtis.',
    'digits' => 'Lauke :attribute turi būti :digits skaitmenų.',
    'digits_between' => 'Lauke :attribute turi būti nuo :min iki :max skaitmenų.',
    'dimensions' => 'Paveikslėlio :attribute matmenys netinkami.',
    'distinct' => 'Lauke :attribute yra pasikartojanti reikšmė.',
    'email' => 'Lauke :attribute turi būti tinkamas el. pašto adresas.',
    'ends_with' => 'Laukas :attribute turi baigtis viena iš šių reikšmių: :values.',
    'exists' => 'Pasirinkta lauko :attribute reikšmė netinkama.',
    'file' => 'Laukas :attribute turi būti failas.',
    'filled' => 'Laukas :attribute turi turėti reikšmę.',
    'gt' => [
        'numeric' => 'Lauko :attribute reikšmė turi būti didesnė nei :value.',
        'file' => 'Failo :attribute dydis turi būti didesnis nei :value kilobaitų.',
        'string' => 'Lauke :attribute turi būti daugiau nei :value simbolių.',
        'array' => 'Lauke :attribute turi būti daugiau nei :value elementų.',
    ],
    'gte' => [
        'numeric' => 'Lauko :attribute reikšmė turi būti ne mažesnė nei :value.',
        'file' => 'Failo :attribute dydis turi būti ne mažesnis nei :value kilobaitų.',
        'string' => 'Lauke :attribute turi būti bent :value simbolių.',
        'array' => 'Lauke :attribute turi būti bent :value elementų.',
    ],
    'image' => 'Laukas :attribute turi būti paveikslėlis.',
    'in' => 'Pasirinkta lauko :attribute reikšmė netinkama.',
    'in_array' => 'Lauko :attribute nėra lauke :other.',
    'integer' => 'Laukas :attribute turi būti sveikasis skaičius.',
    'ip' => 'Laukas :attribute turi būti tinkamas IP adresas.',
    'ipv4' => 'Laukas :attribute turi būti tinkamas IPv4 adresas.',
    'ipv6' => 'Laukas :attribute turi būti tinkamas IPv6 adresas.',
    'json' => 'Laukas :attribute turi būti tinkama JSON eilutė.',
    'lt' => [
        'numeric' => 'Lauko :attribute reikšmė turi būti mažesnė nei :value.',
        'file' => 'Failo :attribute dydis turi būti mažesnis nei :value kilobaitų.',
        'string' => 'Lauke :attribute turi būti mažiau nei :value simbolių.',
        'array' => 'Lauke :attribute turi būti mažiau nei :value elementų.',
    ],
    'lte' => [
        'numeric' => 'Lauko :attribute reikšmė turi būti ne didesnė nei :value.',
        'file' => 'Failo :attribute dydis turi būti ne didesnis nei :value kilobaitų.',
        'string' => 'Lauke :attribute gali būti ne daugiau kaip :value simbolių.',
        'array' => 'Lauke :attribute gali būti ne daugiau kaip :value elementų.',
    ],
    'max' => [
        'numeric' => 'Lauko :attribute reikšmė negali būti didesnė nei :max.',
        'file' => 'Failo :attribute dydis negali viršyti :max kilobaitų.',
        'string' => 'Lauke :attribute gali būti ne daugiau kaip :max simbolių.',
        'array' => 'Lauke :attribute gali būti ne daugiau kaip :max elementų.',
    ],
    'mimes' => 'Laukas :attribute turi būti šio tipo failas: :values.',
    'mimetypes' => 'Laukas :attribute turi būti šio tipo failas: :values.',
    'min' => [
        'numeric' => 'Lauko :attribute reikšmė turi būti bent :min.',
        'file' => 'Failo :attribute dydis turi būti bent :min kilobaitų.',
        'string' => 'Lauke :attribute turi būti bent :min simbolių.',
        'array' => 'Lauke :attribute turi būti bent :min elementų.',
    ],
    'not_in' => 'Pasirinkta lauko :attribute reikšmė netinkama.',
    'not_regex' => 'Lauko :attribute formatas netinkamas.',
    'numeric' => 'Laukas :attribute turi būti skaičius.',
    'password' => 'Slaptažodis neteisingas.',
    'present' => 'Laukas :attribute turi būti pateiktas.',
    'regex' => 'Lauko :attribute formatas netinkamas.',
    'required' => 'Laukas :attribute yra privalomas.',
    'required_if' => 'Laukas :attribute yra privalomas, kai lauko :other reikšmė yra :value.',
    'required_unless' => 'Laukas :attribute yra privalomas, nebent :other yra tarp :values.',
    'required_with' => 'Laukas :attribute yra privalomas, kai pateikta :values.',
    'required_with_all' => 'Laukas :attribute yra privalomas, kai pateiktos visos :values reikšmės.',
    'required_without' => 'Laukas :attribute yra privalomas, kai nepateikta :values.',
    'required_without_all' => 'Laukas :attribute yra privalomas, kai nepateikta nė viena :values reikšmė.',
    'same' => ':attribute ir :other turi sutapti.',
    'size' => [
        'numeric' => ':attribute turi būti :size.',
        'file' => ':attribute dydis turi būti :size kilobaitų.',
        'string' => ':attribute turi būti :size simbolių ilgio.',
        'array' => ':attribute turi turėti :size elementų.',
    ],
    'starts_with' => ':attribute turi prasidėti viena iš šių reikšmių: :values.',
    'string' => ':attribute turi būti eilutė.',
    'timezone' => ':attribute turi būti galiojanti laiko juosta.',
    'unique' => ':attribute jau naudojamas.',
    'uploaded' => 'Nepavyko įkelti :attribute.',
    'url' => ':attribute formatas netinkamas.',
    'uuid' => ':attribute turi būti galiojantis UUID.',

    /*
    |--------------------------------------------------------------------------
    | Pasirinktiniai tikrinimo pranešimai
    |--------------------------------------------------------------------------
    |
    | Čia galite nurodyti pasirinktinius atributų tikrinimo pranešimus, jų
    | pavadinimams naudodami formą "attribute.rule". Taip galima greitai
    | nurodyti pasirinktinį pranešimą konkrečiai atributo taisyklei.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'pasirinktinis-pranešimas',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pasirinktiniai tikrinimo atributai
    |--------------------------------------------------------------------------
    |
    | Šios kalbos eilutės naudojamos atributo vietaženkliui pakeisti
    | lengviau skaitomu tekstu, pavyzdžiui, "El. pašto adresas" vietoj
    | "email". Tai padeda aiškiau perteikti mūsų pranešimus.
    |
    */

    'attributes' => [],

];
