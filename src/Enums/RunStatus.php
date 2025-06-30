<?php
namespace EPICWP\WC_Bulk_AI\Enums;

enum RunStatus: string {
    case PENDING = 'pending';
    case RUNNING = 'running'; 
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case PAUSED = 'paused';

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
     * Check if status indicates the run is active
     *
     * @return bool
     */
    public function isActive(): bool {
        return $this === self::RUNNING;
    }

    /**
     * Check if status indicates the run is waiting
     *
     * @return bool
     */
    public function isPending(): bool {
        return $this === self::PENDING;
    }

    /**
     * Check if status indicates the run can be resumed
     *
     * @return bool
     */
    public function canBeResumed(): bool {
        return in_array($this, [self::PENDING, self::PAUSED]);
    }

    /**
     * Check if status indicates the run can be cancelled
     *
     * @return bool
     */
    public function canBeCancelled(): bool {
        return in_array($this, [self::PENDING, self::RUNNING, self::PAUSED]);
    }
}
