<?php

namespace Ecosystem\ApiHelpersBundle\Exception;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\HttpFoundation\Response;

class ValidationException extends \Exception
{
    public function __construct(
        private ConstraintViolationListInterface $constraintViolationList,
        string $message = 'Invalid data',
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY
    ) {
        parent::__construct(sprintf('%s - %s', $message, json_encode($this->getErrors())), $code);
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