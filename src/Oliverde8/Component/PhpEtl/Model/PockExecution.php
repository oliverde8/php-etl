<?php
declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model;

class PockExecution implements ExecutionInterface
{
    protected string $id;

    public function __construct(protected \DateTime $createdAt)
    {
        $this->id = (string) random_int(1, 100000);
    }


    #[\Override]
    public function getId(): string
    {
        return $this->id;
    }

    #[\Override]
    public function GetCreateTime(): \DateTime
    {
        return $this->createdAt;
    }
}