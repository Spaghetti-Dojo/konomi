<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Blocks;

describe('Bookmark', function (): void {
    it('Render the Block Markup', function (): void {
        $this->signInUser('subscriber');
        $result = do_blocks('<!-- wp:konomi/bookmark {"inactiveColor":"#000000","activeColor":"#ffffff"} -->');
        expect($result)->toMatchSnapshot();
    });
});
