<?php
abstract class Gateway
{
    protected $currency;
    protected $view;

    private $config = [];
    private $logEntries = [];

    public function loadConfig($path)
    {
        if (file_exists($path)) {
            $this->config = json_decode(file_get_contents($path), true) ?: [];
        }
    }

    public function getName()
    {
        return isset($this->config['name']) ? $this->config['name'] : '';
    }

    public function getVersion()
    {
        return isset($this->config['version']) ? $this->config['version'] : '';
    }

    public function log($url, $data, $direction = 'input', $success = true)
    {
        $this->logEntries[] = compact('url', 'data', 'direction', 'success');
    }

    public function getLogEntries()
    {
        return $this->logEntries;
    }

    public function makeView($view, $type, $path)
    {
        return new View($view, $type);
    }

    public function getCurrencies()
    {
        return isset($this->config['currencies']) ? $this->config['currencies'] : [];
    }
}
