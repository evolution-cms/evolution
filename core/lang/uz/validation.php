<?php
/**
 * Evolution CMS validation.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 23:20:04
 *
 * @language Uzbek
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

    'accepted' => ':attribute qabul qilinishi kerak.',
    'active_url' => ':attribute yaroqli URL emas.',
    'after' => ':attribute :date sanasidan keyingi sana bo‘lishi kerak.',
    'after_or_equal' => ':attribute :date sanasiga teng yoki undan keyingi sana bo‘lishi kerak.',
    'alpha' => ':attribute faqat harflardan iborat bo‘lishi mumkin.',
    'alpha_dash' => ':attribute faqat harflar, raqamlar, chiziqchalar va pastki chiziqlardan iborat bo‘lishi mumkin.',
    'alpha_num' => ':attribute faqat harflar va raqamlardan iborat bo‘lishi mumkin.',
    'array' => ':attribute massiv bo‘lishi kerak.',
    'before' => ':attribute :date sanasidan oldingi sana bo‘lishi kerak.',
    'before_or_equal' => ':attribute :date sanasiga teng yoki undan oldingi sana bo‘lishi kerak.',
    'between' => [
        'numeric' => ':attribute qiymati :min va :max oralig‘ida bo‘lishi kerak.',
        'file' => ':attribute hajmi :min va :max kilobayt oralig‘ida bo‘lishi kerak.',
        'string' => ':attribute uzunligi :min va :max belgi oralig‘ida bo‘lishi kerak.',
        'array' => ':attribute :min tadan :max tagacha elementni o‘z ichiga olishi kerak.',
    ],
    'boolean' => ':attribute maydoni rost yoki yolg‘on bo‘lishi kerak.',
    'confirmed' => ':attribute tasdig‘i mos kelmaydi.',
    'date' => ':attribute yaroqli sana emas.',
    'date_equals' => ':attribute :date sanasiga teng sana bo‘lishi kerak.',
    'date_format' => ':attribute :format formatiga mos kelmaydi.',
    'different' => ':attribute va :other bir-biridan farq qilishi kerak.',
    'digits' => ':attribute :digits ta raqamdan iborat bo‘lishi kerak.',
    'digits_between' => ':attribute :min tadan :max tagacha raqamdan iborat bo‘lishi kerak.',
    'dimensions' => ':attribute tasvirining o‘lchamlari noto‘g‘ri.',
    'distinct' => ':attribute maydonida takroriy qiymat mavjud.',
    'email' => ':attribute yaroqli elektron pochta manzili bo‘lishi kerak.',
    'ends_with' => ':attribute quyidagilardan biri bilan tugashi kerak: :values.',
    'exists' => 'Tanlangan :attribute yaroqsiz.',
    'file' => ':attribute fayl bo‘lishi kerak.',
    'filled' => ':attribute maydonida qiymat bo‘lishi kerak.',
    'gt' => [
        'numeric' => ':attribute :value dan katta bo‘lishi kerak.',
        'file' => ':attribute hajmi :value kilobaytdan katta bo‘lishi kerak.',
        'string' => ':attribute uzunligi :value belgidan ortiq bo‘lishi kerak.',
        'array' => ':attribute :value tadan ortiq elementni o‘z ichiga olishi kerak.',
    ],
    'gte' => [
        'numeric' => ':attribute :value ga teng yoki undan katta bo‘lishi kerak.',
        'file' => ':attribute hajmi kamida :value kilobayt bo‘lishi kerak.',
        'string' => ':attribute uzunligi kamida :value belgi bo‘lishi kerak.',
        'array' => ':attribute kamida :value ta elementni o‘z ichiga olishi kerak.',
    ],
    'image' => ':attribute tasvir bo‘lishi kerak.',
    'in' => 'Tanlangan :attribute yaroqsiz.',
    'in_array' => ':attribute maydoni :other ichida mavjud emas.',
    'integer' => ':attribute butun son bo‘lishi kerak.',
    'ip' => ':attribute yaroqli IP manzil bo‘lishi kerak.',
    'ipv4' => ':attribute yaroqli IPv4 manzil bo‘lishi kerak.',
    'ipv6' => ':attribute yaroqli IPv6 manzil bo‘lishi kerak.',
    'json' => ':attribute yaroqli JSON satri bo‘lishi kerak.',
    'lt' => [
        'numeric' => ':attribute :value dan kichik bo‘lishi kerak.',
        'file' => ':attribute hajmi :value kilobaytdan kichik bo‘lishi kerak.',
        'string' => ':attribute uzunligi :value belgidan kam bo‘lishi kerak.',
        'array' => ':attribute :value tadan kam elementni o‘z ichiga olishi kerak.',
    ],
    'lte' => [
        'numeric' => ':attribute :value ga teng yoki undan kichik bo‘lishi kerak.',
        'file' => ':attribute hajmi ko‘pi bilan :value kilobayt bo‘lishi kerak.',
        'string' => ':attribute uzunligi ko‘pi bilan :value belgi bo‘lishi kerak.',
        'array' => ':attribute ko‘pi bilan :value ta elementni o‘z ichiga olishi mumkin.',
    ],
    'max' => [
        'numeric' => ':attribute :max dan katta bo‘lishi mumkin emas.',
        'file' => ':attribute hajmi :max kilobaytdan katta bo‘lishi mumkin emas.',
        'string' => ':attribute uzunligi :max belgidan ortiq bo‘lishi mumkin emas.',
        'array' => ':attribute :max tadan ortiq elementni o‘z ichiga olishi mumkin emas.',
    ],
    'mimes' => ':attribute quyidagi turdagi fayl bo‘lishi kerak: :values.',
    'mimetypes' => ':attribute quyidagi turdagi fayl bo‘lishi kerak: :values.',
    'min' => [
        'numeric' => ':attribute kamida :min bo‘lishi kerak.',
        'file' => ':attribute hajmi kamida :min kilobayt bo‘lishi kerak.',
        'string' => ':attribute uzunligi kamida :min belgi bo‘lishi kerak.',
        'array' => ':attribute kamida :min ta elementni o‘z ichiga olishi kerak.',
    ],
    'not_in' => 'Tanlangan :attribute yaroqsiz.',
    'not_regex' => ':attribute formati noto‘g‘ri.',
    'numeric' => ':attribute son bo‘lishi kerak.',
    'password' => 'Parol noto‘g‘ri.',
    'present' => ':attribute maydoni mavjud bo‘lishi kerak.',
    'regex' => ':attribute formati noto‘g‘ri.',
    'required' => ':attribute maydoni to‘ldirilishi shart.',
    'required_if' => ':other qiymati :value bo‘lganda :attribute maydoni to‘ldirilishi shart.',
    'required_unless' => ':other :values orasida bo‘lmasa, :attribute maydoni majburiy.',
    'required_with' => ':values mavjud bo‘lsa, :attribute maydoni majburiy.',
    'required_with_all' => ':values qiymatlarining barchasi mavjud bo‘lsa, :attribute maydoni majburiy.',
    'required_without' => ':values mavjud bo‘lmasa, :attribute maydoni majburiy.',
    'required_without_all' => ':values qiymatlarining hech biri mavjud bo‘lmasa, :attribute maydoni majburiy.',
    'same' => ':attribute va :other mos kelishi kerak.',
    'size' => [
        'numeric' => ':attribute :size bo‘lishi kerak.',
        'file' => ':attribute hajmi :size kilobayt bo‘lishi kerak.',
        'string' => ':attribute :size ta belgidan iborat bo‘lishi kerak.',
        'array' => ':attribute :size ta elementni o‘z ichiga olishi kerak.',
    ],
    'starts_with' => ':attribute quyidagilardan biri bilan boshlanishi kerak: :values.',
    'string' => ':attribute matnli satr bo‘lishi kerak.',
    'timezone' => ':attribute haqiqiy vaqt mintaqasi bo‘lishi kerak.',
    'unique' => ':attribute allaqachon band.',
    'uploaded' => ':attribute yuklanmadi.',
    'url' => ':attribute formati noto‘g‘ri.',
    'uuid' => ':attribute haqiqiy UUID bo‘lishi kerak.',

    /*
    |--------------------------------------------------------------------------
    | Maxsus tekshirish xabarlari
    |--------------------------------------------------------------------------
    |
    | Bu yerda atributlar uchun maxsus tekshirish xabarlarini belgilashingiz mumkin.
    | Xabarlarni nomlashda "attribute.rule" shaklidan foydalaning. Bu berilgan
    | atribut qoidasi uchun maxsus xabarni tezda belgilash imkonini beradi.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'maxsus-xabar',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maxsus tekshirish atributlari
    |--------------------------------------------------------------------------
    |
    | Quyidagi til satrlari atribut o‘rinbosarini o‘quvchi uchun tushunarliroq
    | nomga almashtirish uchun ishlatiladi, masalan, "email" o‘rniga
    | "Elektron pochta manzili". Bu xabarlarimizni yanada tushunarli qiladi.
    |
    */

    'attributes' => [],

];
