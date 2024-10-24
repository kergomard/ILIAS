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

class ilTestTopList
{
    public function __construct(
        private ilObjTest $object,
        private ilDBInterface $db
    ) {
    }

    /**
     * @param int $a_test_ref_id
     * @param int $a_user_id
     * @return array
     */
    public function getUserToplistByWorkingtime(int $a_test_ref_id, int $a_user_id): array
    {
        $result = $this->db->query(
            '
			SELECT COUNT(tst_pass_result.workingtime) cnt
			FROM object_reference
			INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
			INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
			INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
			INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
				AND tst_pass_result.pass = tst_result_cache.pass
			INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
			AND tst_active.user_fi != ' . $this->db->quote($a_user_id, 'integer') . '
			AND workingtime <
			(
				SELECT workingtime
				FROM object_reference
				INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
				INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
				INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
				INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
					AND tst_pass_result.pass = tst_result_cache.pass
				WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
				AND tst_active.user_fi = ' . $this->db->quote($a_user_id, 'integer') . '
			)
		'
        );
        $row = $this->db->fetchAssoc($result);
        $better_participants = $row['cnt'];
        $own_placement = $better_participants + 1;

        $result = $this->db->query(
            '
			SELECT COUNT(tst_pass_result.workingtime) cnt
			FROM object_reference
			INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
			INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
			INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
			INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
				AND tst_pass_result.pass = tst_result_cache.pass
			INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer')
        );
        $row = $this->db->fetchAssoc($result);
        $number_total = $row['cnt'];

        $result = $this->db->query(
            '
		SELECT tst_result_cache.*, round(reached_points/max_points*100) as percentage ,
			tst_pass_result.workingtime, usr_id, usr_data.firstname, usr_data.lastname
		FROM object_reference
		INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
		INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
		INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
		INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
			AND tst_pass_result.pass = tst_result_cache.pass
		INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi

		WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
		AND tst_active.user_fi = ' . $this->db->quote($a_user_id, 'integer') . '

		UNION(
			SELECT tst_result_cache.*, round(reached_points/max_points*100) as percentage,
				tst_pass_result.workingtime, usr_id, usr_data.firstname, usr_data.lastname
			FROM object_reference
			INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
			INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
			INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
			INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
				AND tst_pass_result.pass = tst_result_cache.pass
			INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
			AND tst_active.user_fi != ' . $this->db->quote($a_user_id, 'integer') . '
			AND workingtime >=
			(
				SELECT tst_pass_result.workingtime
				FROM object_reference
				INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
				INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
				INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
				INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
					AND tst_pass_result.pass = tst_result_cache.pass
				WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
				AND tst_active.user_fi = ' . $this->db->quote($a_user_id, 'integer') . '
			)
			ORDER BY workingtime DESC
			LIMIT 0, ' . $this->db->quote($this->object->getHighscoreTopNum(), 'integer') . '
		)
		UNION(
			SELECT tst_result_cache.*, round(reached_points/max_points*100) as percentage,
				tst_pass_result.workingtime, usr_id, usr_data.firstname, usr_data.lastname
			FROM object_reference
			INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
			INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
			INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
			INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
				AND tst_pass_result.pass = tst_result_cache.pass
			INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
			AND tst_active.user_fi != ' . $this->db->quote($a_user_id, 'integer') . '
			AND workingtime <
			(
				SELECT tst_pass_result.workingtime
				FROM object_reference
				INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
				INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
				INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
				INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
					AND tst_pass_result.pass = tst_result_cache.pass
				WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
				AND tst_active.user_fi = ' . $this->db->quote($a_user_id, 'integer') . '
			)
			ORDER BY workingtime DESC
			LIMIT 0, ' . $this->db->quote($this->object->getHighscoreTopNum(), 'integer') . '
		)
		ORDER BY workingtime ASC
		LIMIT 0, ' . $this->db->quote($this->object->getHighscoreTopNum(), 'integer') . '
		'
        );

        $i = $own_placement - (($better_participants >= 3) ? 3 : $better_participants);

        $data = [];

        if ($i > 1) {
            $item = $this->buildEmptyItem();
            $data[] = $item;
        }

        while ($row = $this->db->fetchAssoc($result)) {
            $item = $this->getResultTableRow($row, $i, $a_user_id);
            $i++;
            $data[] = $item;
        }

        if ($number_total > $i) {
            $item = $this->buildEmptyItem();
            $data[] = $item;
        }

        return $data;
    }

    /**
     * @param int $a_test_ref_id
     * @param int $a_user_id
     * @return array
     */
    public function getGeneralToplistByPercentage(int $a_test_ref_id, int $a_user_id): array
    {
        $this->db->setLimit($this->object->getHighscoreTopNum(), 0);
        $result = $this->db->query(
            '
			SELECT tst_result_cache.*, round(points/maxpoints*100,2) as percentage, tst_pass_result.workingtime, usr_data.usr_id, usr_data.firstname, usr_data.lastname
			FROM object_reference
			INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
			INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
			INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
			INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi AND tst_pass_result.pass = tst_result_cache.pass
			INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
			ORDER BY percentage DESC'
        );

        $i = 0;
        $data = [];

        while ($row = $this->db->fetchAssoc($result)) {
            $i++;
            $item = $this->getResultTableRow($row, $i, $a_user_id);

            $data[] = $item;
        }

        return $data;
    }

    /**
     * @param int $a_test_ref_id
     * @param int $a_user_id
     * @return array
     */
    public function getGeneralToplistByWorkingtime(int $a_test_ref_id, int $a_user_id): array
    {
        $this->db->setLimit($this->object->getHighscoreTopNum(), 0);
        $result = $this->db->query(
            '
			SELECT tst_result_cache.*, round(points/maxpoints*100,2) as percentage, tst_pass_result.workingtime, usr_data.usr_id, usr_data.firstname, usr_data.lastname
			FROM object_reference
			INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
			INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
			INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
			INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi AND tst_pass_result.pass = tst_result_cache.pass
			INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
			ORDER BY workingtime ASC'
        );

        $i = 0;
        $data = [];

        while ($row = $this->db->fetchAssoc($result)) {
            $i++;
            $item = $this->getResultTableRow($row, $i, $a_user_id);
            $data[] = $item;
        }

        return $data;
    }

    /**
     * @param int $a_test_ref_id
     * @param int $a_user_id
     * @return array
     */
    public function getUserToplistByPercentage(int $a_test_ref_id, int $a_user_id): array
    {
        $result = $this->db->query(
            '
			SELECT COUNT(tst_pass_result.workingtime) cnt
			FROM object_reference
			INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
			INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
			INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
			INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
				AND tst_pass_result.pass = tst_result_cache.pass
			INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
			AND tst_active.user_fi != ' . $this->db->quote($a_user_id, 'integer') . '
			AND round(reached_points/max_points*100) >=
			(
				SELECT round(reached_points/max_points*100)
				FROM object_reference
				INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
				INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
				INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
				INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
					AND tst_pass_result.pass = tst_result_cache.pass
				WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
				AND tst_active.user_fi = ' . $this->db->quote($a_user_id, 'integer') . '
			)
		'
        );
        $row = $this->db->fetchAssoc($result);
        $better_participants = $row['cnt'];
        $own_placement = $better_participants + 1;

        $result = $this->db->query(
            '
			SELECT COUNT(tst_pass_result.workingtime) cnt
			FROM object_reference
			INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
			INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
			INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
			INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
				AND tst_pass_result.pass = tst_result_cache.pass
			INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer')
        );
        $row = $this->db->fetchAssoc($result);
        $number_total = $row['cnt'];

        $result = $this->db->query(
            '
		SELECT tst_result_cache.*, round(reached_points/max_points*100) as percentage ,
			tst_pass_result.workingtime, usr_id, usr_data.firstname, usr_data.lastname
		FROM object_reference
		INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
		INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
		INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
		INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
			AND tst_pass_result.pass = tst_result_cache.pass
		INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi

		WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
		AND tst_active.user_fi = ' . $this->db->quote($a_user_id, 'integer') . '

		UNION(
			SELECT tst_result_cache.*, round(reached_points/max_points*100) as percentage,
				tst_pass_result.workingtime, usr_id, usr_data.firstname, usr_data.lastname
			FROM object_reference
			INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
			INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
			INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
			INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
				AND tst_pass_result.pass = tst_result_cache.pass
			INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
			AND tst_active.user_fi != ' . $this->db->quote($a_user_id, 'integer') . '
			AND round(reached_points/max_points*100) >=
			(
				SELECT round(reached_points/max_points*100)
				FROM object_reference
				INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
				INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
				INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
				INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
					AND tst_pass_result.pass = tst_result_cache.pass
				WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
				AND tst_active.user_fi = ' . $this->db->quote($a_user_id, 'integer') . '
			)
			ORDER BY round(reached_points/max_points*100) ASC
			LIMIT 0, ' . $this->db->quote($this->object->getHighscoreTopNum(), 'integer') . '
		)
		UNION(
			SELECT tst_result_cache.*, round(reached_points/max_points*100) as percentage,
				tst_pass_result.workingtime, usr_id, usr_data.firstname, usr_data.lastname
			FROM object_reference
			INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
			INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
			INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
			INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
				AND tst_pass_result.pass = tst_result_cache.pass
			INNER JOIN usr_data ON usr_data.usr_id = tst_active.user_fi
			WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
			AND tst_active.user_fi != ' . $this->db->quote($a_user_id, 'integer') . '
			AND round(reached_points/max_points*100) <=
			(
				SELECT round(reached_points/max_points*100)
				FROM object_reference
				INNER JOIN tst_tests ON object_reference.obj_id = tst_tests.obj_fi
				INNER JOIN tst_active ON tst_tests.test_id = tst_active.test_fi
				INNER JOIN tst_result_cache ON tst_active.active_id = tst_result_cache.active_fi
				INNER JOIN tst_pass_result ON tst_active.active_id = tst_pass_result.active_fi
					AND tst_pass_result.pass = tst_result_cache.pass
				WHERE object_reference.ref_id = ' . $this->db->quote($a_test_ref_id, 'integer') . '
				AND tst_active.user_fi = ' . $this->db->quote($a_user_id, 'integer') . '
			)
			ORDER BY round(reached_points/max_points*100) ASC
			LIMIT 0, ' . $this->db->quote($this->object->getHighscoreTopNum(), 'integer') . '
		)
		ORDER BY round(reached_points/max_points*100) DESC, tstamp ASC
		LIMIT 0, ' . $this->db->quote($this->object->getHighscoreTopNum(), 'integer') . '
		'
        );

        $i = $own_placement - ($better_participants >= $this->object->getHighscoreTopNum()
            ? $this->object->getHighscoreTopNum() : $better_participants);

        $data = [];

        if ($i > 1) {
            $item = $this->buildEmptyItem();
            $data[] = $item;
        }

        while ($row = $this->db->fetchAssoc($result)) {
            $item = $this->getResultTableRow($row, $i, $a_user_id);
            $i++;
            $data[] = $item;
        }

        if ($number_total > $i) {
            $item = $this->buildEmptyItem();
            $data[] = $item;
        }

        return $data;
    }

    /**
     * @param array $row
     * @param int $i
     * @param int $usrId
     * @return array
     * @throws ilDateTimeException
     */
    private function getResultTableRow(array $row, int $i, int $usrId): array
    {
        $item = [];

        $item['rank'] = $i . '. ';

        if ($this->object->isHighscoreAnon() && (int) $row['usr_id'] !== $usrId) {
            $item['participant'] = '-, -';
        } else {
            $item['participant'] = $row['lastname'] . ', ' . $row['firstname'];
        }

        if ($this->object->getHighscoreAchievedTS()) {
            $item['achieved'] = new ilDateTime($row['tstamp'], IL_CAL_UNIX);
        }

        if ($this->object->getHighscoreScore()) {
            $item['score'] = $row['reached_points'] . ' / ' . $row['max_points'];
        }

        if ($this->object->getHighscorePercentage()) {
            $item['percentage'] = $row['percentage'] . '%';
        }

        if ($this->object->getHighscoreHints()) {
            $item['hints'] = $row['hint_count'];
        }

        if ($this->object->getHighscoreWTime()) {
            $item['time'] = $this->formatTime((int) $row['workingtime']);
        }

        $item['is_actor'] = ((int) $row['usr_id'] === $usrId);

        return $item;
    }

    /**
     * @param int $seconds
     * @return string
     */
    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $seconds -= $hours * 3600;
        $minutes = floor($seconds / 60);
        $seconds -= $minutes * 60;

        return str_pad(number_format($hours, 0, '.', ''), 2, '0', STR_PAD_LEFT) . ":"
            . str_pad(number_format($minutes, 0, '.', ''), 2, '0', STR_PAD_LEFT) . ":"
            . str_pad(number_format($seconds, 0, '.', ''), 2, '0', STR_PAD_LEFT);
    }

    private function buildEmptyItem(): array
    {
        return [
            'rank' => '...' ,
            'is_actor' => false,
            'participant' => '',
            'achieved' => '',
            'score' => '',
            'percentage' => '',
            'hints' => '',
            'time' => ''
        ];
    }
}
