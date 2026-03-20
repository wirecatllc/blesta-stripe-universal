<?php
class Input
{
    private $rules = [];
    private $errors = [];

    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function validates(array $data)
    {
        return true;
    }

    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    public function errors()
    {
        return $this->errors;
    }
}
