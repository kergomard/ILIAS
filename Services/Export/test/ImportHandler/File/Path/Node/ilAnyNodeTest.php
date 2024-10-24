<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace Test\ImportHandler\File\Path\Node;

use PHPUnit\Framework\TestCase;
use ILIAS\Export\ImportHandler\File\Path\Node\ilAnyNode as ilAnyNodeFilePathNode;

class ilAnyNodeTest extends TestCase
{
    protected function setUp(): void
    {

    }

    public function testAnyNode(): void
    {
        $node = new ilAnyNodeFilePathNode();
        $this->assertEquals('node()', $node->toString());
        $this->assertTrue($node->requiresPathSeparator());
    }
}
