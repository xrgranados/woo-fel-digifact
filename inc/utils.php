<?php

/**
 * Renders CSS styles as a string.
 *
 * @param array $styles An array of CSS styles.
 * @return string A string of CSS styles.
 */
if (!function_exists('_renderStyles')) {
    function _renderStyles(array $styles = []) {
        $propertiesStrings = [];

        foreach ($styles as $property => $value) {
            if ($value === '') {
                $attributeStrings[] = $property;
                continue;
            }

            $propertiesStrings[] = "{$property}: {$value};";
        }

        return implode(' ', $propertiesStrings);
    }
}

/**
 * Renders HTML attributes as a string.
 *
 * @param array $attributes An array of HTML attributes.
 * @return string A string of HTML attributes.
 */
if (!function_exists('_renderAttributes')) {
    function _renderAttributes(array $attributes = []) {
        $attributeStrings = [];

        foreach ($attributes as $attribute => $value) {
            if ($value === '') {
                $attributeStrings[] = $attribute;
                continue;
            }

            $attributeStrings[] = "{$attribute}=\"{$value}\"";
        }

        return implode(' ', $attributeStrings);
    }
}

/**
 * Debug function.
 *
 * @return void
 */
if (!function_exists('_dd')) {
    function _dd()
    {
        $args = func_get_args();
        $styles = [
            'background-color' => '#111',
            'color' => 'green',
            'color' => 'green',
            'padding' => '15px',
        ];

        $renderedStyles = _renderStyles($styles);
        foreach ($args as $key => $arg) {
            echo "<pre style=\"{$renderedStyles}\">";
            print_r($arg);
            echo '</pre>';
        }
        exit;
    }
}

/**
 * Returns the old value of a field from the $_POST array.
 *
 * @param string $field The field name.
 * @param mixed $default The default value to return if the field is not set.   Default is null.
 */
if (!function_exists('old_post')) {
    function old_post($field, $default = null)
    {
        return isset($_POST[$field]) ? $_POST[$field] : $default;
    }
}

/**
 * Returns the old value of a field from the $_GET array.
 *
 * @param string $field The field name.
 * @param mixed $default The default value to return if the field is not set.   Default is null.
 */
if (!function_exists('old_get')) {
    function old_get($field, $default = null)
    {
        return isset($_GET[$field]) ? $_GET[$field] : $default;
    }
}

/**
 * Returns the old value of a field from the $_REQUEST array.
 *
 * @param string $field The field name.
 * @param mixed $default The default value to return if the field is not set.   Default is null.
 */
if (!function_exists('_old')) {
    function _old($field, $default = null)
    {
        $request = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
        if ($request === 'POST') {
            return old_post($field, $default);
        }

        return old_get($field, $default);
    }
}

/**
 * Formats a date according to the given format.
 *
 * @param string $date The date to format.
 * @param string $format The format to use. Default is 'Y-m-d H:i:s'.
 * @return string The formatted date.
 */
if (!function_exists('_formatDate')) {
    function _formatDate($date, $format = 'Y-m-d H:i:s')
    {
        $date = new DateTime($date);
        return $date->format($format);
    }
}

/**
 * Renders a link.
 *
 * @param string $url The URL of the link.
 * @param string $text The text of the link.
 * @param array $attributes An array of HTML attributes.
 * @return string The rendered link.
 */
if (!function_exists('_link')) {
    function _link($url = '#', $text = '', $attributes = [])
    {
        $attributes = _renderAttributes($attributes);
        return "<a href=\"{$url}\" {$attributes}>{$text}</a>";
    }
}
