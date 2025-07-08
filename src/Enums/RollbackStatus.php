<?php
namespace EPICWP\WC_Bulk_AI\Enums;

enum RollbackStatus: string {
    case UNAPPLIED = 'unapplied';
    case APPLIED   = 'applied';
}
