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

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;

use ILIAS\Test\ExportImport\Factory as ExportImportFactory;
use ILIAS\Test\ExportImport\Types as ExportImportTypes;
use ILIAS\Test\RequestDataCollector;

/**
 * Class ilTestParticipantsGUI
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package components\ILIAS/Test
 *
 * @ilCtrl_Calls ilTestParticipantsGUI: ilTestParticipantsTableGUI
 * @ilCtrl_Calls ilTestParticipantsGUI: ilRepositorySearchGUI
 * @ilCtrl_Calls ilTestParticipantsGUI: ilTestEvaluationGUI
 */
class ilTestParticipantsGUI
{
    public const CMD_SHOW = 'show';
    public const CMD_SET_FILTER = 'setFilter';
    public const CMD_RESET_FILTER = 'resetFilter';
    public const CMD_SAVE_CLIENT_IP = 'saveClientIp';

    public const CALLBACK_ADD_PARTICIPANT = 'addParticipants';

    private const EXPORT_TYPE_PARAMETER = 'export_type';
    private const EXPORT_PLUGIN_TYPE_PARAMETER = 'export_plugin_type';

    protected ilTestObjectiveOrientedContainer $objective_parent;
    protected ilTestAccess $test_access;

    protected ilTestParticipantAccessFilterFactory $participant_access_filter;

    public function __construct(
        protected ilObjTest $test_obj,
        protected ilTestQuestionSetConfig $question_set_config,
        protected ilAccess $access,
        protected ilGlobalTemplateInterface $main_tpl,
        protected UIFactory $ui_factory,
        protected UIRenderer $ui_renderer,
        protected ilLanguage $lng,
        protected ilCtrlInterface $ctrl,
        protected ilDBInterface $db,
        protected ilTabsGUI $tabs,
        protected ilToolbarGUI $toolbar,
        protected ilComponentFactory $component_factory,
        protected ExportImportFactory $export_factory,
        protected RequestDataCollector $testrequest
    ) {
        $this->participant_access_filter = new ilTestParticipantAccessFilterFactory($access);
    }

    public function getTestObj(): ilObjTest
    {
        return $this->test_obj;
    }

    public function setTestObj(ilObjTest $test_obj): void
    {
        $this->test_obj = $test_obj;
    }

    public function getQuestionSetConfig(): ilTestQuestionSetConfig
    {
        return $this->question_set_config;
    }

    public function setQuestionSetConfig(ilTestQuestionSetConfig $question_set_config): void
    {
        $this->question_set_config = $question_set_config;
    }

    public function getObjectiveParent(): ilTestObjectiveOrientedContainer
    {
        return $this->objective_parent;
    }

    public function setObjectiveParent(ilTestObjectiveOrientedContainer $objective_parent): void
    {
        $this->objective_parent = $objective_parent;
    }

    public function getTestAccess(): ilTestAccess
    {
        return $this->test_access;
    }

    public function setTestAccess(ilTestAccess $test_access): void
    {
        $this->test_access = $test_access;
    }

    public function executeCommand(): void
    {
        switch ($this->ctrl->getNextClass($this)) {
            case 'ilrepositorysearchgui':
                $gui = new ilRepositorySearchGUI();
                $gui->setCallback($this, self::CALLBACK_ADD_PARTICIPANT, []);

                $gui->addUserAccessFilterCallable($this->participant_access_filter->getManageParticipantsUserFilter(
                    $this->getTestObj()->getRefId()
                ));


                $this->ctrl->setReturnByClass(self::class, self::CMD_SHOW);
                $this->ctrl->forwardCommand($gui);

                break;

            case "iltestevaluationgui":
                $gui = new ilTestEvaluationGUI($this->getTestObj());
                $gui->setObjectiveOrientedContainer($this->getObjectiveParent());
                $gui->setTestAccess($this->getTestAccess());
                $this->tabs->clearTargets();
                $this->tabs->clearSubTabs();

                $this->ctrl->forwardCommand($gui);

                break;

            default:

                $command = $this->ctrl->getCmd(self::CMD_SHOW) . 'Cmd';
                $this->{$command}();
        }
    }

    public function addParticipants($user_ids = []): ?bool
    {
        $filter_closure = $this->participant_access_filter->getManageParticipantsUserFilter($this->getTestObj()->getRefId());
        $filtered_user_ids = $filter_closure($user_ids);

        $countusers = 0;
        foreach ($filtered_user_ids as $user_id) {
            $client_ip = $_POST["client_ip"][$countusers] ?? '';
            $this->getTestObj()->inviteUser($user_id, $client_ip);
            $countusers++;
        }

        $message = "";
        if ($countusers) {
            $message = $this->lng->txt("tst_invited_selected_users");
        }
        if (strlen($message)) {
            $this->main_tpl->setOnScreenMessage('info', $message, true);
        } else {
            $this->main_tpl->setOnScreenMessage('info', $this->lng->txt("tst_invited_nobody"), true);
            return false;
        }

        $this->ctrl->redirect($this, self::CMD_SHOW);
        return null;
    }

    protected function buildTableGUI(): ilTestParticipantsTableGUI
    {
        $table_gui = new ilTestParticipantsTableGUI($this, self::CMD_SHOW, $this->ui_factory, $this->ui_renderer);

        $table_gui->setParticipantHasSolutionsFilterEnabled(
            $this->getTestObj()->getFixedParticipants()
        );

        if ($this->getTestObj()->getFixedParticipants()) {
            $table_gui->setTitle($this->lng->txt('tst_tbl_invited_users'));
        } else {
            $table_gui->setTitle($this->lng->txt('tst_tbl_participants'));
        }

        return $table_gui;
    }

    protected function setFilterCmd(): void
    {
        $table_gui = $this->buildTableGUI();
        $table_gui->initFilter($this->getTestObj()->getFixedParticipants());
        $table_gui->writeFilterToSession();
        $table_gui->resetOffset();
        $this->showCmd();
    }

    protected function resetFilterCmd(): void
    {
        $table_gui = $this->buildTableGUI();
        $table_gui->resetFilter();
        $table_gui->resetOffset();
        $this->showCmd();
    }

    public function showCmd(): void
    {
        $table_gui = $this->buildTableGUI();

        if ($this->getTestObj()->getFixedParticipants()) {
            $participant_list = $this->getTestObj()->getInvitedParticipantList()->getAccessFilteredList(
                $this->participant_access_filter->getManageParticipantsUserFilter($this->getTestObj()->getRefId())
            );

            $table_gui->setData($this->applyFilterCriteria($participant_list->getParticipantsTableRows()));
            $table_gui->setRowKeyDataField('usr_id');
            $table_gui->setManageInviteesCommandsEnabled(true);
            $table_gui->setDescription($this->lng->txt("fixed_participants_hint"));
        } else {
            $participant_list = $this->getTestObj()->getActiveParticipantList()->getAccessFilteredList(
                $this->participant_access_filter->getManageParticipantsUserFilter($this->getTestObj()->getRefId())
            );

            $table_gui->setData($participant_list->getParticipantsTableRows());
            $table_gui->setRowKeyDataField('active_id');
        }

        $table_gui->setManageResultsCommandsEnabled(true);

        $this->initToolbarControls($participant_list);

        $table_gui->setAnonymity($this->getTestObj()->getAnonymity());

        $table_gui->initColumns();
        $table_gui->initCommands();

        $table_gui->initFilter();
        $table_gui->setFilterCommand(self::CMD_SET_FILTER);
        $table_gui->setResetCommand(self::CMD_RESET_FILTER);

        $this->main_tpl->setContent($this->ctrl->getHTML($table_gui));
    }

    protected function applyFilterCriteria(array $in_rows): array
    {
        $selected_pax = ilSession::get('form_tst_participants_' . $this->getTestObj()->getRefId() . '_selection');

        if (!is_string($selected_pax)) {
            return $in_rows;
        }

        $filter = unserialize($selected_pax, ['allowed_classes' => false]);

        if (!is_string($filter) || $filter === 'all') {
            return $in_rows;
        }

        $rows = [];

        foreach ($in_rows as $row) {
            $query = $this->db->query(
                'SELECT count(solution_id) count
				FROM tst_solutions
				WHERE active_fi = ' . $this->db->quote($row['active_id'])
                . ' HAVING count ' . ($filter === 'withSolutions' ? '>' : '=') . ' 0'
            );

            if (is_array($this->db->fetchAssoc($query))) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    protected function initToolbarControls(ilTestParticipantList $participant_list): void
    {
        if ($this->getTestObj()->getFixedParticipants()) {
            $this->addUserSearchControls($this->toolbar);
        }

        if ($this->getTestObj()->getFixedParticipants() && $participant_list->hasUnfinishedPasses()) {
            $this->toolbar->addSeparator();
        }

        if ($participant_list->hasUnfinishedPasses()) {
            $this->addFinishAllPassesButton($this->toolbar);
        }

        if ($this->getTestObj()->evalTotalPersons() > 0) {
            $this->addExportDropdown($this->toolbar);
        }
    }

    protected function addUserSearchControls(ilToolbarGUI $toolbar): void
    {
        ilRepositorySearchGUI::fillAutoCompleteToolbar(
            $this,
            $toolbar,
            [
                'auto_complete_name' => $this->lng->txt('user'),
                'submit_name' => $this->lng->txt('add')
            ]
        );
        $toolbar->addSeparator();

        $search_btn = $this->ui_factory->button()->standard(
            $this->lng->txt('tst_search_users'),
            $this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI', 'start')
        );
        $toolbar->addComponent($search_btn);
    }

    protected function addFinishAllPassesButton(ilToolbarGUI $toolbar): void
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */

        $finish_all_user_passes_btn = $DIC->ui()->factory()->button()->standard(
            $DIC->language()->txt('finish_all_user_passes'),
            $DIC->ctrl()->getLinkTargetByClass('iltestevaluationgui', 'finishAllUserPasses')
        );
        $toolbar->addComponent($finish_all_user_passes_btn);
    }

    private function addExportDropdown(ilToolbarGUI $toolbar): void
    {
        $toolbar->setFormName('form_output_eval');
        $toolbar->setFormAction($this->ctrl->getFormActionByClass(self::class, 'exportEvaluation'));

        if ($this->getTestObj()->getAnonymity()) {
            $this->ctrl->setParameterByClass(self::class, self::EXPORT_TYPE_PARAMETER, 'all_test_runs_a');
            $options = [
                $this->ui_factory->button()->shy(
                    $this->lng->txt('exp_scored_test_run'),
                    $this->ctrl->getLinkTargetByClass(self::class, 'exportResults')
                )
            ];
        } else {
            $options = $this->buildOptionsForTestWithNames();
        }

        $options = $this->addPluginExportsToOptions($options);

        $this->ctrl->clearParameterByClass(self::class, 'export_type');
        $toolbar->addComponent(
            $this->ui_factory->dropdown()->standard($options)->withLabel($this->lng->txt('exp_eval_data'))
        );
    }

    /**
     * @return array<\ILIAS\UI\Component\Button\Shy>
     */
    private function buildOptionsForTestWithNames(): array
    {
        $this->ctrl->setParameterByClass(self::class, self::EXPORT_TYPE_PARAMETER, ExportImportTypes::SCORED_RUN->value);
        $options = [
            $this->ui_factory->button()->shy(
                $this->lng->txt('exp_scored_test_run'),
                $this->ctrl->getLinkTargetByClass(self::class, 'exportResults')
            )
        ];
        $this->ctrl->setParameterByClass(self::class, self::EXPORT_TYPE_PARAMETER, ExportImportTypes::ALL_RUNS->value);
        $options[] = $this->ui_factory->button()->shy(
            $this->lng->txt('exp_all_test_runs'),
            $this->ctrl->getLinkTargetByClass(self::class, 'exportResults')
        );
        return $this->addCertificateExportToOptions($options);
    }

    /**
     * @param array<\ILIAS\UI\Component\Button\Shy> $options
     * @return array<\ILIAS\UI\Component\Button\Shy>
     */
    private function addCertificateExportToOptions(array $options): array
    {
        try {
            if ((new ilCertificateActiveValidator())->validate()) {
                $this->ctrl->setParameterByClass(self::class, self::EXPORT_TYPE_PARAMETER, ExportImportTypes::CERTIFICATE_ARCHIVE->value);
                $options[] = $this->ui_factory->button()->shy(
                    $this->lng->txt('exp_grammar_as') . ' ' . $this->lng->txt('exp_type_certificate'),
                    $this->ctrl->getLinkTargetByClass(self::class, 'exportResults')
                );
            }
        } catch (ilException $e) {
        }
        return $options;
    }

    /**
     * @param array<\ILIAS\UI\Component\Button\Shy> $options
     * @return array<\ILIAS\UI\Component\Button\Shy>
     */
    private function addPluginExportsToOptions(array $options): array
    {
        foreach ($this->component_factory->getActivePluginsInSlot('texp') as $plugin) {
            $plugin->setTest($this->getTestObj());
            $this->ctrl->setParameterByClass(self::class, self::EXPORT_TYPE_PARAMETER, ExportImportTypes::PLUGIN->value);
            $this->ctrl->setParameterByClass(self::class, self::EXPORT_PLUGIN_TYPE_PARAMETER, $plugin->getFormat());
            $options[] = $this->ui_factory->button()->shy(
                $plugin->getFormatLabel(),
                $this->ctrl->getLinkTargetByClass(self::class, 'exportResults')
            );
        }
        $this->ctrl->clearParameterByClass(self::class, self::EXPORT_PLUGIN_TYPE_PARAMETER);
        return $options;
    }

    public function exportResultsCmd(): void
    {
        $export_type = ExportImportTypes::tryFrom(
            $this->testrequest->strVal(self::EXPORT_TYPE_PARAMETER)
        );

        if ($export_type === null) {
            $this->main_tpl->setOnScreenMessage('failure', $this->lng->txt('failure'));
            $this->showCmd();
            return;
        }

        $plugin_type = null;
        if ($export_type === ExportImportTypes::PLUGIN) {
            $plugin_type = $this->testrequest->strVal(self::EXPORT_PLUGIN_TYPE_PARAMETER);
        }

        $this->export_factory->getExporter(
            $this->getTestObj(),
            $export_type,
            $plugin_type
        )->deliver();
        $this->showCmd();
    }

    protected function saveClientIpCmd(): void
    {
        $filter_closure = $this->participant_access_filter->getManageParticipantsUserFilter($this->getTestObj()->getRefId());
        $selected_users = $filter_closure($this->testrequest->raw('chbUser') ?? []);

        if ($selected_users === []) {
            $this->main_tpl->setOnScreenMessage('info', $this->lng->txt("select_one_user"), true);
        }

        foreach ($selected_users as $user_id) {
            $this->getTestObj()->setClientIP($user_id, $_POST["clientip_" . $user_id]);
        }

        $this->ctrl->redirect($this, self::CMD_SHOW);
    }

    protected function removeParticipantsCmd(): void
    {
        $filter_closure = $this->participant_access_filter->getManageParticipantsUserFilter($this->getTestObj()->getRefId());
        $a_user_ids = $filter_closure((array) $_POST["chbUser"]);

        if (is_array($a_user_ids)) {
            foreach ($a_user_ids as $user_id) {
                $this->getTestObj()->disinviteUser($user_id);
            }
        } else {
            $this->main_tpl->setOnScreenMessage('info', $this->lng->txt("select_one_user"), true);
        }

        $this->ctrl->redirect($this, self::CMD_SHOW);
    }
}
