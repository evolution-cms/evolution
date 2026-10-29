<?php
/**
 * Evolution CMS validation.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Kyrgyz
 * @package Evolution CMS
 * @subpackage core
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

return [
    'accepted' => ':attribute кабыл алынышы керек.',
    'active_url' => ':attribute жарактуу URL эмес.',
    'after' => ':attribute :date күнүнөн кийинки күн болушу керек.',
    'after_or_equal' => ':attribute :date күнүнө барабар же андан кийинки күн болушу керек.',
    'alpha' => ':attribute тамгалардан гана турушу мүмкүн.',
    'alpha_dash' => ':attribute тамгалардан, сандардан, дефистерден жана астынкы сызыктардан гана турушу мүмкүн.',
    'alpha_num' => ':attribute тамгалардан жана сандардан гана турушу мүмкүн.',
    'array' => ':attribute массив болушу керек.',
    'before' => ':attribute :date күнүнөн мурунку күн болушу керек.',
    'before_or_equal' => ':attribute :date күнүнө барабар же андан мурунку күн болушу керек.',
    'between' => [
        'numeric' => ':attribute :min менен :max ортосунда болушу керек.',
        'file' => ':attribute :min менен :max килобайт ортосунда болушу керек.',
        'string' => ':attribute :min менен :max белгинин ортосунда болушу керек.',
        'array' => ':attribute :min менен :max элементтин ортосунда камтышы керек.',
    ],
    'boolean' => ':attribute талаасы чын же жалган болушу керек.',
    'confirmed' => ':attribute ырастоосу дал келбейт.',
    'date' => ':attribute жарактуу күн эмес.',
    'date_equals' => ':attribute :date күнүнө барабар күн болушу керек.',
    'date_format' => ':attribute :format форматына туура келбейт.',
    'different' => ':attribute менен :other ар башка болушу керек.',
    'digits' => ':attribute :digits сандан турушу керек.',
    'digits_between' => ':attribute :min менен :max сандын ортосунда турушу керек.',
    'dimensions' => ':attribute сүрөтүнүн өлчөмдөрү туура эмес.',
    'distinct' => ':attribute талаасында кайталанган маани бар.',
    'email' => ':attribute жарактуу электрондук почта дареги болушу керек.',
    'ends_with' => ':attribute төмөнкүлөрдүн бири менен аякташы керек: :values.',
    'exists' => 'Тандалган :attribute жараксыз.',
    'file' => ':attribute файл болушу керек.',
    'filled' => ':attribute талаасында маани болушу керек.',
    'gt' => [
        'numeric' => ':attribute :value маанисинен чоң болушу керек.',
        'file' => ':attribute :value килобайттан чоң болушу керек.',
        'string' => ':attribute :value белгиден узун болушу керек.',
        'array' => ':attribute :value элементтен көп камтышы керек.',
    ],
    'gte' => [
        'numeric' => ':attribute :value маанисинен чоң же ага барабар болушу керек.',
        'file' => ':attribute :value килобайттан чоң же ага барабар болушу керек.',
        'string' => ':attribute кеминде :value белгиден турушу керек.',
        'array' => ':attribute кеминде :value элементти камтышы керек.',
    ],
    'image' => ':attribute сүрөт болушу керек.',
    'in' => 'Тандалган :attribute жараксыз.',
    'in_array' => ':attribute талаасы :other ичинде жок.',
    'integer' => ':attribute бүтүн сан болушу керек.',
    'ip' => ':attribute жарактуу IP дареги болушу керек.',
    'ipv4' => ':attribute жарактуу IPv4 дареги болушу керек.',
    'ipv6' => ':attribute жарактуу IPv6 дареги болушу керек.',
    'json' => ':attribute жарактуу JSON сабы болушу керек.',
    'lt' => [
        'numeric' => ':attribute :value маанисинен кичине болушу керек.',
        'file' => ':attribute :value килобайттан кичине болушу керек.',
        'string' => ':attribute :value белгиден кыска болушу керек.',
        'array' => ':attribute :value элементтен аз камтышы керек.',
    ],
    'lte' => [
        'numeric' => ':attribute :value маанисинен кичине же ага барабар болушу керек.',
        'file' => ':attribute :value килобайттан кичине же ага барабар болушу керек.',
        'string' => ':attribute :value белгиден ашпашы керек.',
        'array' => ':attribute :value элементтен ашык камтыбашы керек.',
    ],
    'max' => [
        'numeric' => ':attribute :max маанисинен чоң болбошу керек.',
        'file' => ':attribute :max килобайттан ашпашы керек.',
        'string' => ':attribute :max белгиден ашпашы керек.',
        'array' => ':attribute :max элементтен ашык камтыбашы керек.',
    ],
    'mimes' => ':attribute төмөнкү түрдөгү файл болушу керек: :values.',
    'mimetypes' => ':attribute төмөнкү түрдөгү файл болушу керек: :values.',
    'min' => [
        'numeric' => ':attribute кеминде :min болушу керек.',
        'file' => ':attribute кеминде :min килобайт болушу керек.',
        'string' => ':attribute кеминде :min белгиден турушу керек.',
        'array' => ':attribute кеминде :min элементти камтышы керек.',
    ],
    'not_in' => 'Тандалган :attribute жараксыз.',
    'not_regex' => ':attribute форматы жараксыз.',
    'numeric' => ':attribute сан болушу керек.',
    'password' => 'Сырсөз туура эмес.',
    'present' => ':attribute талаасы болушу керек.',
    'regex' => ':attribute форматы жараксыз.',
    'required' => ':attribute талаасы милдеттүү.',
    'required_if' => ':other :value болгондо :attribute талаасы милдеттүү.',
    'required_unless' => ':other :values ичинде болбосо, :attribute талаасы милдеттүү.',
    'required_with' => ':values бар болсо, :attribute талаасы милдеттүү.',
    'required_with_all' => ':values баары бар болсо, :attribute талаасы милдеттүү.',
    'required_without' => ':values жок болсо, :attribute талаасы милдеттүү.',
    'required_without_all' => ':values эч бири жок болсо, :attribute талаасы милдеттүү.',
    'same' => ':attribute менен :other дал келиши керек.',
    'size' => [
        'numeric' => ':attribute :size болушу керек.',
        'file' => ':attribute :size килобайт болушу керек.',
        'string' => ':attribute :size белгиден турушу керек.',
        'array' => ':attribute :size элементти камтышы керек.',
    ],
    'starts_with' => ':attribute төмөнкүлөрдүн бири менен башталышы керек: :values.',
    'string' => ':attribute сап болушу керек.',
    'timezone' => ':attribute жарактуу убакыт алкагы болушу керек.',
    'unique' => ':attribute мурунтан колдонулат.',
    'uploaded' => ':attribute жүктөлбөй калды.',
    'url' => ':attribute форматы жараксыз.',
    'uuid' => ':attribute жарактуу UUID болушу керек.',
    'custom' => ['attribute-name' => ['rule-name' => 'өзгөчө-билдирүү']],
    'attributes' => [],
];
