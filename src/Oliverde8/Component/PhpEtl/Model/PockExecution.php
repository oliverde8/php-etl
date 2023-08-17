<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model;

class PockExecution implements ExecutionInterface
{
    protected \DateTime $createdAt;

    protected string $id;

    public function __construct(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
        $this->id = (string) rand(1, 100000);
    }


    public function getId(): string
    {
        return $this->id;
    }

    public function GetCreateTime(): \DateTime
    {
        return $this->createdAt;
    }
}