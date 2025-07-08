<?php
namespace EPICWP\WC_Bulk_AI\Models;

use DateTime;
use EPICWP\WC_Bulk_AI\Enums\RollbackStatus;

/**
 * Product rollback model.
 */
class Product_Rollback {
    public const TABLE_NAME = 'wcbai_product_rollbacks';

    /**
     * The job ID.
     *
     * @var int
     */
    protected int $job_id;

    /**
     * The property.
     *
     * @var string
     */
    protected string $property;

    /**
     * The previous value.
     *
     * @var string
     */
    protected string $previous_value;

    /**
     * The status. Applied or unapplied.
     *
     * @var RollbackStatus
     */
    protected RollbackStatus $status;

    /**
     * The creation date.
     *
     * @var DateTime
     */
    protected DateTime $created_at;

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
     * Create a new product rollback.
     *
     * @param int    $job_id The job ID.
     * @param string $property The property to rollback.
     * @param string $previous_value The previous value of the property.
     * @return ?Product_Rollback The product rollback object.
     */
    public static function create( int $job_id, string $property, string $previous_value ): ?Product_Rollback {
        if ( ! $previous_value ) {
            return null;
        }

        global $wpdb;
        $wpdb->insert(
            self::get_table_name(),
            array(
                'job_id'         => $job_id,
                'previous_value' => $previous_value,
                'property'       => $property,
                'status'         => RollbackStatus::UNAPPLIED->value,
            ),
        );
        return new self( $wpdb->insert_id );
    }

    /**
     * Get the product rollback by job ID and property.
     *
     * @param int    $job_id The job ID.
     * @param string $property The property.
     * @return ?Product_Rollback The product rollback object.
     */
    public static function get_by_job_id_and_property( int $job_id, string $property ): ?Product_Rollback {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::get_table_name() . ' WHERE job_id = %d AND property = %s AND status = %s',
                $job_id,
                $property,
                RollbackStatus::UNAPPLIED->value,
            ),
        );
        if ( ! $row ) {
            return null;
        }
        return new self( $row->id );
    }

    /**
     * Constructor.
     *
     * @param int $id The ID of the product rollback.
     */
    protected public function __construct( protected int $id ) {
    }

    /**
     * Load the product rollback from the database.
     */
    public function load(): void {
        global $wpdb;
        $row                  = $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM ' . self::get_table_name() . ' WHERE id = %d', $this->id ),
        );
        $this->job_id         = $row->job_id;
        $this->property       = $row->property;
        $this->previous_value = $row->previous_value;
        $this->created_at     = $row->created_at;
    }

    /**
     * Get the ID of the product rollback.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get the job ID of the product rollback.
     *
     * @return int
     */
    public function get_job_id(): int {
        return $this->job_id;
    }

    /**
     * Get the property of the product rollback.
     *
     * @return string
     */
    public function get_property(): string {
        return $this->property;
    }

    /**
     * Get the previous value of the product rollback.
     *
     * @return string
     */
    public function get_previous_value(): string {
        return $this->previous_value;
    }

    /**
     * Get the creation date of the product rollback.
     *
     * @return DateTime
     */
    public function get_created_at(): DateTime {
        return $this->created_at;
    }

    /**
     * Get the status of the product rollback.
     *
     * @return RollbackStatus
     */
    public function get_status(): RollbackStatus {
        return $this->status;
    }

    /**
     * Update the status of the product rollback.
     *
     * @param RollbackStatus $status The new status.
     * @return void
     */
    public function update_status( RollbackStatus $status ): void {
        $this->status = $status;
        global $wpdb;
        $wpdb->update(
            self::get_table_name(),
            array( 'status' => $status ),
            array( 'id' => $this->id ),
        );
    }

    /**
     * Mark the product rollback as applied.
     *
     * @return void
     */
    public function mark_as_applied(): void {
        $this->update_status( RollbackStatus::APPLIED );
    }
}
