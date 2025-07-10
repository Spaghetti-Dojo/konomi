<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Blocks;

use SpaghettiDojo\Konomi\Tests\WpLoad;

WpLoad::load();

describe('Bookmark', function (): void {
    it('Render the Block Markup', function (): void {
        $result = do_blocks('<!-- wp:konomi/bookmark {"inactiveColor":"#000000","activeColor":"#ffffff"} -->');
        expect($result)->toMatchSnapshot();
    });
});
