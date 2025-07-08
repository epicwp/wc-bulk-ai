<?php
namespace EPICWP\WC_Bulk_AI\Models;

use DateTime;
use EPICWP\WC_Bulk_AI\Enums\JobStatus;

class Job {
    public const TABLE_NAME = 'wcbai_jobs';

    /**
     * The status of the job.
     *
     * @var JobStatus
     */
    protected JobStatus $status;

    /**
     * The product ID of the job.
     *
     * @var int
     */
    protected int $product_id;

    /**
     * The run ID of the job.
     *
     * @var int
     */
    protected int $run_id;

    /**
     * The creation date of the job.
     *
     * @var DateTime
     */
    protected DateTime $created_at;

    /**
     * The start date of the job.
     *
     * @var ?DateTime
     */
    protected ?DateTime $started_at = null;

    /**
     * The finish date of the job.
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
     * Create a new job.
     *
     * @param int $run_id
     * @param int $product_id
     * @return Job
     */
    public static function create( int $run_id, int $product_id ): Job {
        global $wpdb;
        $wpdb->insert(
            self::get_table_name(),
            array(
                'created_at' => \current_time( 'mysql' ),
                'product_id' => $product_id,
                'run_id'     => $run_id,
                'status'     => JobStatus::PENDING->value,
            ),
        );
        return new Job( $wpdb->insert_id );
    }

    /**
     * Get the total number of jobs in the database.
     *
     * @return int
     */
    public static function get_count(): int {
        global $wpdb;
        return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::get_table_name() );
    }

    /**
     * Clear all jobs from the database.
     *
     * @return int Number of jobs deleted
     */
    public static function clear_all(): int {
        global $wpdb;
        $count = self::get_count();
        $wpdb->query( 'DELETE FROM ' . self::get_table_name() );
        $wpdb->query( 'ALTER TABLE ' . self::get_table_name() . ' AUTO_INCREMENT = 1' );
        return $count;
    }

    /**
     * Constructor. Initializes the job from the database.
     *
     * @param int $id
     */
    public function __construct(
        protected int $id,
    ) {
        $this->load();
    }

    /**
     * Get the status of the job.
     *
     * @return JobStatus
     */
    public function get_status(): JobStatus {
        return $this->status;
    }

    /**
     * Get the product ID of the job.
     *
     * @return int
     */
    public function get_product_id(): int {
        return $this->product_id;
    }

    /**
     * Get the run ID of the job.
     *
     * @return int
     */
    public function get_run_id(): int {
        return $this->run_id;
    }

    /**
     * Get the task that is being processed on all the jobs in this bulk run.
     *
     * @return string
     */
    public function get_task(): string {
        $run = new Run( $this->run_id );
        return $run->get_task();
    }

    /**
     * Get the creation date of the job.
     *
     * @return DateTime
     */
    public function get_created_at(): DateTime {
        return $this->created_at;
    }

    /**
     * Get the start date of the job.
     *
     * @return ?DateTime
     */
    public function get_started_at(): ?DateTime {
        return $this->started_at;
    }

    /**
     * Get the finish date of the job.
     *
     * @return ?DateTime
     */
    public function get_finished_at(): ?DateTime {
        return $this->finished_at;
    }

    /**
     * Update the status of the job.
     *
     * @param JobStatus $status
     * @return void
     */
    public function update_status( JobStatus $status ): void {
        global $wpdb;
        $wpdb->update(
            self::get_table_name(),
            array( 'status' => $status->value ),
            array( 'id' => $this->id ),
        );
        $this->status = $status;
    }

    /**
     * Set the job status to running.
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
                'status'     => JobStatus::RUNNING->value,
            ),
            array( 'id' => $this->id ),
        );
        $this->status     = JobStatus::RUNNING;
        $this->started_at = new DateTime( $now );
    }

    /**
     * Set the job status to cancelled.
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
                'status'      => JobStatus::CANCELLED->value,
            ),
            array( 'id' => $this->id ),
        );
        $this->status      = JobStatus::CANCELLED;
        $this->finished_at = new DateTime( $now );
    }

    /**
     * Set the job status to completed.
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
                'status'      => JobStatus::COMPLETED->value,
            ),
            array( 'id' => $this->id ),
        );
        $this->status      = JobStatus::COMPLETED;
        $this->finished_at = new DateTime( $now );
    }

    /**
     * Set the job status to failed.
     *
     * @return void
     */
    public function fail( string $error_message = '' ): void {
        global $wpdb;
        $now = \current_time( 'mysql' );
        $wpdb->update(
            self::get_table_name(),
            array(
                'feedback'    => $error_message,
                'finished_at' => $now,
                'status'      => JobStatus::FAILED->value,
            ),
            array( 'id' => $this->id ),
        );
        $this->status      = JobStatus::FAILED;
        $this->finished_at = new DateTime( $now );
    }

    /**
     * Load the job from the database.
     *
     * @return void
     */
    protected function load(): void {
        global $wpdb;
        $job = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::get_table_name() . ' WHERE id = %d',
                $this->id,
            ),
        );

        if ( ! $job ) {
            throw new \Exception( "Job with ID {$this->id} not found" );
        }

        $this->status      = JobStatus::from( $job->status );
        $this->product_id  = $job->product_id;
        $this->run_id      = $job->run_id;
        $this->created_at  = new DateTime( $job->created_at );
        $this->started_at  = $job->started_at ? new DateTime( $job->started_at ) : null;
        $this->finished_at = $job->finished_at ? new DateTime( $job->finished_at ) : null;
    }
}
