<?php
namespace EPICWP\WC_Bulk_AI\Models;

use DateTime;

class Run {

    protected string $status;
    protected DateTime $created_at;
    protected DateTime $started_at;
    protected DateTime $finished_at;

    public function __construct(
        protected int $id,
    ) {
        $this->load();
    }

    protected function load(): void {
        global $wpdb;
        $run = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wc_bulk_ai_runs WHERE id = %d",
                $this->id
            )
        );
        $this->status = $run->status;
        $this->created_at = new DateTime($run->created_at);
        $this->started_at = new DateTime($run->started_at);
        $this->finished_at = new DateTime($run->finished_at);
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_next_job(): ?Job {
        global $wpdb;
        $job = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wc_bulk_ai_jobs WHERE run_id = %d ORDER BY id ASC LIMIT 1",
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
                "SELECT COUNT(*) FROM {$wpdb->prefix}wc_bulk_ai_jobs WHERE run_id = %d AND status = 'completed'",
                $this->id
            )
        );
    }

    public function get_total_jobs_count(): int {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wc_bulk_ai_jobs WHERE run_id = %d",
                $this->id
            )
        );
    }

    public function get_created_at(): DateTime {
        return $this->created_at;
    }

    public function get_started_at(): DateTime {
        return $this->started_at;
    }

    public function get_finished_at(): DateTime {
        return $this->finished_at;
    }

    public static function create(): Run {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'wc_bulk_ai_runs',
        );
        return new Run($wpdb->insert_id);
    }
    
}