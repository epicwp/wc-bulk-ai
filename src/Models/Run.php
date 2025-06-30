<?php
namespace EPICWP\WC_Bulk_AI\Models;

use DateTime;

class Run {

    public const TABLE_NAME = 'wcbai_runs';

    /**
     * The task that is being processed on all the jobs in this bulk run.
     *
     * @var string
     */
    protected string $task;

    /**
     * The status of the run.
     *
     * @var string
     */
    protected string $status;

    /**
     * The creation date of the run.
     *
     * @var DateTime
     */
    protected DateTime $created_at;

    /**
     * The start date of the run.
     *
     * @var ?DateTime
     */
    protected ?DateTime $started_at = null;

    /**
     * The finish date of the run.
     *
     * @var ?DateTime
     */
    protected ?DateTime $finished_at = null;

    public function __construct(
        protected int $id,
    ) {
        $this->load();
    }

    /**
     * Get the full table name with WordPress prefix
     *
     * @return string
     */
    public static function get_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Load the run from the database.
     *
     * @return void
     */
    protected function load(): void {
        global $wpdb;
        $run = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::get_table_name() . " WHERE id = %d",
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

    /**
     * Get the run ID.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get the task that is being processed on all the jobs in this bulk run.
     *
     * @return string
     */
    public function get_task(): string {
        return $this->task;
    }

    /**
     * Get the next job for the run.
     *
     * @return Job|null
     */
    public function get_next_job(): ?Job {
        global $wpdb;
        $job = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . Job::get_table_name() . " WHERE run_id = %d ORDER BY id ASC LIMIT 1",
                $this->id
            )
        );
        return $job ? new Job($job->id) : null;
    }

    /**
     * Get the status of the run.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Get the progress of the run. This is the number of completed jobs divided by the total number of jobs.
     *
     * @return float
     */
    public function get_progress(): float {
        $progress = $this->get_completed_jobs_count();
        $total = $this->get_total_jobs_count();
        return $total > 0 ? (float) $progress / (float) $total : 0;
    }

    /**
     * Get the number of completed jobs in the run.
     *
     * @return int
     */
    public function get_completed_jobs_count(): int {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . Job::get_table_name() . " WHERE run_id = %d AND status = 'completed'",
                $this->id
            )
        );
    }

    /**
     * Get the total number of jobs in the run.
     *
     * @return int
     */
    public function get_total_jobs_count(): int {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . Job::get_table_name() . " WHERE run_id = %d",
                $this->id
            )
        );
    }

    /**
     * Get the creation date of the run.
     *
     * @return DateTime
     */
    public function get_created_at(): DateTime {
        return $this->created_at;
    }

    /**
     * Get the start date of the run.
     *
     * @return ?DateTime
     */
    public function get_started_at(): ?DateTime {
        return $this->started_at;
    }

    /**
     * Get the finish date of the run.
     *
     * @return ?DateTime
     */
    public function get_finished_at(): ?DateTime {
        return $this->finished_at;
    }

    /**
     * Create a new run.
     *
     * @param string $task
     * @return Run
     */
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
                "SELECT * FROM " . self::get_table_name() . " ORDER BY id DESC"
            )
        );
        return array_map(function($run) {
            return new Run($run->id);
        }, $runs);
    }
    
}