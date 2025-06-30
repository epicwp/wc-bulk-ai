<?php
namespace EPICWP\WC_Bulk_AI\Models;

use DateTime;
use EPICWP\WC_Bulk_AI\Enums\RunStatus;

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
     * @var RunStatus
     */
    protected RunStatus $status;

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
        $this->status = RunStatus::from($run->status);
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
                "SELECT * FROM " . Job::get_table_name() . " WHERE run_id = %d AND status = %s ORDER BY id ASC LIMIT 1",
                $this->id,
                'pending'
            )
        );
        return $job ? new Job($job->id) : null;
    }

    /**
     * Get the status of the run.
     *
     * @return RunStatus
     */
    public function get_status(): RunStatus {
        return $this->status;
    }

    /**
     * Update the status of the run.
     *
     * @param RunStatus $status
     * @return void
     */
    public function update_status(RunStatus $status): void {
        global $wpdb;
        $wpdb->update(
            self::get_table_name(),
            array('status' => $status->value),
            array('id' => $this->id)
        );
        $this->status = $status;
    }

    /**
     * Start the run.
     *
     * @return void
     */
    public function start(): void {
        global $wpdb;
        $now = \current_time('mysql');
        $wpdb->update(
            self::get_table_name(),
            array(
                'status' => RunStatus::RUNNING->value,
                'started_at' => $now
            ),
            array('id' => $this->id)
        );
        $this->status = RunStatus::RUNNING;
        $this->started_at = new DateTime($now);
    }

    /**
     * Complete the run.
     *
     * @return void
     */
    public function complete(): void {
        global $wpdb;
        $now = \current_time('mysql');
        $wpdb->update(
            self::get_table_name(),
            array(
                'status' => RunStatus::COMPLETED->value,
                'finished_at' => $now
            ),
            array('id' => $this->id)
        );
        $this->status = RunStatus::COMPLETED;
        $this->finished_at = new DateTime($now);
    }

    /**
     * Fail the run.
     *
     * @return void
     */
    public function fail(): void {
        global $wpdb;
        $now = \current_time('mysql');
        $wpdb->update(
            self::get_table_name(),
            array(
                'status' => RunStatus::FAILED->value,
                'finished_at' => $now
            ),
            array('id' => $this->id)
        );
        $this->status = RunStatus::FAILED;
        $this->finished_at = new DateTime($now);
    }

    /**
     * Cancel the run.
     *
     * @return void
     */
    public function cancel(): void {
        global $wpdb;
        $now = \current_time('mysql');
        $wpdb->update(
            self::get_table_name(),
            array(
                'status' => RunStatus::CANCELLED->value,
                'finished_at' => $now
            ),
            array('id' => $this->id)
        );
        $this->status = RunStatus::CANCELLED;
        $this->finished_at = new DateTime($now);
    }

    /**
     * Pause the run.
     *
     * @return void
     */
    public function pause(): void {
        global $wpdb;
        $wpdb->update(
            self::get_table_name(),
            array('status' => RunStatus::PAUSED->value),
            array('id' => $this->id)
        );
        $this->status = RunStatus::PAUSED;
    }

    /**
     * Resume a paused run.
     *
     * @return void
     */
    public function resume(): void {
        global $wpdb;
        $wpdb->update(
            self::get_table_name(),
            array('status' => RunStatus::RUNNING->value),
            array('id' => $this->id)
        );
        $this->status = RunStatus::RUNNING;
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
                "SELECT COUNT(*) FROM " . Job::get_table_name() . " WHERE run_id = %d AND status = %s",
                $this->id,
                'completed'
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
            self::get_table_name(),
            array(
                'task' => $task,
                'status' => RunStatus::PENDING->value,
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

    /**
     * Get the latest run.
     *
     * @return ?Run
     */
    public static function get_latest(): ?Run {
        global $wpdb;
        $run = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::get_table_name() . " ORDER BY id DESC LIMIT 1"
            )
        );
        return $run ? new Run($run->id) : null;
    }
}