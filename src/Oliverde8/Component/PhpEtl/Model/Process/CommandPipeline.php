<?php

declare(strict_types=1);

namespace Oliverde8\Component\PhpEtl\Model\Process;

use Oliverde8\Component\PhpEtl\Exception\CommandException;

class CommandPipeline
{
    public const string FILE_PLACEHOLDER = '{file}';

    private array $processes = [];

    private $stdout;

    private $stdin = null;

    private string $buffer = '';

    private readonly string $errorFile;

    private bool $closed = false;

    /**
     * @param string[][] $commands
     * @param resource $input
     */
    public function __construct(array $commands, private $input, ?string $cwd = null, private readonly ?float $idleTimeout = null)
    {
        $meta = stream_get_meta_data($input);
        $localPath = ($meta['wrapper_type'] ?? null) === 'plainfile' ? realpath($meta['uri']) : false;
        $file = $localPath === false ? '-' : $localPath;
        $this->errorFile = tempnam(sys_get_temp_dir(), 'etl_cmd_');

        $stdin = $localPath === false ? ['pipe', 'r'] : $input;
        foreach ($commands as $command) {
            $command = array_map(fn(string $arg): string => str_replace(self::FILE_PLACEHOLDER, $file, $arg), $command);
            $pipes = [];
            $process = proc_open($command, [0 => $stdin, 1 => ['pipe', 'w'], 2 => ['file', $this->errorFile, 'a']], $pipes, $cwd);
            if (!is_resource($process)) {
                $this->terminate();
                throw new CommandException("Unable to start command: " . implode(' ', $command));
            }

            if (is_resource($stdin) && $stdin !== $input) {
                fclose($stdin);
            }
            if (isset($pipes[0])) {
                $this->stdin = $pipes[0];
                stream_set_blocking($this->stdin, false);
            }

            $this->processes[] = $process;
            $stdin = $pipes[1];
        }

        $this->stdout = $stdin;
        stream_set_blocking($this->stdout, false);
    }

    public function read(int $length): string
    {
        while (!feof($this->stdout)) {
            $read = [$this->stdout];
            $write = $this->stdin ? [$this->stdin] : [];
            $except = null;
            $seconds = $this->idleTimeout === null ? null : (int) $this->idleTimeout;
            $microseconds = $this->idleTimeout === null ? null : (int) (($this->idleTimeout - $seconds) * 1_000_000);
            $ready = stream_select($read, $write, $except, $seconds, $microseconds);
            if ($ready === false) {
                throw new CommandException("Unable to wait for command output.");
            }
            if ($ready === 0) {
                throw new CommandException("Command had no activity for {$this->idleTimeout} seconds.");
            }

            if (!empty($write)) {
                $this->pump();
            }

            if (in_array($this->stdout, $read, true)) {
                $data = fread($this->stdout, $length);
                if ($data !== '' && $data !== false) {
                    return $data;
                }
            }
        }

        return '';
    }

    public function eof(): bool
    {
        return feof($this->stdout);
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->closePipes();

        $failures = [];
        foreach ($this->processes as $index => $process) {
            $exitCode = proc_close($process);
            if ($exitCode !== 0) {
                $failures[] = "command #$index exited with code $exitCode";
            }
        }

        $errors = $this->readErrors();
        if (!empty($failures)) {
            throw new CommandException(implode(', ', $failures) . ($errors === '' ? '' : ": $errors"));
        }
    }

    public function terminate(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->closePipes();

        foreach ($this->processes as $process) {
            proc_terminate($process);
            proc_close($process);
        }

        $this->readErrors();
    }

    public function __destruct()
    {
        $this->terminate();
    }

    private function pump(): void
    {
        if ($this->buffer === '') {
            $this->buffer = (string) fread($this->input, 65536);
            if ($this->buffer === '' && feof($this->input)) {
                fclose($this->stdin);
                $this->stdin = null;
                return;
            }
            if ($this->buffer === '' && stream_get_meta_data($this->input)['timed_out']) {
                throw new CommandException("Timed out while reading the input stream.");
            }
            if ($this->buffer === '') {
                return;
            }
        }

        $written = @fwrite($this->stdin, $this->buffer);
        if ($written === false) {
            fclose($this->stdin);
            $this->stdin = null;
            $this->buffer = '';
            return;
        }

        $this->buffer = substr($this->buffer, $written);
    }

    private function closePipes(): void
    {
        if (is_resource($this->stdin)) {
            fclose($this->stdin);
        }
        $this->stdin = null;

        if (is_resource($this->stdout)) {
            fclose($this->stdout);
        }

        if (is_resource($this->input)) {
            fclose($this->input);
        }
    }

    private function readErrors(): string
    {
        if (!file_exists($this->errorFile)) {
            return '';
        }

        $errors = trim((string) file_get_contents($this->errorFile));
        unlink($this->errorFile);

        return $errors;
    }
}
