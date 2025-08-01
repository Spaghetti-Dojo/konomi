<?php

declare(strict_types=1);

/**
 * @var array{
 *     count?: positive-int,
 *     perPage?: positive-int,
 *     pages?: positive-int
 * } $data
 */
$data = (array) ($data ?? null);

$count = (int) ($data['count'] ?? null);
?>
    <nav
        role="navigation"
        aria-label="Pagination Navigation"
        class="konomi-user-profile-pagination"
    >
        <ul>
            <?php for ($index = 1; $index <= $count; ++$index) : ?>
                <li>
                    <a
                        href="/page-<?= $index ?>"
                        data-wp-on--click="actions.updatePagination"
                    >
                        <?= $index ?>
                    </a>
                </li>
            <?php endfor ?>
        </ul>
    </nav>
<?php
