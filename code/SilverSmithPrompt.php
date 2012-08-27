<?php



/**
 * A utility class for outputting to the CLI	
 * @package SilverSmith
 *
 * @author Aaron Carlino <unclecheese@leftandmain.com>
 */
class SilverSmithPrompt {



	/**
	 * Adds the proper delimiters to a given string of text to color it in a CLI
	 *
	 * @param string The text to output
	 * @param string The foreground color, e.g. "red"
	 * @param string The background color, e.g. "gray"
	 * @param string The style of the text, e.g. "bold"
	 * @return string
	 */
    public static function tc_colored($text, $foreground = null, $background = null, $style = null) {
        static $fmt_str = "\033[%dm%s";
        static $reset = "\033[0m";
        $options = self::get_options();
        
        $args    = array(
            $text,
            $foreground,
            $background,
            $style
        );
        $text    = array_shift($args);
        foreach ($args as $arg) {
            if (!$arg)
                continue;
            if (isset($options[$arg])) {
                $text = sprintf($fmt_str, $options[$arg], $text);
            } else {
                self::say("Invalid argument: $arg.");
                exit(1);
            }
        }
        return $text . $reset;
    }
    
    
    
    /**
     * Output text to the CLI followed by a line break
     *
	 * @param string The text to output
	 * @param string The foreground color, e.g. "red"
	 * @param string The background color, e.g. "gray"
	 * @param string The style of the text, e.g. "bold"
	 * @return string
     */
    public static function say($text, $foreground = null, $background = null, $style = null) {
        fwrite(STDOUT, self::tc_colored($text, $foreground, $background, $style) . "\n");
    }
    
    
    
    
    /**
     * Output text to the CLI inline, with no line break
     *
	 * @param string The text to output
	 * @param string The foreground color, e.g. "red"
	 * @param string The background color, e.g. "gray"
	 * @param string The style of the text, e.g. "bold"
	 * @return string
     */
    public static function write($text, $foreground = null, $background = null, $style = null) {
        fwrite(STDOUT, self::tc_colored($text, $foreground, $background, $style));
    }
    
    
    
    
	/**
	 * Gets the available options for text foreground, background, and style
	 *
	 * @return array
	 */    
    protected static function get_options()
    {
        $options = array_merge(array_combine(array(
            'grey',
            'red',
            'green',
            'yellow',
            'blue',
            'magenta',
            'cyan',
            'white'
        ), range(30, 37)), array_combine(array(
            'on_grey',
            'on_red',
            'on_green',
            'on_yellow',
            'on_blue',
            'on_magenta',
            'on_cyan',
            'on_white'
        ), range(40, 47)), array_combine(array(
            'bold',
            'dark',
            '',
            'underline',
            'blink',
            '',
            'reverse',
            'concealed'
        ), range(1, 8)));
        unset($options['']);
        return $options;
    }
}
