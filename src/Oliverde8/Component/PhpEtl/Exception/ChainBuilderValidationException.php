<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Exception;

use Symfony\Component\Validator\ConstraintViolation;
use Throwable;

/**
 * Class ChainBuilderValidationException
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl\Exception
 */
class ChainBuilderValidationException extends \Exception
{
    /**
     * ChainBuilderValidationException constructor.
     *
     * @param string $operation
     * @param ConstraintViolation[] $violations
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $operation, array $violations, int $code = 0, ?Throwable $previous = null)
    {
        $msg = "There was an error building the operation '$operation' : ";
        foreach ($violations as $violation) {
            $msg .= "\n - " . $violation->getPropertyPath() . " - " . implode(', ', $violation->getParameters()) . " : " . $violation->getMessage();
        }
        $msg .= "\n";

        parent::__construct($msg, $code, $previous);
    }
}