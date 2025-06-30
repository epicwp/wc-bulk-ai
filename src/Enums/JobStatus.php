<?php
namespace EPICWP\WC_Bulk_AI\Enums;

enum JobStatus: string {
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Get all status values as an array
     *
     * @return array<string>
     */
    public static function values(): array {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Check if status is a final state (no further processing)
     *
     * @return bool
     */
    public function isFinal(): bool {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED]);
    }

    /**
     * Check if status indicates the job is active
     *
     * @return bool
     */
    public function isActive(): bool {
        return $this === self::RUNNING;
    }

    /**
     * Check if status indicates the job is waiting
     *
     * @return bool
     */
    public function isPending(): bool {
        return $this === self::PENDING;
    }
}
