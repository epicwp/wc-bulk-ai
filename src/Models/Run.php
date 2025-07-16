<?php
namespace EPICWP\WC_Bulk_AI\Models;

use DateTime;
use EPICWP\WC_Bulk_AI\Enums\JobStatus;
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
     * Create a new run.
     *
     * @param string $task
     * @return Run
     */
    public static function create( string $task ): Run {
        global $wpdb;
        $wpdb->insert(
            self::get_table_name(),
            array(
                'created_at' => \current_time( 'mysql' ),
                'status'     => RunStatus::PENDING->value,
                'task'       => $task,
            ),
        );
        if ( 0 === $wpdb->insert_id ) {
            throw new \Exception( 'Failed to create task.' );
        }
        return new Run( $wpdb->insert_id );
    }

    public static function get_by_id( int $id ): ?Run {
        global $wpdb;
        $run = $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM ' . self::get_table_name() . ' WHERE id = %d', $id ),
        );
        return $run ? new Run( $run->id ) : null;
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
                'SELECT * FROM ' . self::get_table_name() . ' ORDER BY id DESC',
            ),
        );
        return \array_map(
            static fn( $run ) => new Run( $run->id ),
            $runs,
        );
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
                'SELECT * FROM ' . self::get_table_name() . ' ORDER BY id DESC LIMIT 1',
            ),
        );
        return $run ? new Run( $run->id ) : null;
    }

    /**
     * Get available runs (not completed/failed/cancelled).
     *
     * @return array<Run>
     */
    public static function get_available(): array {
        global $wpdb;
        $runs = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . self::get_table_name() . ' WHERE status IN (%s, %s, %s) ORDER BY id DESC',
                RunStatus::PENDING->value,
                RunStatus::RUNNING->value,
                RunStatus::PAUSED->value,
            ),
        );
        return \array_map(
            static fn( $run ) => new Run( $run->id ),
            $runs,
        );
    }

    /**
     * Get the total number of runs in the database.
     *
     * @return int
     */
    public static function get_count(): int {
        global $wpdb;
        return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::get_table_name() );
    }

    /**
     * Clear all runs from the database.
     *
     * @return int Number of runs deleted
     */
    public static function clear_all(): int {
        global $wpdb;
        $count = self::get_count();
        $wpdb->query( 'DELETE FROM ' . self::get_table_name() );
        return $count;
    }

    public function __construct(
        protected int $id,
    ) {
        $this->load();
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
                'SELECT * FROM ' . Job::get_table_name() . ' WHERE run_id = %d AND status = %s ORDER BY id ASC LIMIT 1',
                $this->id,
                JobStatus::PENDING->value,
            ),
        );
        return $job ? new Job( $job->id ) : null;
    }

    /**
     * Get the job IDs of the run.
     *
     * @return array<int>
     */
    public function get_job_ids(): array {
        global $wpdb;
        $job_ids = $wpdb->get_col(
            $wpdb->prepare( 'SELECT id FROM ' . Job::get_table_name() . ' WHERE run_id = %d', $this->id ),
        );
        return \array_map( static fn( $job_id ) => (int) $job_id, $job_ids );
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
    public function update_status( RunStatus $status ): void {
        global $wpdb;
        $wpdb->update(
            self::get_table_name(),
            array( 'status' => $status->value ),
            array( 'id' => $this->id ),
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
        $now = \current_time( 'mysql' );
        $wpdb->update(
            self::get_table_name(),
            array(
                'started_at' => $now,
                'status'     => RunStatus::RUNNING->value,
            ),
            array( 'id' => $this->id ),
        );
        $this->status     = RunStatus::RUNNING;
        $this->started_at = new DateTime( $now );
    }

    /**
     * Complete the run.
     *
     * @return void
     */
    public function complete(): void {
        global $wpdb;
        $now = \current_time( 'mysql' );
        $wpdb->update(
            self::get_table_name(),
            array(
                'finished_at' => $now,
                'status'      => RunStatus::COMPLETED->value,
            ),
            array( 'id' => $this->id ),
        );
        $this->status      = RunStatus::COMPLETED;
        $this->finished_at = new DateTime( $now );
    }

    /**
     * Fail the run.
     *
     * @return void
     */
    public function fail(): void {
        global $wpdb;
        $now = \current_time( 'mysql' );
        $wpdb->update(
            self::get_table_name(),
            array(
                'finished_at' => $now,
                'status'      => RunStatus::FAILED->value,
            ),
            array( 'id' => $this->id ),
        );
        $this->status      = RunStatus::FAILED;
        $this->finished_at = new DateTime( $now );
    }

    /**
     * Cancel the run.
     *
     * @return void
     */
    public function cancel(): void {
        global $wpdb;
        $now = \current_time( 'mysql' );
        $wpdb->update(
            self::get_table_name(),
            array(
                'finished_at' => $now,
                'status'      => RunStatus::CANCELLED->value,
            ),
            array( 'id' => $this->id ),
        );
        $this->status      = RunStatus::CANCELLED;
        $this->finished_at = new DateTime( $now );
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
            array( 'status' => RunStatus::PAUSED->value ),
            array( 'id' => $this->id ),
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
            array( 'status' => RunStatus::RUNNING->value ),
            array( 'id' => $this->id ),
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
        $total    = $this->get_total_jobs_count();
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
                'SELECT COUNT(*) FROM ' . Job::get_table_name() . ' WHERE run_id = %d AND status = %s',
                $this->id,
                'completed',
            ),
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
                'SELECT COUNT(*) FROM ' . Job::get_table_name() . ' WHERE run_id = %d',
                $this->id,
            ),
        );
    }

    /**
     * Check if the run has a product.
     *
     * @param int $product_id
     * @return bool
     */
    public function has_product( int $product_id ): bool {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . Job::get_table_name() . ' WHERE run_id = %d AND product_id = %d',
                $this->id,
                $product_id,
            ),
        ) > 0;
    }

    /**
     * Get the product IDs of each job in this run.
     *
     * @return array<int>
     */
    public function get_product_ids(): array {
        global $wpdb;
        $product_ids = $wpdb->get_col(
            $wpdb->prepare(
                'SELECT product_id FROM ' . Job::get_table_name() . ' WHERE run_id = %d',
                $this->id,
            ),
        );
        return \array_map( static fn( $product_id ) => (int) $product_id, $product_ids );
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
     * Get a formatted string for CLI display showing ID, progress, and task.
     *
     * @return string
     */
    public function get_display_string(): string {
        $progress  = \round( $this->get_progress() * 100, 1 );
        $completed = $this->get_completed_jobs_count();
        $total     = $this->get_total_jobs_count();
        $status    = $this->get_status()->value;

        return \sprintf(
            'Run #%d [%s] - %s%% (%d/%d jobs) - "%s"',
            $this->get_id(),
            \strtoupper( $status ),
            $progress,
            $completed,
            $total,
            $this->get_task(),
        );
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
                'SELECT * FROM ' . self::get_table_name() . ' WHERE id = %d',
                $this->id,
            ),
        );

        if ( ! $run ) {
            throw new \Exception( "Run with ID {$this->id} not found" );
        }

        $this->task        = $run->task;
        $this->status      = RunStatus::from( $run->status );
        $this->created_at  = new DateTime( $run->created_at );
        $this->started_at  = $run->started_at ? new DateTime( $run->started_at ) : null;
        $this->finished_at = $run->finished_at ? new DateTime( $run->finished_at ) : null;
    }
}
