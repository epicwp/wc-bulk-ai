<?php
namespace EPICWP\WC_Bulk_AI\Models;

use DateTime;

class Run {

    protected string $task;
    protected string $status;
    protected DateTime $created_at;
    protected ?DateTime $started_at = null;
    protected ?DateTime $finished_at = null;

    public function __construct(
        protected int $id,
    ) {
        $this->load();
    }

    protected function load(): void {
        global $wpdb;
        $run = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wcbai_runs WHERE id = %d",
                $this->id
            )
        );
        
        if (!$run) {
            throw new \Exception("Run with ID {$this->id} not found");
        }
        
        $this->task = $run->task;
        $this->status = $run->status;
        $this->created_at = new DateTime($run->created_at);
        $this->started_at = $run->started_at ? new DateTime($run->started_at) : null;
        $this->finished_at = $run->finished_at ? new DateTime($run->finished_at) : null;
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_task(): string {
        return $this->task;
    }

    public function get_next_job(): ?Job {
        global $wpdb;
        $job = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wcbai_jobs WHERE run_id = %d ORDER BY id ASC LIMIT 1",
                $this->id
            )
        );
        return $job ? new Job($job->id) : null;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_progress(): float {
        $progress = $this->get_completed_jobs_count();
        $total = $this->get_total_jobs_count();
        return $total > 0 ? (float) $progress / (float) $total : 0;
    }

    public function get_completed_jobs_count(): int {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wcbai_jobs WHERE run_id = %d AND status = 'completed'",
                $this->id
            )
        );
    }

    public function get_total_jobs_count(): int {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wcbai_jobs WHERE run_id = %d",
                $this->id
            )
        );
    }

    public function get_created_at(): DateTime {
        return $this->created_at;
    }

    public function get_started_at(): ?DateTime {
        return $this->started_at;
    }

    public function get_finished_at(): ?DateTime {
        return $this->finished_at;
    }

    public static function create(string $task): Run {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'wcbai_runs',
            array(
                'task' => $task,
                'status' => 'pending',
                'created_at' => \current_time('mysql'),
            )
        );
        return new Run($wpdb->insert_id);
    }

    /**
     * List all runs.
     *
     * @return array<Run>
     */
    public static function list(): array {
        global $wpdb;
        $runs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wcbai_runs ORDER BY id DESC"
            )
        );
        return array_map(function($run) {
            return new Run($run->id);
        }, $runs);
    }
    
}