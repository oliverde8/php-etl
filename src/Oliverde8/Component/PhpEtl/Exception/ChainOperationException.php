<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Exception;

use Throwable;

/**
 * Class ChainException
 *
 * @author    de Cramer Oliver<oiverde8@gmail.com>
 * @copyright 2018 Oliverde8
 * @package Oliverde8\Component\PhpEtl
 */
class ChainOperationException extends \Exception
{
    /** @var string */
    protected string $chainOperationName;

    /**
     * ChainOperationException constructor.
     */
    public function __construct(string $message = "", int $code = 0, \Exception $previous = null, string $chainOperationName = '')
    {
        $this->chainOperationName = $chainOperationName;

        parent::__construct($message, $code, $previous);
    }

    public function getChainOperationName(): string
    {
        return $this->chainOperationName;
    }
}