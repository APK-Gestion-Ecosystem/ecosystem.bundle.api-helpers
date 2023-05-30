<?php

namespace Ecosystem\ApiHelpersBundle\Exception;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \Exception
{
    public function __construct(private ConstraintViolationListInterface $constraintViolationList, string $message = 'Invalid data', int $code = 400)
    {
        parent::__construct($message, $code);
    }

    public function getErrors(): array
    {
        $errors = [];
        /** @var ConstraintViolation $validationError */
        foreach ($this->constraintViolationList as $validationError) {
            $errors[] = [
                'propertyPath' => $validationError->getPropertyPath(),
                'message' => $validationError->getMessage(),
                'value' => $validationError->getInvalidValue(),
            ];
        }
        return $errors;
    }
}