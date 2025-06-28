<?php

declare(strict_types=1);

namespace SpaghettiDojo\Konomi\Tests\Functional\Blocks;

use SpaghettiDojo\Konomi\Tests\WpLoad;

WpLoad::load();

describe('Konomi', function (): void {
    it('Render the Block Markup', function (): void {
        $result = do_blocks(<<<MARKUP
<!-- wp:konomi/konomi -->
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
        <div class="wp-block-group">
            <!-- wp:konomi/reaction {"inactiveColor":"#cfcabe","activeColor":"#d8613c"} /-->
            <!-- wp:konomi/bookmark {"inactiveColor":"#cfcabe","activeColor":"#b1c5a4"} /-->
        </div>
    <!-- /wp:group -->
<!-- /wp:konomi/konomi -->
MARKUP
);
        expect($result)->toMatchSnapshot();
    });
});
