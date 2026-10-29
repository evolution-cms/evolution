<?php
/**
 * Evolution CMS validation.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Estonian
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

    'accepted' => 'Väli :attribute tuleb aktsepteerida.',
    'active_url' => 'Väli :attribute ei ole kehtiv URL.',
    'after' => 'Välja :attribute kuupäev peab olema hilisem kui :date.',
    'after_or_equal' => 'Välja :attribute kuupäev peab olema :date või hilisem.',
    'alpha' => 'Väli :attribute võib sisaldada ainult tähti.',
    'alpha_dash' => 'Väli :attribute võib sisaldada ainult tähti, numbreid, sidekriipse ja allkriipse.',
    'alpha_num' => 'Väli :attribute võib sisaldada ainult tähti ja numbreid.',
    'array' => 'Väli :attribute peab olema massiiv.',
    'before' => 'Välja :attribute kuupäev peab olema varasem kui :date.',
    'before_or_equal' => 'Välja :attribute kuupäev peab olema :date või varasem.',
    'between' => [
        'numeric' => 'Välja :attribute väärtus peab olema vahemikus :min kuni :max.',
        'file' => 'Faili :attribute suurus peab olema vahemikus :min kuni :max kilobaiti.',
        'string' => 'Välja :attribute pikkus peab olema vahemikus :min kuni :max märki.',
        'array' => 'Väli :attribute peab sisaldama :min kuni :max elementi.',
    ],
    'boolean' => 'Välja :attribute väärtus peab olema tõene või väär.',
    'confirmed' => 'Välja :attribute kinnitus ei ühti.',
    'date' => 'Väli :attribute ei ole kehtiv kuupäev.',
    'date_equals' => 'Välja :attribute kuupäev peab olema :date.',
    'date_format' => 'Väli :attribute ei vasta vormingule :format.',
    'different' => 'Väljad :attribute ja :other peavad olema erinevad.',
    'digits' => 'Väli :attribute peab koosnema :digits numbrist.',
    'digits_between' => 'Väli :attribute peab koosnema :min kuni :max numbrist.',
    'dimensions' => 'Pildi :attribute mõõtmed on sobimatud.',
    'distinct' => 'Väljal :attribute on korduv väärtus.',
    'email' => 'Väli :attribute peab olema kehtiv e-posti aadress.',
    'ends_with' => 'Väli :attribute peab lõppema ühega järgmistest: :values.',
    'exists' => 'Valitud :attribute on sobimatu.',
    'file' => 'Väli :attribute peab olema fail.',
    'filled' => 'Väljal :attribute peab olema väärtus.',
    'gt' => [
        'numeric' => 'Välja :attribute väärtus peab olema suurem kui :value.',
        'file' => 'Faili :attribute suurus peab olema suurem kui :value kilobaiti.',
        'string' => 'Välja :attribute pikkus peab olema suurem kui :value märki.',
        'array' => 'Väli :attribute peab sisaldama rohkem kui :value elementi.',
    ],
    'gte' => [
        'numeric' => 'Välja :attribute väärtus peab olema vähemalt :value.',
        'file' => 'Faili :attribute suurus peab olema vähemalt :value kilobaiti.',
        'string' => 'Välja :attribute pikkus peab olema vähemalt :value märki.',
        'array' => 'Väli :attribute peab sisaldama vähemalt :value elementi.',
    ],
    'image' => 'Väli :attribute peab olema pilt.',
    'in' => 'Valitud :attribute on sobimatu.',
    'in_array' => 'Välja :attribute ei leidu väljal :other.',
    'integer' => 'Väli :attribute peab olema täisarv.',
    'ip' => 'Väli :attribute peab olema kehtiv IP-aadress.',
    'ipv4' => 'Väli :attribute peab olema kehtiv IPv4-aadress.',
    'ipv6' => 'Väli :attribute peab olema kehtiv IPv6-aadress.',
    'json' => 'Väli :attribute peab olema kehtiv JSON-string.',
    'lt' => [
        'numeric' => 'Välja :attribute väärtus peab olema väiksem kui :value.',
        'file' => 'Faili :attribute suurus peab olema väiksem kui :value kilobaiti.',
        'string' => 'Välja :attribute pikkus peab olema väiksem kui :value märki.',
        'array' => 'Väli :attribute peab sisaldama vähem kui :value elementi.',
    ],
    'lte' => [
        'numeric' => 'Välja :attribute väärtus võib olla kuni :value.',
        'file' => 'Faili :attribute suurus võib olla kuni :value kilobaiti.',
        'string' => 'Välja :attribute pikkus võib olla kuni :value märki.',
        'array' => 'Väli :attribute võib sisaldada kuni :value elementi.',
    ],
    'max' => [
        'numeric' => 'Välja :attribute väärtus võib olla kuni :max.',
        'file' => 'Faili :attribute suurus võib olla kuni :max kilobaiti.',
        'string' => 'Välja :attribute pikkus võib olla kuni :max märki.',
        'array' => 'Väli :attribute võib sisaldada kuni :max elementi.',
    ],
    'mimes' => 'Väli :attribute peab olema järgmist tüüpi fail: :values.',
    'mimetypes' => 'Väli :attribute peab olema järgmist tüüpi fail: :values.',
    'min' => [
        'numeric' => 'Välja :attribute väärtus peab olema vähemalt :min.',
        'file' => 'Faili :attribute suurus peab olema vähemalt :min kilobaiti.',
        'string' => 'Välja :attribute pikkus peab olema vähemalt :min märki.',
        'array' => 'Väli :attribute peab sisaldama vähemalt :min elementi.',
    ],
    'not_in' => 'Valitud :attribute on sobimatu.',
    'not_regex' => 'Välja :attribute vorming on sobimatu.',
    'numeric' => 'Väli :attribute peab olema arv.',
    'password' => 'Parool on vale.',
    'present' => 'Väli :attribute peab olema olemas.',
    'regex' => 'Välja :attribute vorming on sobimatu.',
    'required' => 'Väli :attribute on kohustuslik.',
    'required_if' => 'Väli :attribute on kohustuslik, kui :other on :value.',
    'required_unless' => 'Väli :attribute on nõutud, välja arvatud juhul, kui :other on üks väärtustest :values.',
    'required_with' => 'Väli :attribute on nõutud, kui :values on olemas.',
    'required_with_all' => 'Väli :attribute on nõutud, kui kõik väljad :values on olemas.',
    'required_without' => 'Väli :attribute on nõutud, kui :values puudub.',
    'required_without_all' => 'Väli :attribute on nõutud, kui kõik väljad :values puuduvad.',
    'same' => 'Väljad :attribute ja :other peavad ühtima.',
    'size' => [
        'numeric' => 'Välja :attribute väärtus peab olema :size.',
        'file' => 'Faili :attribute suurus peab olema :size kilobaiti.',
        'string' => 'Välja :attribute pikkus peab olema :size märki.',
        'array' => 'Väli :attribute peab sisaldama :size elementi.',
    ],
    'starts_with' => 'Väli :attribute peab algama ühega järgmistest väärtustest: :values.',
    'string' => 'Väli :attribute peab olema tekstistring.',
    'timezone' => 'Väli :attribute peab olema kehtiv ajavöönd.',
    'unique' => 'Välja :attribute väärtus on juba kasutusel.',
    'uploaded' => 'Faili :attribute üleslaadimine ebaõnnestus.',
    'url' => 'Välja :attribute vorming on vigane.',
    'uuid' => 'Väli :attribute peab olema kehtiv UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'kohandatud-teade',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
