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

/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package     Modules/Test
 */
class ilTestProcessLockerDb extends ilTestProcessLocker
{
    /**
     * @var ilDBInterface
     */
    protected $db;

    /**
     * @var ilAtomQuery
     */
    protected $atom_query;

    /**
     * @param ilDBInterface $db
     */
    public function __construct(ilDBInterface $db)
    {
        $this->db = $db;
        $this->atom_query = $this->db->buildAtomQuery();
    }

    /**
     * {@inheritdoc}
     */
    protected function onBeforeExecutingTestStartOperation()
    {
        $this->atom_query->addTableLock('tst_active');
    }

    /**
     * {@inheritdoc}
     */
    protected function onBeforeExecutingRandomPassBuildOperation($withTaxonomyTables = false)
    {
        $this->atom_query->addTableLock('tst_rnd_cpy');
        $this->atom_query->addTableLock('qpl_questions');
        $this->atom_query->addTableLock('qpl_qst_type');
        $this->atom_query->addTableLock('tst_test_rnd_qst')->lockSequence(true);
        $this->atom_query->addTableLock('il_plugin');
        $this->atom_query->addTableLock('tst_active');

        if ($withTaxonomyTables) {
            $this->atom_query->addTableLock('tax_tree')->aliasName('s');
            $this->atom_query->addTableLock('tax_tree')->aliasName('t');
            $this->atom_query->addTableLock('tax_node_assignment');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function onBeforeExecutingTestFinishOperation()
    {
        $this->atom_query->addTableLock('tst_active');
    }

    /**
     * {@inheritdoc}
     */
    protected function executeOperation(callable $operation)
    {
        $this->atom_query ->addQueryCallable(function (ilDBInterface $ilDB) use ($operation) {
            $operation();
        });
        $this->atom_query->run();
    }

    protected function onBeforeExecutingNamedOperation(string $operationDescriptor): void
    {
        throw new RuntimeException('Operation not supported');
    }

    protected function onAfterExecutingNamedOperation(string $operationDescriptor): void
    {
        throw new RuntimeException('Operation not supported');
    }
}
