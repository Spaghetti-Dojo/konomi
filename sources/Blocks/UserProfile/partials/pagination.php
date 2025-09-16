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

$perPage = 10;
$count = (int) ($data['count'] ?? null);
$pages = ceil($count / $perPage);

if ($perPage > $count) {
    return;
}

wp_interactivity_state('konomiProfilePagination', [
    'page' => 1,
    'perPage' => $perPage,
    'count' => $count,
    'pages' => $pages,
    'updateReason' => 'page-update',
]);
?>
    <nav
        role="navigation"
        aria-label="<?= esc_attr__('User Profile Pagination', 'konomi') ?>"
        data-wp-interactive="konomiProfilePagination"
        class="konomi-user-profile-pagination"
        data-wp-on-window--popstate="actions.updatePaginationByHistory"
        data-wp-watch--table-rows="callbacks.updateTableRows"
        data-wp-watch--pagination-links="callbacks.updatePaginationLinks"
        data-wp-watch--update-history="callbacks.updateHistory"
    >
        <ul>
            <?php
            for ($index = 1; $index <= $pages; ++$index) :
                $index = (string) $index;
                ?>
                <li>
                    <a
                        href="/page-<?= esc_attr($index) ?>"
                        data-wp-on--click="actions.updatePagination"
                    >
                        <?= esc_html($index) ?>
                    </a>
                </li>
            <?php endfor ?>
        </ul>
    </nav>
<?php
