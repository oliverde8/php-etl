<?php

namespace Oliverde8\Component\PhpEtl\Model\State;

enum OperationStateEnum
{
    case NotInit;
    case Waiting;
    case Async;
    case Running;
    case Stopping;
    case Stopped;

    public function label(): string
    {
        return match($this) {
            static::NotInit => 'Not Initialized',
            static::Waiting => 'Waiting',
            static::Async => 'Async',
            static::Running => 'Running',
            static::Stopping => 'Stopping',
            static::Stopped => 'Stopped',
        };
    }
}
