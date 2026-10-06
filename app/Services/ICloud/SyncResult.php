<?php

namespace App\Services\ICloud;

/**
 * Outcome of one album sync. Deliberately plain so commands, jobs and tests can
 * all report the same numbers.
 */
class SyncResult
{
    public function __construct(
        public readonly int $created = 0,
        public readonly int $failed = 0,
        public readonly int $pruned = 0,
        public readonly int $prunable = 0,
        public readonly bool $unchanged = false,
        public readonly bool $skipped = false,
        public readonly ?string $error = null,
    ) {
    }

    public static function unchanged(): self
    {
        return new self(unchanged: true);
    }

    public static function skipped(string $reason): self
    {
        return new self(skipped: true, error: $reason);
    }

    public function summary(): string
    {
        if ($this->skipped) {
            return "skipped: {$this->error}";
        }

        if ($this->unchanged) {
            return 'unchanged';
        }

        $parts = ["created {$this->created}"];

        if ($this->failed > 0) {
            $parts[] = "failed {$this->failed}";
        }

        if ($this->pruned > 0) {
            $parts[] = "pruned {$this->pruned}";
        }

        // Surfaced separately from `pruned` so a prune-disabled album still
        // reports what it *would* remove.
        if ($this->prunable > 0) {
            $parts[] = "would prune {$this->prunable} (pruning disabled)";
        }

        return implode(', ', $parts);
    }
}
