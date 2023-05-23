<?php

namespace Ecosystem\ApiHelpersBundle\Exception;

class InvalidDataException extends \Exception
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Invalid data', int $code = 400)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}