<?php

/**
*
*/
class ShortcodeAdmin
{
    protected $finder;

    public function __construct(GwaShortcode $finder)
    {
        $this->finder = $finder;
    }

    public function render()
    {
        // Retrieve shortcodes
        $allShortcodes = $this->finder->getShortcodes();

        // Arrange shortcodes by function
        $shortcodeArray = $this->finder->sortShortcodesByFunction($allShortcodes);

        return $this->template($shortcodeArray);
    }

    protected function template(array $shortcodeArray)
    {
        $codes = json_encode($this->finder->getShortcodes());

        echo <<<EOF

EOF;

        $output = '<div class="wrap">';
        $output .= '<h2>'.__('Available Shortcodes:', 'gwa').'</h2>';
        $output .= '<table class="gwa-table">';
        $output .= '<tr>';
        $output .= '<td>';

        $output .= $this->loopShortcodes($shortcodeArray);

        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';
        $output .= '</div>';

        return $output;
    }

    protected function loopShortcodes(array $shortcodeArray)
    {
        // Display all server shortcodes
        foreach ($shortcodeArray as $name => $group) {
            if (!empty($group)) {
                $output  = '<table class="widefat importers">';
                $output .= '<tbody>';
                $output .= '<tr>';
                $output .= '<th><strong>'.__('Shortcode', 'gwa').'</strong></th>';
                $output .= '<th><strong>'.__('Arguments', 'gwa').'</strong></th>';
                $output .= '</tr>';

                foreach ($group as $shortcode) {
                    $output .= '<tr><th>['.$shortcode['name'].']</th>';
                    $output .= '<td>';

                    if (!empty($shortcode['params'])) {
                        // Retrieve final element.
                        $keys = array_keys($shortcode['params']);
                        $lastKey = end($keys);

                        foreach ($shortcode['params'] as $key => $param) {
                            if ($key === $lastKey) {
                                $output .= $param;
                            } else {
                                $output .= $param.', ';
                            }
                        }

                    } else {
                        $output .= __('No Shortcodes', 'gwa');
                    }

                    $output .= '</td></tr>';
                }

                $output .= '</tbody></table>';

                return $output;
            }
        }
    }
}
