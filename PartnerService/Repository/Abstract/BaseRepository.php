<?php

namespace App\Libraries\PartnerService\Repository\Abstract;

use Exception;

abstract class BaseRepository
{
    protected array $required_fields;
    protected array $data;
    protected $validator;

    abstract public function sanitizeData(): array;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->validator = service('validation');
    }

    protected function is_valid(array $data): bool
    {
        if(!empty($this->required_fields)) {
            $missing_fields = array_diff($this->required_fields, array_keys($data));
        }

        return empty($missing_fields);
    }

    public function getJSON()
    {
        return json_encode($this->data);
    }

    public function getAllData()
    {
        return $this->data;
    }

    public function validate($data, $rules)
    {
        
        $err = $this->validator->run($data, $rules);
            
        return $err ?? throw new Exception('Erro: ' . json_encode($this->validator->getErrors()));

    }
}