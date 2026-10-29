<?php
/**
 * Evolution CMS validation.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Armenian
 * @package Evolution CMS
 * @subpackage core
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Վավերացման լեզվական տողեր
    |--------------------------------------------------------------------------
    |
    | Հետևյալ լեզվական տողերը պարունակում են վավերացման դասի օգտագործած
    | լռելյայն սխալի հաղորդագրությունները։ Որոշ կանոններ ունեն մի քանի տարբերակ,
    | օրինակ՝ չափի կանոնները։ Այստեղ կարող եք փոփոխել յուրաքանչյուր հաղորդագրություն։
    |
    */

    'accepted' => ':attribute դաշտը պետք է ընդունվի։',
    'active_url' => ':attribute դաշտը վավեր URL չէ։',
    'after' => ':attribute դաշտը պետք է լինի :date ամսաթվից հետո։',
    'after_or_equal' => ':attribute դաշտը պետք է լինի :date ամսաթվին հավասար կամ դրանից հետո։',
    'alpha' => ':attribute դաշտը կարող է պարունակել միայն տառեր։',
    'alpha_dash' => ':attribute դաշտը կարող է պարունակել միայն տառեր, թվեր, գծիկներ և ընդգծման նշաններ։',
    'alpha_num' => ':attribute դաշտը կարող է պարունակել միայն տառեր և թվեր։',
    'array' => ':attribute դաշտը պետք է լինի զանգված։',
    'before' => ':attribute դաշտը պետք է լինի :date ամսաթվից առաջ։',
    'before_or_equal' => ':attribute դաշտը պետք է լինի :date ամսաթվին հավասար կամ դրանից առաջ։',
    'between' => [
        'numeric' => ':attribute դաշտը պետք է լինի :min-ից :max միջակայքում։',
        'file' => ':attribute ֆայլի չափը պետք է լինի :min-ից :max կիլոբայթ։',
        'string' => ':attribute դաշտը պետք է պարունակի :min-ից :max նիշ։',
        'array' => ':attribute դաշտը պետք է պարունակի :min-ից :max տարր։',
    ],
    'boolean' => ':attribute դաշտը պետք է լինի ճշմարիտ կամ կեղծ։',
    'confirmed' => ':attribute դաշտի հաստատումը չի համընկնում։',
    'date' => ':attribute դաշտը վավեր ամսաթիվ չէ։',
    'date_equals' => ':attribute դաշտը պետք է լինի :date ամսաթվին հավասար։',
    'date_format' => ':attribute դաշտը չի համապատասխանում :format ձևաչափին։',
    'different' => ':attribute և :other դաշտերը պետք է տարբերվեն։',
    'digits' => ':attribute դաշտը պետք է պարունակի :digits թվանշան։',
    'digits_between' => ':attribute դաշտը պետք է պարունակի :min-ից :max թվանշան։',
    'dimensions' => ':attribute պատկերի չափերը անվավեր են։',
    'distinct' => ':attribute դաշտը պարունակում է կրկնվող արժեք։',
    'email' => ':attribute դաշտը պետք է լինի վավեր էլեկտրոնային փոստի հասցե։',
    'ends_with' => ':attribute դաշտը պետք է ավարտվի հետևյալ արժեքներից մեկով՝ :values։',
    'exists' => 'Ընտրված :attribute արժեքը անվավեր է։',
    'file' => ':attribute դաշտը պետք է լինի ֆայլ։',
    'filled' => ':attribute դաշտը պետք է ունենա արժեք։',
    'gt' => [
        'numeric' => ':attribute դաշտը պետք է լինի :value-ից մեծ։',
        'file' => ':attribute ֆայլի չափը պետք է լինի :value կիլոբայթից մեծ։',
        'string' => ':attribute դաշտը պետք է պարունակի :value-ից ավելի նիշ։',
        'array' => ':attribute դաշտը պետք է պարունակի :value-ից ավելի տարր։',
    ],
    'gte' => [
        'numeric' => ':attribute դաշտը պետք է լինի :value-ից մեծ կամ դրան հավասար։',
        'file' => ':attribute ֆայլի չափը պետք է լինի առնվազն :value կիլոբայթ։',
        'string' => ':attribute դաշտը պետք է պարունակի առնվազն :value նիշ։',
        'array' => ':attribute դաշտը պետք է պարունակի առնվազն :value տարր։',
    ],
    'image' => ':attribute դաշտը պետք է լինի պատկեր։',
    'in' => 'Ընտրված :attribute արժեքը անվավեր է։',
    'in_array' => ':attribute դաշտը գոյություն չունի :other-ում։',
    'integer' => ':attribute դաշտը պետք է լինի ամբողջ թիվ։',
    'ip' => ':attribute դաշտը պետք է լինի վավեր IP հասցե։',
    'ipv4' => ':attribute դաշտը պետք է լինի վավեր IPv4 հասցե։',
    'ipv6' => ':attribute դաշտը պետք է լինի վավեր IPv6 հասցե։',
    'json' => ':attribute դաշտը պետք է լինի վավեր JSON տող։',
    'lt' => [
        'numeric' => ':attribute դաշտը պետք է լինի :value-ից փոքր։',
        'file' => ':attribute ֆայլի չափը պետք է լինի :value կիլոբայթից փոքր։',
        'string' => ':attribute դաշտը պետք է պարունակի :value-ից քիչ նիշ։',
        'array' => ':attribute դաշտը պետք է պարունակի :value-ից քիչ տարր։',
    ],
    'lte' => [
        'numeric' => ':attribute դաշտը պետք է լինի :value-ից փոքր կամ դրան հավասար։',
        'file' => ':attribute ֆայլի չափը չպետք է գերազանցի :value կիլոբայթը։',
        'string' => ':attribute դաշտը պետք է պարունակի առավելագույնը :value նիշ։',
        'array' => ':attribute դաշտը պետք է պարունակի առավելագույնը :value տարր։',
    ],
    'max' => [
        'numeric' => ':attribute դաշտը չպետք է գերազանցի :max-ը։',
        'file' => ':attribute ֆայլի չափը չպետք է գերազանցի :max կիլոբայթը։',
        'string' => ':attribute դաշտը պետք է պարունակի առավելագույնը :max նիշ։',
        'array' => ':attribute դաշտը պետք է պարունակի առավելագույնը :max տարր։',
    ],
    'mimes' => ':attribute դաշտը պետք է լինի հետևյալ տեսակի ֆայլ՝ :values։',
    'mimetypes' => ':attribute դաշտը պետք է լինի հետևյալ տեսակի ֆայլ՝ :values։',
    'min' => [
        'numeric' => ':attribute դաշտը պետք է լինի առնվազն :min։',
        'file' => ':attribute ֆայլի չափը պետք է լինի առնվազն :min կիլոբայթ։',
        'string' => ':attribute դաշտը պետք է պարունակի առնվազն :min նիշ։',
        'array' => ':attribute դաշտը պետք է պարունակի առնվազն :min տարր։',
    ],
    'not_in' => 'Ընտրված :attribute արժեքը անվավեր է։',
    'not_regex' => ':attribute դաշտի ձևաչափը անվավեր է։',
    'numeric' => ':attribute դաշտը պետք է լինի թիվ։',
    'password' => 'Գաղտնաբառը սխալ է։',
    'present' => ':attribute դաշտը պետք է առկա լինի։',
    'regex' => ':attribute դաշտի ձևաչափը անվավեր է։',
    'required' => ':attribute դաշտը պարտադիր է։',
    'required_if' => ':attribute դաշտը պարտադիր է, երբ :other դաշտը :value է։',
    'required_unless' => ':attribute դաշտը պարտադիր է, եթե :other-ը :values արժեքներից չէ։',
    'required_with' => ':attribute դաշտը պարտադիր է, երբ :values-ը առկա է։',
    'required_with_all' => ':attribute դաշտը պարտադիր է, երբ :values-ը առկա են։',
    'required_without' => ':attribute դաշտը պարտադիր է, երբ :values-ը բացակայում է։',
    'required_without_all' => ':attribute դաշտը պարտադիր է, երբ :values-ից ոչ մեկը առկա չէ։',
    'same' => ':attribute և :other դաշտերը պետք է համընկնեն։',
    'size' => [
        'numeric' => ':attribute դաշտի արժեքը պետք է լինի :size։',
        'file' => ':attribute ֆայլի չափը պետք է լինի :size կիլոբայթ։',
        'string' => ':attribute դաշտը պետք է պարունակի :size նիշ։',
        'array' => ':attribute դաշտը պետք է պարունակի :size տարր։',
    ],
    'starts_with' => ':attribute դաշտը պետք է սկսվի հետևյալներից մեկով՝ :values։',
    'string' => ':attribute դաշտը պետք է լինի տող։',
    'timezone' => ':attribute դաշտը պետք է լինի վավեր ժամային գոտի։',
    'unique' => ':attribute դաշտի արժեքն արդեն օգտագործվում է։',
    'uploaded' => ':attribute ֆայլի վերբեռնումը չհաջողվեց։',
    'url' => ':attribute դաշտի ձևաչափն անվավեր է։',
    'uuid' => ':attribute դաշտը պետք է լինի վավեր UUID։',

    /*
    |--------------------------------------------------------------------------
    | Վավերացման հատուկ լեզվային տողեր
    |--------------------------------------------------------------------------
    |
    | Այստեղ կարող եք սահմանել դաշտերի վավերացման հատուկ հաղորդագրություններ՝
    | տողերն անվանելով «attribute.rule» ձևաչափով։ Սա թույլ է տալիս արագ
    | սահմանել հատուկ լեզվային տող տվյալ դաշտի կանոնի համար։
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'հատուկ հաղորդագրություն',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Վավերացման դաշտերի հատուկ անվանումներ
    |--------------------------------------------------------------------------
    |
    | Հետևյալ լեզվային տողերը փոխարինում են դաշտի տեղապահը
    | ընթերցողի համար ավելի հասկանալի անվանումով, օրինակ՝ «Էլ. փոստի հասցե»
    | «email»-ի փոխարեն։ Սա հաղորդագրություններն ավելի հստակ է դարձնում։
    |
    */

    'attributes' => [],

];
