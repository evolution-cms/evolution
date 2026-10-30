<?php
/**
 * Evolution CMS validation.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 23:20:04
 *
 * @language Romanian
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

    'accepted' => 'Câmpul :attribute trebuie acceptat.',
    'active_url' => 'Câmpul :attribute nu este un URL valid.',
    'after' => 'Câmpul :attribute trebuie să fie o dată ulterioară datei :date.',
    'after_or_equal' => 'Câmpul :attribute trebuie să fie o dată ulterioară sau egală cu :date.',
    'alpha' => 'Câmpul :attribute poate conține doar litere.',
    'alpha_dash' => 'Câmpul :attribute poate conține doar litere, cifre, cratime și caractere de subliniere.',
    'alpha_num' => 'Câmpul :attribute poate conține doar litere și cifre.',
    'array' => 'Câmpul :attribute trebuie să fie un tablou.',
    'before' => 'Câmpul :attribute trebuie să fie o dată anterioară datei :date.',
    'before_or_equal' => 'Câmpul :attribute trebuie să fie o dată anterioară sau egală cu :date.',
    'between' => [
        'numeric' => 'Câmpul :attribute trebuie să aibă o valoare între :min și :max.',
        'file' => 'Fișierul :attribute trebuie să aibă între :min și :max kiloocteți.',
        'string' => 'Câmpul :attribute trebuie să conțină între :min și :max caractere.',
        'array' => 'Câmpul :attribute trebuie să conțină între :min și :max elemente.',
    ],
    'boolean' => 'Câmpul :attribute trebuie să fie adevărat sau fals.',
    'confirmed' => 'Confirmarea câmpului :attribute nu corespunde.',
    'date' => 'Câmpul :attribute nu este o dată validă.',
    'date_equals' => 'Câmpul :attribute trebuie să fie o dată egală cu :date.',
    'date_format' => 'Câmpul :attribute nu corespunde formatului :format.',
    'different' => 'Câmpurile :attribute și :other trebuie să fie diferite.',
    'digits' => 'Câmpul :attribute trebuie să conțină :digits cifre.',
    'digits_between' => 'Câmpul :attribute trebuie să conțină între :min și :max cifre.',
    'dimensions' => 'Imaginea :attribute are dimensiuni nevalide.',
    'distinct' => 'Câmpul :attribute conține o valoare duplicată.',
    'email' => 'Câmpul :attribute trebuie să fie o adresă de e-mail validă.',
    'ends_with' => 'Câmpul :attribute trebuie să se termine cu una dintre următoarele valori: :values.',
    'exists' => 'Valoarea selectată pentru :attribute nu este validă.',
    'file' => 'Câmpul :attribute trebuie să fie un fișier.',
    'filled' => 'Câmpul :attribute trebuie să aibă o valoare.',
    'gt' => [
        'numeric' => 'Câmpul :attribute trebuie să fie mai mare decât :value.',
        'file' => 'Fișierul :attribute trebuie să aibă mai mult de :value kiloocteți.',
        'string' => 'Câmpul :attribute trebuie să conțină mai mult de :value caractere.',
        'array' => 'Câmpul :attribute trebuie să conțină mai mult de :value elemente.',
    ],
    'gte' => [
        'numeric' => 'Câmpul :attribute trebuie să fie mai mare sau egal cu :value.',
        'file' => 'Fișierul :attribute trebuie să aibă cel puțin :value kiloocteți.',
        'string' => 'Câmpul :attribute trebuie să conțină cel puțin :value caractere.',
        'array' => 'Câmpul :attribute trebuie să conțină cel puțin :value elemente.',
    ],
    'image' => 'Câmpul :attribute trebuie să fie o imagine.',
    'in' => 'Valoarea selectată pentru :attribute nu este validă.',
    'in_array' => 'Câmpul :attribute nu există în :other.',
    'integer' => 'Câmpul :attribute trebuie să fie un număr întreg.',
    'ip' => 'Câmpul :attribute trebuie să fie o adresă IP validă.',
    'ipv4' => 'Câmpul :attribute trebuie să fie o adresă IPv4 validă.',
    'ipv6' => 'Câmpul :attribute trebuie să fie o adresă IPv6 validă.',
    'json' => 'Câmpul :attribute trebuie să fie un șir JSON valid.',
    'lt' => [
        'numeric' => 'Câmpul :attribute trebuie să fie mai mic decât :value.',
        'file' => 'Fișierul :attribute trebuie să aibă mai puțin de :value kiloocteți.',
        'string' => 'Câmpul :attribute trebuie să conțină mai puțin de :value caractere.',
        'array' => 'Câmpul :attribute trebuie să conțină mai puțin de :value elemente.',
    ],
    'lte' => [
        'numeric' => 'Câmpul :attribute trebuie să fie mai mic sau egal cu :value.',
        'file' => 'Fișierul :attribute trebuie să aibă cel mult :value kiloocteți.',
        'string' => 'Câmpul :attribute trebuie să conțină cel mult :value caractere.',
        'array' => 'Câmpul :attribute nu poate conține mai mult de :value elemente.',
    ],
    'max' => [
        'numeric' => 'Câmpul :attribute nu poate fi mai mare decât :max.',
        'file' => 'Fișierul :attribute nu poate avea mai mult de :max kiloocteți.',
        'string' => 'Câmpul :attribute nu poate conține mai mult de :max caractere.',
        'array' => 'Câmpul :attribute nu poate conține mai mult de :max elemente.',
    ],
    'mimes' => 'Câmpul :attribute trebuie să fie un fișier de tipul: :values.',
    'mimetypes' => 'Câmpul :attribute trebuie să fie un fișier de tipul: :values.',
    'min' => [
        'numeric' => 'Câmpul :attribute trebuie să fie cel puțin :min.',
        'file' => 'Fișierul :attribute trebuie să aibă cel puțin :min kiloocteți.',
        'string' => 'Câmpul :attribute trebuie să conțină cel puțin :min caractere.',
        'array' => 'Câmpul :attribute trebuie să conțină cel puțin :min elemente.',
    ],
    'not_in' => 'Valoarea selectată pentru :attribute nu este validă.',
    'not_regex' => 'Formatul câmpului :attribute nu este valid.',
    'numeric' => 'Câmpul :attribute trebuie să fie un număr.',
    'password' => 'Parola este incorectă.',
    'present' => 'Câmpul :attribute trebuie să fie prezent.',
    'regex' => 'Formatul câmpului :attribute nu este valid.',
    'required' => 'Câmpul :attribute este obligatoriu.',
    'required_if' => 'Câmpul :attribute este obligatoriu când :other are valoarea :value.',
    'required_unless' => 'Câmpul :attribute este obligatoriu, cu excepția cazului în care :other se află în :values.',
    'required_with' => 'Câmpul :attribute este obligatoriu când :values este prezent.',
    'required_with_all' => 'Câmpul :attribute este obligatoriu când toate valorile :values sunt prezente.',
    'required_without' => 'Câmpul :attribute este obligatoriu când :values nu este prezent.',
    'required_without_all' => 'Câmpul :attribute este obligatoriu când niciuna dintre valorile :values nu este prezentă.',
    'same' => ':attribute și :other trebuie să coincidă.',
    'size' => [
        'numeric' => ':attribute trebuie să fie :size.',
        'file' => ':attribute trebuie să aibă :size kiloocteți.',
        'string' => ':attribute trebuie să aibă :size caractere.',
        'array' => ':attribute trebuie să conțină :size elemente.',
    ],
    'starts_with' => ':attribute trebuie să înceapă cu una dintre următoarele valori: :values.',
    'string' => ':attribute trebuie să fie un șir de caractere.',
    'timezone' => ':attribute trebuie să fie un fus orar valid.',
    'unique' => ':attribute este deja utilizat.',
    'uploaded' => 'Încărcarea pentru :attribute a eșuat.',
    'url' => 'Formatul pentru :attribute nu este valid.',
    'uuid' => ':attribute trebuie să fie un UUID valid.',

    /*
    |--------------------------------------------------------------------------
    | Mesaje de validare personalizate
    |--------------------------------------------------------------------------
    |
    | Aici puteți specifica mesaje de validare personalizate pentru atribute folosind
    | convenția "attribute.rule" pentru denumirea mesajelor. Astfel puteți
    | specifica rapid un mesaj personalizat pentru regula unui atribut dat.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'mesaj-personalizat',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Atribute de validare personalizate
    |--------------------------------------------------------------------------
    |
    | Următoarele mesaje sunt folosite pentru a înlocui substituentul atributului
    | cu ceva mai ușor de citit, cum ar fi "Adresă de e-mail" în loc
    | de "email". Acest lucru ne ajută să facem mesajele mai expresive.
    |
    */

    'attributes' => [],

];
