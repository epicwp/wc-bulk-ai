<?php
namespace EPICWP\WC_Bulk_AI\Models;

use DateTime;

class Job {

    protected string $status;
    protected int $product_id;
    protected int $run_id;
    protected string $task;
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
        $job = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wc_bulk_ai_jobs WHERE id = %d",
                $this->id
            )
        );
        $this->status = $job->status;
        $this->product_id = $job->product_id;
        $this->run_id = $job->run_id;
        $this->task = $job->task;
        $this->created_at = new DateTime($job->created_at);
        $this->started_at = new DateTime($job->started_at);
        $this->finished_at = new DateTime($job->finished_at);
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_product_id(): int {
        return $this->product_id;
    }

    public function get_run_id(): int {
        return $this->run_id;
    }

    public function get_task(): string {
        return $this->task;
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

    public static function create(string $task, int $product_id): Job {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'wc_bulk_ai_jobs',
            array(
                'task' => $task,
                'product_id' => $product_id,
            )
        );
        return new Job($wpdb->insert_id);
    }
}   