<?php

namespace Tests\Tools\Providers;

class ProviderData
{
    private ?\Closure $procedure = null;
    public string $label = '';
    public string $field = '';
    private mixed $value = null;
    public string $table = '';
    public array $entity = [];
    public string $key = '';
    public array $response = [];
    public string $errorMessage = '';

    private array $requestBody = [];

    public function __get(string $key): mixed
    {
        if (! property_exists($this, $key)) {
            throw new \RuntimeException("Property $key was not declared.");
        }

        if (is_callable($this->{$key})) {
            return ($this->{$key})();
        }

        return $this->{$key};
    }

    public function __set(string $key, mixed $value): void
    {
        if (! property_exists($this, $key)) {
            throw new \RuntimeException("Property $key was not declared.");
        }

        $this->{$key} = $value;
    }

    public static function createInstance(
        string $label = '',
        string $field = '',
        mixed $value = null,
        string $table = '',
        array $entity = [],
        string $key = '',
        array $response = [],
        string $errorMessage = '',
    ): static {
        $static = new static;

        if (empty($label) && ! empty($field)) {
            $static->label = $field;
        } else {
            $static->label = $label;
        }

        $static->field = $field;
        $static->value = $value;
        $static->table = $table;
        $static->entity = $entity;
        $static->key = $key;
        $static->response = $response;
        $static->errorMessage = $errorMessage;

        return $static;
    }

    public function runCallback(array $configuration = []): static
    {
        if (is_null($this->procedure)) {
            return $this;
        }

        $data = ($this->procedure)($configuration);

        if ($data instanceof ProviderData) {
            $this->field = $data->field;
            $this->value = $data->value;
            $this->table = $data->table;
            $this->entity = $data->entity;
            $this->key = $data->key;
            $this->response = $data->response;
            $this->errorMessage = $data->errorMessage;

            return $this;
        }

        if (is_array($data)) {
            foreach ($data as $variable => $val) {
                if (property_exists($this, $variable)) {
                    $this->{$variable} = $val;
                }
            }

            return $this;
        }

        throw new \TypeError('Returned data form callback are not supported.');
    }

    public function setCallback(\Closure $callback): static
    {
        $this->procedure = $callback;

        return $this;
    }

    public function setRequestBody(array $body): self
    {
        $this->requestBody = $body;

        return $this;
    }

    public function getPreparedRequestBody(): array
    {
        return $this->requestBody;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
