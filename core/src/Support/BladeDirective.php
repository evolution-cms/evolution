<?php namespace EvolutionCMS\Support;

class BladeDirective
{

    public static function evoParser($content): string
    {
        return '<?php echo evo_parser(' . $content . ');?>';
    }

    public static function evoLang($key): string
    {
        return '<?php echo ManagerTheme::getLexicon(' . $key . ');?>';
    }

    public static function evoStyle($key): string
    {
        return '<?php echo ManagerTheme::getStyle(' . $key . ');?>';
    }

    public static function evoAdminLang(): string
    {
        return '<?php echo ManagerTheme::getLangName();?>';
    }

    public static function evoCharset(): string
    {
        return '<?php echo ManagerTheme::getCharset();?>';
    }

    public static function evoAdminThemeUrl(): string
    {
        return '<?php echo ManagerTheme::getThemeUrl();?>';
    }

    public static function evoAdminThemeName(): string
    {
        return '<?php echo ManagerTheme::getTheme();?>';
    }

    public static function makeUrl($id): string
    {
        return '<?php echo app("UrlProcessor")->makeUrlWithString(' . $id . ');?>';
    }
    public static function csrf(): string
    {
        return '<?php echo csrf_field();?>';
    }

    public static function evoRole($role = ''): string
    {
        return "<?php if(evo_role($role)): ?>";
    }

    public static function evoElseRole($role = ''): string
    {
        return "<?php elseif(evo_role($role)): ?>";
    }

    public static function evoEndRole(): string
    {
        return '<?php endif; ?>';
    }

    public static function evoConfig(string $name = '', string $default = ''): string
    {
        return '<?php echo evo()->getConfig(' . $name . ', ' . $default . ');?>';
    }
}
