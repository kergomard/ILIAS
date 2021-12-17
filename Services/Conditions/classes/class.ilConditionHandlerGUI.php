<?php declare(strict_types=1);
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * class ilConditionHandlerGUI
 * @author       Stefan Meyer <meyer@leifos.com>
 * @version      $Id$
 * This class is aggregated in folders, groups which have a parent course object
 * Since it is something like an interface, all varirables, methods have there own name space (names start with cci) to avoid collisions
 * @ilCtrl_Calls ilConditionHandlerGUI:
 */
class ilConditionHandlerGUI
{
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;
    protected ilTree $tree;
    protected ilAccessHandler $access;
    protected ilToolbarGUI $toolbar;
    protected ilConditionUtil $conditionUtil;
    protected ilObjectDefinition $objectDefinition;

    protected ilConditionHandler $ch_obj;
    protected ?ilObject $target_obj = null;
    protected int $target_id = 0;
    protected string $target_type = '';
    protected string $target_title = '';
    protected int $target_ref_id = 0;

    protected bool $automatic_validation = true;

    /**
     * Constructor
     */
    public function __construct(int $a_ref_id = null)
    {
        global $DIC;

        $this->ch_obj = new ilConditionHandler();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('rbac');
        $this->lng->loadLanguageModule('cond');
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tree = $DIC->repositoryTree();
        $this->access = $DIC->access();
        $this->toolbar = $DIC->toolbar();
        $this->conditionUtil = $DIC->conditions()->util();
        $this->objectDefinition = $DIC['objDefinition'];

        if ($a_ref_id) {
            $target_obj = ilObjectFactory::getInstanceByRefId($a_ref_id);
            $this->setTargetId($target_obj->getId());
            $this->setTargetRefId($target_obj->getRefId());
            $this->setTargetType($target_obj->getType());
            $this->setTargetTitle($target_obj->getTitle());
        }
    }

    /**
     * Translate operator
     */
    public static function translateOperator(int $a_obj_id, string $a_operator) : string
    {
        global $DIC;

        $lng = $DIC->language();
        switch ($a_operator) {
            case ilConditionHandler::OPERATOR_LP:
                $lng->loadLanguageModule('trac');

                $obj_settings = new ilLPObjSettings($a_obj_id);
                return ilLPObjSettings::_mode2Text($obj_settings->getMode());

            default:
                $lng->loadLanguageModule('rbac');
                return $lng->txt('condition_' . $a_operator);
        }
    }

    protected function getConditionHandler() : ilConditionHandler
    {
        return $this->ch_obj;
    }

    public function setBackButtons(array $a_btn_arr) : void
    {
        ilSession::set('precon_btn', $a_btn_arr);
    }

    public function getBackButtons() : array
    {
        if (ilSession::has('precon_btn')) {
            return ilSession::get('precon_btn');
        }
        return [];
    }

    public function executeCommand()
    {
        global $DIC;

        $ilErr = $DIC['ilErr'];

        if (!$this->access->checkAccess('write', '', $this->getTargetRefId())) {
            $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->WARNING);
        }

        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd();
        switch ($next_class) {
            default:
                if (empty($cmd)) {
                    $cmd = "view";
                }
                $this->$cmd();
                break;
        }
    }

    public function setAutomaticValidation(bool $a_status) : void
    {
        $this->automatic_validation = $a_status;
    }

    public function getAutomaticValidation() : bool
    {
        return $this->automatic_validation;
    }

    /**
     * set target id
     */
    public function setTargetId(int $a_target_id) : void
    {
        $this->target_id = $a_target_id;
    }

    /**
     * get target id
     */
    public function getTargetId() : int
    {
        return $this->target_id;
    }

    /**
     * set target ref id
     */
    public function setTargetRefId(int $a_target_ref_id) : void
    {
        $this->target_ref_id = $a_target_ref_id;
    }

    /**
     * get target ref id
     */
    public function getTargetRefId() : int
    {
        return $this->target_ref_id;
    }

    /**
     * set target type
     */
    public function setTargetType(string $a_target_type) : void
    {
        $this->target_type = $a_target_type;
    }

    /**
     * get target type
     */
    public function getTargetType() : string
    {
        return $this->target_type;
    }

    /**
     * set target title
     */
    public function setTargetTitle(string $a_target_title) : void
    {
        $this->target_title = $a_target_title;
    }

    /**
     * Check if target has refernce id
     * @return bool
     */
    public function isTargetReferenced() : bool
    {
        return (bool) $this->getTargetRefId();
    }

    /**
     * get target title
     */
    public function getTargetTitle() : string
    {
        return $this->target_title;
    }

    /**
     * list conditions
     */
    protected function listConditions() : void
    {
        // check if parent deals with conditions
        if (
            $this->getTargetRefId() > 0 &&
            $this->conditionUtil->isUnderParentControl($this->getTargetRefId())
        ) {
            ilUtil::sendInfo($this->lng->txt("cond_under_parent_control"));
            return;
        }

        $this->toolbar->addButton($this->lng->txt('add_condition'), $this->ctrl->getLinkTarget($this, 'selector'));
        $this->tpl->addBlockFile('ADM_CONTENT', 'adm_content', 'tpl.list_conditions.html', 'Services/AccessControl');

        $optional_conditions = ilConditionHandler::getPersistedOptionalConditionsOfTarget(
            $this->getTargetRefId(),
            $this->getTargetId(),
            $this->getTargetType()
        );
        if (count($optional_conditions)) {
            if (!$_REQUEST["list_mode"]) {
                $_REQUEST["list_mode"] = "subset";
            }
        } elseif (!$_REQUEST["list_mode"]) {
            $_REQUEST["list_mode"] = "all";
        }

        // Show form only if conditions are availabe
        if (count(ilConditionHandler::_getPersistedConditionsOfTarget(
            $this->getTargetRefId(),
            $this->getTargetId(),
            $this->getTargetType()
        ))
        ) {
            $form = $this->showObligatoryForm($optional_conditions);
            if ($form instanceof ilPropertyFormGUI) {
                $this->tpl->setVariable('TABLE_SETTINGS', $form->getHTML());
            }
        }

        $table = new ilConditionHandlerTableGUI($this, 'listConditions', ($_REQUEST["list_mode"] != "all"));
        $table->setConditions(
            ilConditionHandler::_getPersistedConditionsOfTarget(
                $this->getTargetRefId(),
                $this->getTargetId(),
                $this->getTargetType()
            )
        );

        $h = $table->getHTML();
        $this->tpl->setVariable('TABLE_CONDITIONS', $h);
    }

    /**
     * Save obligatory settings
     */
    protected function saveObligatorySettings() : void
    {
        $form = $this->showObligatoryForm();
        if ($form->checkInput()) {
            $old_mode = $form->getInput("old_list_mode");
            switch ($form->getInput("list_mode")) {
                case "all":
                    if ($old_mode != "all") {
                        $optional_conditions = ilConditionHandler::getPersistedOptionalConditionsOfTarget(
                            $this->getTargetRefId(),
                            $this->getTargetId(),
                            $this->getTargetType()
                        );
                        // Set all optional conditions to obligatory
                        foreach ($optional_conditions as $item) {
                            ilConditionHandler::updateObligatory($item["condition_id"], true);
                        }
                    }
                    break;

                case "subset":
                    $num_req = $form->getInput('required');
                    if ($old_mode != "subset") {
                        $all_conditions = ilConditionHandler::_getPersistedConditionsOfTarget(
                            $this->getTargetRefId(),
                            $this->getTargetId(),
                            $this->getTargetType()
                        );
                        foreach ($all_conditions as $item) {
                            ilConditionHandler::updateObligatory($item["condition_id"], false);
                        }
                    }
                    ilConditionHandler::saveNumberOfRequiredTriggers(
                        $this->getTargetRefId(),
                        $this->getTargetId(),
                        $num_req
                    );
                    break;
            }

            $cond = new ilConditionHandler();
            $cond->setTargetRefId($this->getTargetRefId());
            $cond->updateHiddenStatus((bool) $form->getInput('hidden'));

            ilUtil::sendSuccess($this->lng->txt('settings_saved'), true);
            $this->ctrl->redirect($this, 'listConditions');
        }

        $form->setValuesByPost();
        ilUtil::sendFailure($this->lng->txt('err_check_input'));
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Save obligatory settings
     */
    protected function saveObligatoryList() : void
    {
        $all_conditions = ilConditionHandler::_getPersistedConditionsOfTarget(
            $this->getTargetRefId(),
            $this->getTargetId(),
            $this->getTargetType()
        );

        if ($_POST["obl"] && sizeof($_POST["obl"]) > sizeof($all_conditions) - 2) {
            ilUtil::sendFailure($this->lng->txt("rbac_precondition_minimum_optional"), true);
            $this->ctrl->redirect($this, 'listConditions');
        }

        foreach ($all_conditions as $item) {
            $status = false;
            if ($_POST["obl"] && in_array($item["condition_id"], $_POST["obl"])) {
                $status = true;
            }
            ilConditionHandler::updateObligatory($item["condition_id"], $status);
        }

        // re-calculate
        ilConditionHandler::calculatePersistedRequiredTriggers(
            $this->getTargetRefId(),
            $this->getTargetId(),
            $this->getTargetType(),
            true
        );

        ilUtil::sendSuccess($this->lng->txt('settings_saved'), true);
        $this->ctrl->redirect($this, 'listConditions');
    }

    /**
     * Show obligatory form
     * @return ilPropertyFormGUI|null
     */
    protected function showObligatoryForm($opt = array()) : ?ilPropertyFormGUI
    {
        if (!$this->objectDefinition->isRbacObject($this->getTargetType())) {
            return null;
        }

        if (!$opt) {
            $opt = ilConditionHandler::getPersistedOptionalConditionsOfTarget(
                $this->getTargetRefId(),
                $this->getTargetId(),
                $this->getTargetType()
            );
        }
        $all = ilConditionHandler::_getPersistedConditionsOfTarget($this->getTargetRefId(), $this->getTargetId());
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this, 'listConditions'));
        $form->setTitle($this->lng->txt('precondition_obligatory_settings'));
        $form->addCommandButton('saveObligatorySettings', $this->lng->txt('save'));

        $hide = new ilCheckboxInputGUI($this->lng->txt('rbac_precondition_hide'), 'hidden');
        $hide->setChecked(ilConditionHandler::lookupPersistedHiddenStatusByTarget($this->getTargetRefId()));
        $hide->setValue("1");
        $hide->setInfo($this->lng->txt('rbac_precondition_hide_info'));
        $form->addItem($hide);

        $mode = new ilRadioGroupInputGUI($this->lng->txt("rbac_precondition_mode"), "list_mode");
        $form->addItem($mode);
        $mode->setValue($_REQUEST["list_mode"]);

        $mall = new ilRadioOption($this->lng->txt("rbac_precondition_mode_all"), "all");
        $mall->setInfo($this->lng->txt("rbac_precondition_mode_all_info"));
        $mode->addOption($mall);

        if (count($all) > 1) {
            $min = 1;
            $max = count($all) - 1;

            $msubset = new ilRadioOption($this->lng->txt("rbac_precondition_mode_subset"), "subset");
            $msubset->setInfo($this->lng->txt("rbac_precondition_mode_subset_info"));
            $mode->addOption($msubset);

            $obl = new ilNumberInputGUI($this->lng->txt('precondition_num_obligatory'), 'required');
            $obl->setInfo($this->lng->txt('precondition_num_optional_info'));

            $num_required = ilConditionHandler::lookupObligatoryConditionsOfTarget($this->getTargetRefId(),
                $this->getTargetId());
            $obl->setValue($num_required > 0 ? $num_required : null);
            $obl->setRequired(true);
            $obl->setSize(1);
            $obl->setMinValue($min);
            $obl->setMaxValue($max);
            $msubset->addSubItem($obl);
        }

        $old_mode = new ilHiddenInputGUI("old_list_mode");
        $old_mode->setValue($_REQUEST["list_mode"]);
        $form->addItem($old_mode);

        return $form;
    }

    public function edit() : void
    {
        global $DIC;

        $ilObjDataCache = $DIC['ilObjDataCache'];

        if (!$_GET['condition_id']) {
            ilUtil::sendFailure("Missing id: condition_id");
            $this->listConditions();
            return;
        }
        $condition = ilConditionHandler::_getCondition((int) $_GET['condition_id']);

        $this->tpl->addBlockfile(
            'ADM_CONTENT',
            'adm_content',
            'tpl.condition_handler_edit_condition.html',
            "Services/AccessControl"
        );
        $this->ctrl->setParameter($this, 'condition_id', (int) $_GET['condition_id']);

        $form = $this->initFormCondition($condition['trigger_ref_id'], (int) $_GET['condition_id'], 'edit');
        $this->tpl->setVariable('CONDITION_TABLE', $form->getHTML());
    }

    public function updateCondition() : void
    {
        if (!$_GET['condition_id']) {
            ilUtil::sendFailure("Missing id: condition_id");
            $this->listConditions();
            return;
        }

        // Update condition
        $condition_handler = new ilConditionHandler();
        $condition = ilConditionHandler::_getCondition((int) $_GET['condition_id']);
        $condition_handler->setOperator($_POST['operator']);
        $condition_handler->setObligatory((int) $_POST['obligatory']);
        $condition_handler->setTargetRefId($this->getTargetRefId());
        $condition_handler->setValue('');
        switch ($this->getTargetType()) {
            case 'st':
                $condition_handler->setReferenceHandlingType($_POST['ref_handling']);
                break;

            default:
                $condition_handler->setReferenceHandlingType(ilConditionHandler::UNIQUE_CONDITIONS);
                break;
        }
        $condition_handler->updateCondition($condition['id']);

        // Update relevant sco's
        if ($condition['trigger_type'] == 'sahs') {

            $olp = ilObjectLP::getInstance($condition['trigger_obj_id']);
            $collection = $olp->getCollectionInstance();
            if ($collection) {
                $collection->delete();
            }

            if (is_array($_POST['item_ids'])) { // #12901
                $collection->activateEntries($_POST['item_ids']);
            }
            ilLPStatusWrapper::_refreshStatus($condition['trigger_obj_id']);
        }
        ilUtil::sendSuccess($this->lng->txt('settings_saved'));
        $this->ctrl->redirect($this, 'listConditions');
    }

    public function askDelete() : void
    {
        if (!count($_POST['conditions'])) {
            ilUtil::sendFailure($this->lng->txt('no_condition_selected'));
            $this->listConditions();
            return;
        }

        // display confirmation message
        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($this->ctrl->getFormAction($this, "listConditions"));
        $cgui->setHeaderText($this->lng->txt("rbac_condition_delete_sure"));
        $cgui->setCancel($this->lng->txt("cancel"), "listConditions");
        $cgui->setConfirm($this->lng->txt("delete"), "delete");

        // list conditions that should be deleted
        foreach ($_POST['conditions'] as $condition_id) {
            $condition = ilConditionHandler::_getCondition($condition_id);

            $title = ilObject::_lookupTitle($condition['trigger_obj_id']) .
                " (" . $this->lng->txt("condition") . ": " .
                $this->lng->txt('condition_' . $condition['operator']) . ")";
            $icon = ilUtil::getImagePath('icon_' . $condition['trigger_type'] . '.svg');
            $alt = $this->lng->txt('obj_' . $condition['trigger_type']);

            $cgui->addItem("conditions[]", $condition_id, $title, $icon, $alt);
        }

        $this->tpl->setContent($cgui->getHTML());
    }

    public function delete() : void
    {
        if (!count($_POST['conditions'])) {
            ilUtil::sendFailure($this->lng->txt('no_condition_selected'));
            $this->listConditions();
            return;
        }

        foreach ($_POST['conditions'] as $condition_id) {
            $this->ch_obj->deleteCondition($condition_id);
        }
        ilUtil::sendSuccess($this->lng->txt('condition_deleted'), true);
        $this->ctrl->redirect($this, 'listConditions');
    }

    public function selector() : void
    {
        ilUtil::sendInfo($this->lng->txt("condition_select_object"));

        $exp = new ilConditionSelector($this, "selector");
        $exp->setTypeWhiteList(array_merge(
            $this->getConditionHandler()->getTriggerTypes(),
            array("root", "cat", "grp", "fold", "crs", "prg")
        ));
        //setRefId have to be after setTypeWhiteList!
        $exp->setRefId($this->getTargetRefId());
        $exp->setClickableTypes($this->getConditionHandler()->getTriggerTypes());

        if (!$exp->handleCommand()) {
            $this->tpl->setContent($exp->getHTML());
        }
    }

    public function add() : void
    {
        if (!$_GET['source_id']) {
            ilUtil::sendFailure("Missing id: condition_id");
            $this->selector();
            return;
        }
        $form = $this->initFormCondition((int) $_GET['source_id'], 0, 'add');
        $this->tpl->addBlockFile(
            'ADM_CONTENT',
            'adm_content',
            'tpl.condition_handler_add.html',
            "Services/AccessControl"
        );
        $this->tpl->setVariable('CONDITION_TABLE', $form->getHTML());
    }

    /**
     * assign new trigger condition to target
     */
    public function assign() : void
    {
        if (!isset($_GET['source_id'])) {
            echo "class.ilConditionHandlerGUI: no source_id given";

            return;
        }
        if (!$_POST['operator']) {
            ilUtil::sendFailure($this->lng->txt('err_check_input'));
            $this->add();
            return;
        }

        $this->ch_obj->setTargetRefId($this->getTargetRefId());
        $this->ch_obj->setTargetObjId($this->getTargetId());
        $this->ch_obj->setTargetType($this->getTargetType());

        switch ($this->getTargetType()) {
            case 'st':
                $this->ch_obj->setReferenceHandlingType($_POST['ref_handling']);
                break;

            default:
                $this->ch_obj->setReferenceHandlingType(ilConditionHandler::UNIQUE_CONDITIONS);
                break;
        }
        // this has to be changed, if non referenced trigger are implemted
        if (!$trigger_obj = ilObjectFactory::getInstanceByRefId((int) $_GET['source_id'], false)) {
            echo 'ilConditionHandler: Trigger object does not exist';
        }
        $this->ch_obj->setTriggerRefId($trigger_obj->getRefId());
        $this->ch_obj->setTriggerObjId($trigger_obj->getId());
        $this->ch_obj->setTriggerType($trigger_obj->getType());
        $this->ch_obj->setOperator($_POST['operator']);
        $this->ch_obj->setObligatory((int) $_POST['obligatory']);
        $this->ch_obj->setHiddenStatus(ilConditionHandler::lookupPersistedHiddenStatusByTarget($this->getTargetRefId()));
        $this->ch_obj->setValue('');

        // Save assigned sco's
        if ($this->ch_obj->getTriggerType() == 'sahs') {

            $olp = ilObjectLP::getInstance($this->ch_obj->getTriggerObjId());
            $collection = $olp->getCollectionInstance();
            if ($collection) {
                $collection->delete();
            }

            if (is_array($_POST['item_ids'])) { // #12901
                $collection->activateEntries($_POST['item_ids']);
            }
        }
        $this->ch_obj->enableAutomaticValidation($this->getAutomaticValidation());
        if (!$this->ch_obj->storeCondition()) {
            ilUtil::sendFailure($this->ch_obj->getErrorMessage(), true);
        } else {
            ilUtil::sendSuccess($this->lng->txt('added_new_condition'), true);
        }
        $this->ctrl->redirect($this, 'listConditions');
    }

    public function chi_update() : void
    {
        foreach ($this->__getConditionsOfTarget() as $condition) {
            $this->ch_obj->setOperator($_POST['operator'][$condition["id"]]);
            $this->ch_obj->setValue($_POST['value'][$condition["id"]]);
            $this->ch_obj->updateCondition($condition['id']);
        }
        ilUtil::sendSuccess($this->lng->txt('conditions_updated'));

        $this->ctrl->returnToParent($this);
    }

    public function __getConditionsOfTarget() : array
    {
        $cond = [];
        foreach (ilConditionHandler::_getPersistedConditionsOfTarget($this->getTargetRefId(), $this->getTargetId(),
            $this->getTargetType()) as $condition) {
            if ($condition['operator'] == 'not_member') {
                continue;
            } else {
                $cond[] = $condition;
            }
        }
        return $cond;
    }

    private function initFormCondition(
        int $a_source_id,
        int $a_condition_id = 0,
        string $a_mode = 'add'
    ) : ilPropertyFormGUI {
        $trigger_obj_id = ilObject::_lookupObjId($a_source_id);
        $trigger_type = ilObject::_lookupType($trigger_obj_id);

        $condition = ilConditionHandler::_getCondition($a_condition_id);
        $form = new ilPropertyFormGUI();
        $this->ctrl->setParameter($this, 'source_id', $a_source_id);
        $form->setFormAction($this->ctrl->getFormAction($this));

        $info_source = new ilNonEditableValueGUI($this->lng->txt("rbac_precondition_source"));
        $info_source->setValue(ilObject::_lookupTitle(ilObject::_lookupObjId($a_source_id)));
        $form->addItem($info_source);

        $info_target = new ilNonEditableValueGUI($this->lng->txt("rbac_precondition_target"));
        $info_target->setValue($this->getTargetTitle());
        $form->addItem($info_target);

        $obl = new ilHiddenInputGUI('obligatory');
        if ($a_condition_id) {
            $obl->setValue($condition['obligatory']);
        } else {
            $obl->setValue("1");
        }
        $form->addItem($obl);

        $sel = new ilSelectInputGUI($this->lng->txt('condition'), 'operator');
        $ch_obj = new ilConditionHandler();
        if ($a_mode == 'add') {
            $operators[0] = $this->lng->txt('select_one');
        }
        $operators = [];
        foreach ($ch_obj->getOperatorsByTriggerType($trigger_type) as $operator) {
            $operators[$operator] = $this->lng->txt('condition_' . $operator);
        }
        $sel->setValue($condition['operator'] ?? 0);
        $sel->setOptions($operators);
        $sel->setRequired(true);
        $form->addItem($sel);

        if (ilConditionHandler::_isReferenceHandlingOptional($this->getTargetType())) {
            $rad_opt = new ilRadioGroupInputGUI($this->lng->txt('cond_ref_handling'), 'ref_handling');
            $rad_opt->setValue($condition['ref_handling'] ?? ilConditionHandler::SHARED_CONDITIONS);

            $opt2 = new ilRadioOption($this->lng->txt('cond_ref_shared'),
                (string) ilConditionHandler::SHARED_CONDITIONS);
            $rad_opt->addOption($opt2);

            $opt1 = new ilRadioOption($this->lng->txt('cond_ref_unique'),
                (string) ilConditionHandler::UNIQUE_CONDITIONS);
            $rad_opt->addOption($opt1);

            $form->addItem($rad_opt);
        }

        // Additional settings for SCO's
        if ($trigger_type == 'sahs') {
            $this->lng->loadLanguageModule('trac');

            $cus = new ilCustomInputGUI($this->lng->txt('trac_sahs_relevant_items'), 'item_ids[]');
            $cus->setRequired(true);

            $tpl = new ilTemplate(
                'tpl.condition_handler_sco_row.html',
                true,
                true,
                "Services/AccessControl"
            );
            $counter = 0;

            $olp = ilObjectLP::getInstance($trigger_obj_id);
            $collection = $olp->getCollectionInstance();
            if ($collection) {
                foreach ($collection->getPossibleItems() as $item_id => $sahs_item) {
                    $tpl->setCurrentBlock("sco_row");
                    $tpl->setVariable('SCO_ID', $item_id);
                    $tpl->setVariable('SCO_TITLE', $sahs_item['title']);
                    $tpl->setVariable('CHECKED', $collection->isAssignedEntry($item_id) ? 'checked="checked"' : '');
                    $tpl->parseCurrentBlock();
                    $counter++;
                }
            }
            $tpl->setVariable('INFO_SEL', $this->lng->txt('trac_lp_determination_info_sco'));
            $cus->setHtml($tpl->get());
            $form->addItem($cus);
        }
        switch ($a_mode) {
            case 'edit':
                $form->setTitleIcon(ilUtil::getImagePath('icon_' . $this->getTargetType() . '.svg'));
                $form->setTitle($this->lng->txt('rbac_edit_condition'));
                $form->addCommandButton('updateCondition', $this->lng->txt('save'));
                $form->addCommandButton('listConditions', $this->lng->txt('cancel'));
                break;

            case 'add':
                $form->setTitleIcon(ilUtil::getImagePath('icon_' . $this->getTargetType() . '.svg'));
                $form->setTitle($this->lng->txt('add_condition'));
                $form->addCommandButton('assign', $this->lng->txt('save'));
                $form->addCommandButton('selector', $this->lng->txt('back'));
                break;
        }
        return $form;
    }
}
