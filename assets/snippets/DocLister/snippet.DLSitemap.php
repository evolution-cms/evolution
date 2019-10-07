<?php
include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

if (!isset($params['config'])) {
    $params['config'] = 'sitemap:core';
}
if (!isset($schema)) {
    $schema = 'https://www.sitemaps.org/schemas/sitemap/0.9';
}

if (!class_exists("DLSitemap")) {
    class DLSitemap
    {
        public static function prepare(array $data, DocumentParser $modx, $_DocLister)
        {
            $data['date'] = !empty($data['editedon']) ? $data['editedon'] : $data['createdon'];
            $datediff = floor((time() - $data['date']) / 86400);
            if ($datediff <= 1) {
                $data['priority'] = '1.0';
                $data['update'] = 'daily';
            } elseif (($datediff > 1) && ($datediff <= 7)) {
                $data['priority'] = '0.75';
                $data['update'] = 'weekly';
            } elseif (($datediff > 7) && ($datediff <= 30)) {
                $data['priority'] = '0.50';
                $data['update'] = 'weekly';
            } else {
                $data['priority'] = '0.25';
                $data['update'] = 'monthly';
            }
            $dateFormat = $_DocLister->getCFGDef('dateFormat');
            if ($dateFormat) {
                $data['date'] = strftime($dateFormat, $data['date']);
            } else {
                $data['date'] = date('c', $data['date']);
            }
            $priorityField = $_DocLister->getCFGDef('priority', 'tv.sitemap_priority');
            $changefreqField = $_DocLister->getCFGDef('changefreq', 'tv.sitemap_changefreq');
            if (!empty($data[$priorityField])) {
                $data['priority'] = $data[$priorityField];
            }
            if (!empty($data[$changefreqField])) {
                $data['update'] = $data[$changefreqField];
            }

            return $data;
        }
    }
}

$params['prepare'] = function ($data, $modx, $_DocLister) {
    if ($BeforePrepare = $_DocLister->getCFGDef('BeforePrepare')) {
        $data = call_user_func($BeforePrepare, $data, $modx, $_DocLister);
    }
    $data = DLSitemap::prepare($data, $modx, $_DocLister);
    if ($AfterPrepare = $_DocLister->getCFGDef('AfterPrepare')) {
        $data = call_user_func($AfterPrepare, $data, $modx, $_DocLister);
    }
    return $data;
};

$out = $modx->runSnippet('DocLister', $params);
if (!empty($out)) {
    $out = "<?xml version=\"1.0\" encoding=\"{$modx->config['modx_charset']}\"?>\n<urlset xmlns=\"{$schema}\">{$out}\n</urlset>";
}

return $out;
