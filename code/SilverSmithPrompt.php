<?php

class SilverSmithPrompt
{
    public static function tc_colored($text, $foreground = null, $background = null, $style = null)
    {
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
    public static function say($text, $foreground = null, $background = null, $style = null)
    {
        fwrite(STDOUT, self::tc_colored($text, $foreground, $background, $style) . "\n");
    }
    public static function write($text, $foreground = null, $background = null, $style = null)
    {
        fwrite(STDOUT, self::tc_colored($text, $foreground, $background, $style));
    }
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
