<?php

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
    protected $chainOperationName;

    /**
     * ChainOperationException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param string $chainOperationName
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null, $chainOperationName = '')
    {
        $this->chainOperationName = $chainOperationName;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getChainOperationName()
    {
        return $this->chainOperationName;
    }
}