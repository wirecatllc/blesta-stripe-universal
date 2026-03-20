<?php
class View
{
    private $viewName;
    private $type;
    private $defaultView = '';
    private $data = [];

    public function __construct($view = '', $type = '')
    {
        $this->viewName = $view;
        $this->type = $type;
    }

    public function setDefaultView($path)
    {
        $this->defaultView = $path;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function fetch()
    {
        return json_encode([
            'view' => $this->viewName,
            'type' => $this->type,
            'data' => $this->data,
        ]);
    }
}
