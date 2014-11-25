<?php
/**
 * Plugin Name: Gwa Shortcode Finder
 * Plugin URI:
 * Description: Displays a list of shortcodes available for use.
 * Version: 1.0.0
 * Author: Great White Ark
 * Author URI: http://www.greatwhiteark.com
 * License: MIT
*/

class GwaShortcode
{
    /**
     * @name PLUGIN_SLUG
     *
     * @var const
     */
    public $pluginSlug = 'gwShortcode-finder';

    /**
     * @access public
     */
    public $popupID = 'gwa-popup';

    public $shortcodes = array();

    /**
     * Create a new gwa shortcode instance.
     *
     * @return add_action init all functions to worpdress init
     */
    public function __construct()
    {
        add_action('init', function () {
            add_action('media_buttons_context', array($this, 'postAddShortcodeButton'));
            add_action('admin_menu', array($this, 'menuAdd'));
            add_action('admin_enqueue_scripts', array($this, 'loadAdminAssets'));
            add_action('admin_footer-post.php', array($this, 'renderShortcodePopup'));
            add_action('admin_footer-post-new.php', array($this, 'renderShortcodePopup'));
            add_action('sidebar_admin_setup', array($this, 'widgetAddShortcodeButton'));
            add_action('wp_ajax_getShortcodes', array($this, 'getShortcodesAjax'));
        });
    }

    /**
     * Enqueues the admin CSS file.
     * @param string $hook
     *   Passes the name of the page that the user is on.
     */
    public function loadAdminAssets($hook)
    {
        wp_enqueue_style('gwa-tools-css', plugins_url().'/'.$this->pluginSlug.'/css/tools.css');

        if ($hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_style('gwa-post-css', plugins_url().'/'.$this->pluginSlug.'/css/post.css');
            wp_enqueue_script(
                'gwa-post-js',
                plugins_url().'/'.$this->pluginSlug.'/js/shortcode-popup.js',
                array('jquery')
            );
        }
    }

    /**
     * Adds the 'Shortcode' link to the Tools menu.
     */
    public function menuAdd()
    {
        add_submenu_page(
            'tools.php',
            'Shortcodes',
            'Shortcodes',
            'manage_options',
            'gwa-shortcode-finder',
            array($this, 'renderMenu')
        );
    }

    /**
     * Retrieves all shortcodes and information about them.
     */
    public function getShortcodes()
    {
        //Add any other shortcodes
        global $shortcode_tags;
        $codes = array();

        foreach ($shortcode_tags as $codename => $func) {
            $code = array();

            if (is_string($func)) {
                $reflection = new ReflectionFunction($func);
            } elseif (is_array($func)) {
                $reflection = new ReflectionMethod($func[0], $func[1]);
            }

            $code['name'] = $codename;
            $funcName = $reflection->getName();
            $code['function_name'] = $funcName;
            $funcFileName = $reflection->getFileName();
            $code['filename'] = $funcFileName;

            $code = array_merge(
                $code,
                $this->checkIfPluginHasShortCodes($funcFileName),
                $this->checkIfThemesHasShortCodes($funcFileName)
            );

            $funcDefinition = $this->functionToString($func);
            $funcParams = $reflection->getParameters();

            $code['params'] = array_unique(
                array_merge(
                    $this->literalRegexCheck(
                        $funcDefinition,
                        array_key_exists(0, $funcParams) ? $funcParams[0]->name : ''
                    ),
                    $this->arrayFuncRegexCheck($funcDefinition),
                    $this->arrayReferenceRegexCheck($funcDefinition)
                )
            );

            $codes[] = $code;
        }

        return $codes;
    }

    /**
     * Check if plugin dir has shortcodes
     *
     * @param  string $funcFileName dir path
     * @return array
     */
    protected function checkIfPluginHasShortCodes($funcFileName)
    {
        $code = array();

        if (stripos($funcFileName, 'plugins') !== false) {
            $code['type'] = 'plugin';
            $code['details'] = $this->getPluginData($funcFileName);
        }

        return $code;
    }

    /**
     * Check if themes dir has shortcodes
     *
     * @param  string $funcFileName dir path
     * @return array
     */
    protected function checkIfThemesHasShortCodes($funcFileName)
    {
        $code = array();

        if (stripos($funcFileName, 'themes') !== false) {
            $code['type'] = 'theme';
            $code['details'] = array(
                'Name'        => wp_get_theme()->get('Name'),
                'ThemeURI'    => wp_get_theme()->get('ThemeURI'),
                'Description' => wp_get_theme()->get('Description'),
                'Author'      => wp_get_theme()->get('Author'),
                'AuthorURI'   => wp_get_theme()->get('AuthorURI'),
                'Version'     => wp_get_theme()->get('Version'),
                'TextDomain'  => wp_get_theme()->get('TextDomain'),
                'DomainPath'  => wp_get_theme()->get('DomainPath')
            );
        } else {
            $code['type'] = 'native';
            $code['details'] = array();
        }

        return $code;
    }

    /**
     * Literal match based on array name
     *
     * @param  string $funcDefinition
     * @param  $funcAttrParam
     * @return array matches
     */
    protected function literalRegexCheck($funcDefinition, $funcAttrParam)
    {
        $regex = '|'.$funcAttrParam.'\[[\'\"](.+?)[\'\"]\]|';
        preg_match_all($regex, $funcDefinition, $matches, PREG_PATTERN_ORDER);

        return $matches[1];
    }

    /**
     * Array based match based on shortcode_atts func
     *
     * @param  string $funcDefinition
     * @return array matches
     */
    protected function arrayFuncRegexCheck($funcDefinition)
    {
        $regex = '|shortcode_atts\s*\(\s*array\s*\(([\s\S]+?);|';
        preg_match_all($regex, $funcDefinition, $matches, PREG_PATTERN_ORDER);

        foreach ($matches[1] as $sm) {
            $regex = '|[\'\"](.+?)[\'\"]\s*=>|';
            preg_match_all($regex, $sm, $matches, PREG_PATTERN_ORDER);
        }

        return $matches[1];
    }

    /**
     * Array based match based on shortcode_atts reference array
     *
     * @param  string $funcDefinition
     * @return array matches
     */
    protected function arrayReferenceRegexCheck($funcDefinition)
    {
        $regex = '|shortcode_atts\s*\(\s*\$(.+?),|';
        preg_match_all($regex, $funcDefinition, $matches, PREG_PATTERN_ORDER);

        foreach ($matches[1] as $rm) {
            $regex = '|\$'.$rm.'\s*=\s*array\(([\s\S]+?);|';
            preg_match_all($regex, $funcDefinition, $matches, PREG_PATTERN_ORDER);
        }

        foreach ($matches[1] as $rm) {
            $regex = '|[\'\"](.+?)[\'\"]\s*=>|';
            preg_match_all($regex, $rm, $matches, PREG_PATTERN_ORDER);
        }

        return $matches[1];
    }

    /**
     * Get server side shortcodes via AJAX
     */
    public function getShortcodesAjax()
    {
        echo json_encode($this->getShortcodes());
        die();
    }

    /**
     * Arrange all shortcodes by function.
     *
     * @param array  $shortcodes This contains all shortcodes to sort.
     *                           These are typically retrieved with getShortcodes.
     *
     * @return array $shortcodeArray This contains all shortcodes, but now arranged according to function.
     */
    public function sortShortcodesByFunction($shortcodes)
    {
        foreach ($shortcodes as $shortcode) {
            switch ($shortcode['type']) {
                case 'native':
                    $shortcodeArray['Native WordPress'][] = $shortcode;
                    break;
                case 'plugin':
                case 'theme':
                    $name = $shortcode['details']['Name'];

                    //Sometimes details aren't provided
                    if (!$name) {
                        $name = $shortcode['name'];
                    }

                    $shortcodeArray[$shortcode['details']['Name']][] = $shortcode;
                    break;
                default:
                    $shortcodeArray['Misc'][] = $shortcode;
                    break;
            }
        }

        return $shortcodeArray;
    }

    /**
     * Converts plugin functions into strings (actual shortcode value).
     *
     * @param mixed   $func This expects a shortcode from the global array of shortcodes from Wordpress.
     *
     * @return string $def  This returns the name of the function, or in our case, the name of the shortcode.
     */
    protected function functionToString($func)
    {
        if (is_array($func)) {
            $rf = is_object($func[0]) ? new ReflectionObject($func[0]) : new ReflectionClass($func[0]);
            $rf = $rf->getMethod($func[1]);
        } else {
            $rf = new ReflectionFunction($func);
        }

        $c = file($rf->getFileName());
        $def = '';

        for ($i = $rf->getStartLine(); $i <= $rf->getEndLine(); $i++) {
            $def .= sprintf('%s', $c[$i-1]);
        }

        return $def;
    }

    /**
     * Adds the 'Shortcode' link to the Tools menu.
     */
    public function renderMenu()
    {
        require 'ShortcodeAdmin.php';

        echo (new ShortcodeAdmin($this))->render();
    }

    /**
     * Adds the 'Shortcode' button above the TinyMCE Editor.
     */
    public function postAddShortcodeButton()
    {
        // Popup Variablese
        $pTitle = __('Add Shortcode', 'gwa');

        $buttonMarkup = '
            <a href="#TB_inline?width=400&inlineId='.$this->popupID.'&class=testest" id="gwa-button" class="button gwa-button thickbox" title="'.$pTitle.'">
                <span class="gwa-button-icon"></span>
                '.$pTitle.'
            </a>
        ';

        return $buttonMarkup;
    }

    /**
     * Adds the 'Shortcode' button into each widget.
     */
    public function widgetAddShortcodeButton()
    {
        global
            $wp_registered_widgets,
            $wp_registered_widget_controls;

        foreach ($wp_registered_widgets as $key => $w) {
            if ($wp_registered_widget_controls[$key]['name'] == 'Text') {
                $wp_registered_widget_controls[$key]['callback'] = 'widgetRenderShortcode';
            }
        }
    }

    /**
     * Adds the 'Shortcode' button into each widget.
     */
    public function widgetRenderShortcode()
    {
        echo $this->postAddShortcodeButton();
    }

    /**
     * Renders the popup for the 'Shortcodes' button.
     */
    public function renderShortcodePopup()
    {
        require 'GwaShortcodePopup.php';

        echo (new GwaShortcodePopup())->makeIframe();
    }

    //Copied from WP. This method is not allowed on the front end.
    protected function getPluginData($pluginFile)
    {
        $defaultHeaders = array(
            'Name'        => 'Plugin Name',
            'PluginURI'   => 'Plugin URI',
            'Version'     => 'Version',
            'Description' => 'Description',
            'Author'      => 'Author',
            'AuthorURI'   => 'Author URI',
            'TextDomain'  => 'Text Domain',
            'DomainPath'  => 'Domain Path'
        );

        $pluginData = get_file_data($pluginFile, $defaultHeaders, 'plugin');

        $pluginData['Title']      = $pluginData['Name'];
        $pluginData['AuthorName'] = $pluginData['Author'];

        return $pluginData;
    }
}

$gwaShortcode = new GwaShortcode();
