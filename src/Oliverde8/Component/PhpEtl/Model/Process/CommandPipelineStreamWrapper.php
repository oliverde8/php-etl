<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model\Process;

class CommandPipelineStreamWrapper
{
    public const string PROTOCOL = 'etl-command';

    public $context;

    private CommandPipeline $pipeline;

    /**
     * @return resource
     */
    public static function open(CommandPipeline $pipeline)
    {
        if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            stream_wrapper_register(self::PROTOCOL, self::class);
        }

        $context = stream_context_create([self::PROTOCOL => ['pipeline' => $pipeline]]);

        return fopen(self::PROTOCOL . '://pipeline', 'r', false, $context);
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $this->pipeline = stream_context_get_options($this->context)[self::PROTOCOL]['pipeline'];

        return true;
    }

    public function stream_read(int $count): string
    {
        return $this->pipeline->read($count);
    }

    public function stream_eof(): bool
    {
        return $this->pipeline->eof();
    }

    public function stream_close(): void
    {
    }
}
