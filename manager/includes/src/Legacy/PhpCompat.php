<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Interfaces\PhpCompatInterface;

/**
 * @TODO file_put_contents(), strftime(), mb_*()
 */
class PhpCompat implements PhpCompatInterface
{
    /**
     * @param string|array $str
     * @param int $flags
     * @param string $encode
     * @param int $safecount
     * @return string|array
     */
    public function htmlspecialchars($str = '', $flags = ENT_COMPAT, $encode = '', $safecount = 0)
    {
        $safecount++;
        $modx = evolutionCMS();

        if (1000 < $safecount) {
            exit("error too many loops '{$safecount}'");
        }

        if (is_array($str)) {
            foreach ($str as $i => $v) {
                $str[$i] = $this->htmlspecialchars($v, $flags, $encode, $safecount);
            }
        } elseif ($str !== '') {
            if ($encode !== '') {
                $encode = $modx->config['modx_charset'];
            }
            $ent_str = htmlspecialchars($str, $flags, $encode);

            if (!empty($str) && empty($ent_str)) {
                $detect_order = implode(',', mb_detect_order());
                $ent_str = mb_convert_encoding($str, $encode, $detect_order);
            }
        } else {
            $ent_str = '';
        }

        return $ent_str;
    }

    public function entities($data)
    {
        return entities($data, evolutionCMS()->getConfig('modx_charset', 'UTF-8'));
    }
}
