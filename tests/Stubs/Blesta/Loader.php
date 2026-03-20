<?php
class Loader
{
    public static function loadComponents($target, array $components)
    {
        foreach ($components as $component) {
            if ($component === 'Input') {
                $target->Input = new Input();
            }
        }
    }

    public static function loadHelpers($target, array $helpers)
    {
    }

    public static function loadModels($target, array $models)
    {
    }

    public static function load($path)
    {
        if (file_exists($path)) {
            require_once $path;
        }
    }
}
