<?php
if(!function_exists('createList')) {
    /**
     * @param string $sectionHeader
     * @param array $linkArr
     * @return string
     */
    function createList($sectionHeader, $linkArr)
    {
        $output = '<div class="sectionHeader">' . $sectionHeader . '</div><div class="sectionBody">' . "\n";
        $output .= '<table width="500"  border="0" cellspacing="0" cellpadding="0">' . "\n";
        $links = '';
        foreach ($linkArr as $row) {
            if (!is_array($row['link'])) {
                $row['link'] = array($row['link']);
            }
            foreach ($row['link'] as $link) {
                $links .= $links != '' ? '<br/>' : '';
                $links .= '<a href="' . $link . '" target="_blank">' . $link . '</a>';
            }
            $output .= '
		<tr>
			<td align="left"><strong>' . $row["title"] . '</strong></td>
			<td align="left">' . $links . '</td>
		</tr>';
            $links = '';
        }
        $output .= '</table></div>' . "\n";

        return $output;
    }
}
