<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

use SpaghettiDojo\Konomi\Storage;
use SpaghettiDojo\Konomi\Tests\Helpers;
use SpaghettiDojo\Konomi\User;

/**
 * @param array<int, string> $rows other-axis id => entity type
 */
function seedRecords(
    Helpers\InMemoryStorage $storage,
    int $id,
    User\ItemGroup $group,
    array $rows,
    Storage\Axis $axis = Storage\Axis::User
): void {

    $records = [];
    foreach ($rows as $otherId => $type) {
        $records[] = $axis === Storage\Axis::User
            ? new Storage\Record($otherId, $id, $type)
            : new Storage\Record($id, $otherId, $type);
    }
    $storage->seed($id, $group, $records);
}
