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

namespace ILIAS\Test\Tests\ExportImport;

use ILIAS\Test\ExportImport\ExportFilename;

/**
 * @author Marvin Beym <mbeym@databay.de>
 */
class ExportFilenameTest extends \ilTestBaseTestCase
{
    private ExportFilename $test_obj;

    protected function setUp(): void
    {
        parent::setUp();

        $this->test_obj = new ExportFilename(0);
    }

    public function test_instantiateObject_shouldReturnInstance(): void
    {
        $this->assertInstanceOf(ExportFilename::class, $this->test_obj);
    }
}
