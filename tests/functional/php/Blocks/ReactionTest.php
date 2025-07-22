<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Blocks;

describe('Reaction', function (): void {
    it('Render the Block Markup', function (): void {
        $this->signInUser('subscriber');
        $result = do_blocks('<!-- wp:konomi/reaction {"inactiveColor":"#000000","activeColor":"#ffffff"} -->');
        expect($result)->toMatchSnapshot();
    });
});
