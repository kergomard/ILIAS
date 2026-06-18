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

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;

/**
 * The assErrorTextGUI class encapsulates the GUI representation for error text questions.
 *
 * @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
 * @author		Björn Heyser <bheyser@databay.de>
 * @author		Maximilian Becker <mbecker@databay.de>
 *
 * @version		$Id$
 *
 * @ingroup 	ModulesTestQuestionPool
 *
 * @ilctrl_iscalledby assErrorTextGUI: ilObjQuestionPoolGUI
 * @ilCtrl_Calls assErrorTextGUI: ilFormPropertyDispatchGUI
 */
class assErrorTextGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable, ilGuiAnswerScoringAdjustable, \ILIAS\UI\Component\Table\DataRetrieval
{
    private const DEFAULT_POINTS_WRONG = -1;

    private ilTabsGUI $tabs;
    private UIFactory $ui_factory;
    private UIRenderer $ui_renderer;

    public function __construct($id = -1)
    {
        global $DIC;
        $this->tabs = $DIC->tabs();
        $this->ui_factory = $DIC['ui.factory'];
        $this->ui_renderer = $DIC['ui.renderer'];

        parent::__construct();
        $this->object = new assErrorText();
        $this->setErrorMessage($this->lng->txt("msg_form_save_error"));
        if ($id >= 0) {
            $this->object->loadFromDb($id);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function writePostData(bool $always = false): int
    {
        $hasErrors = (!$always) ? $this->editQuestion(true) : false;
        if (!$hasErrors) {
            $this->writeQuestionGenericPostData();
            $this->writeQuestionSpecificPostData(new ilPropertyFormGUI());
            $this->writeAnswerSpecificPostData(new ilPropertyFormGUI());
            $this->saveTaxonomyAssignments();
            return 0;
        }
        return 1;
    }

    public function writeAnswerSpecificPostData(ilPropertyFormGUI $form): void
    {
        $data = $this->restructurePostDataForSaving($this->request_data_collector->raw('errordata') ?? []);
        $this->object->setErrorData($data);
        $this->object->removeErrorDataWithoutPosition();
    }

    private function restructurePostDataForSaving(array $post): array
    {
        $keys = $post['key'] ?? [];
        $restructured_array = [];
        foreach ($keys as $key => $text_wrong) {
            $restructured_array[] = new assAnswerErrorText(
                $text_wrong,
                $post['value'][$key],
                (float) str_replace(',', '.', $post['points'][$key])
            );
        }
        return $restructured_array;
    }

    public function writeQuestionSpecificPostData(ilPropertyFormGUI $form): void
    {
        $this->object->setQuestion(
            $this->request_data_collector->string('question')
        );

        $this->object->setErrorText(
            $this->request_data_collector->raw('errortext')
        );

        $this->object->parseErrorText();

        $this->object->setPointsWrong(
            $this->request_data_collector->float('points_wrong') ?? self::DEFAULT_POINTS_WRONG
        );

        if (!$this->object->getSelfAssessmentEditingMode()) {
            $this->object->setTextSize(
                $this->request_data_collector->float('textsize')
            );
        }
    }

    /**
    * Creates an output of the edit form for the question
    *
    * @access public
    */
    public function editQuestion(
        bool $checkonly = false,
        ?bool $is_save_cmd = null
    ): bool {
        /** @var ILIAS\DI\Container $DIC */
        global $DIC;
        $cmd = $DIC->http()->wrapper()->query()->retrieve(
            'sub_cmd',
            $DIC->refinery()->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always(null)
            ])
        );

        $is_edit = $DIC->http()->wrapper()->query()->retrieve(
            'edit',
            $DIC->refinery()->byTrying([
                $this->refinery->kindlyTo()->bool(),
                $this->refinery->always(false)
            ])
        );

        if ($cmd !== 'questionOverview') {
            $this->tabs_gui->clearTargets();
            if ($is_edit) {
                $this->ctrl->setParameterByClass(
                    self::class,
                    'sub_cmd',
                    $cmd === 'questionOverview'
                );
            }
            $this->tabs_gui->setBackTarget(
                'Cancel',
                $this->ctrl->getFormActionByClass(
                    $is_edit ? self::class : ilAssQuestionPreviewGUI::class,
                    $is_edit ? 'editQuestion' : 'show'
                )
            );
            $this->ctrl->clearParameterByClass(self::class, 'sub_cmd');
        }

        $this->ctrl->setParameterByClass(
            self::class,
            'question_type',
            $this->object->getQuestionType()
        );

        if ($cmd === null) {
            $content = $this->buildBasicForm($is_edit);
        } elseif ($cmd === 'answers') {
            $content = $this->buildAnswersForm();
        } elseif ($cmd === 'questionOverview') {
            $content = $this->buildQuestionOverview();
        }

        $this->getQuestionTemplate();
        $this->tpl->setVariable(
            'QUESTION_DATA',
            $this->ui_renderer->render($content)
        );
        return true;
    }

    private function buildBasicForm(bool $is_edit)
    {
        $ff = $this->ui_factory->input()->field();
        $this->ctrl->setParameterByClass(self::class, 'sub_cmd', 'answers');
        return $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormActionByClass(self::class, 'editQuestion'),
            [
                'error_text' => $ff->textarea($this->lng->txt('errortext'))
                    ->withRequired(true),
                'points_deduction' => $ff->numeric(
                    'Points Subtracted for Wrong Selections',
                    'Enter the points that will be subtracted for each selected word that is not in the list of marked errors.'
                )->withStepSize(0.0001)
                ->withRequired(true)
                ->withValue(1)
            ]
        )->withSubmitLabel($is_edit ? $this->lng->txt('save') : $this->lng->txt('next'));
    }

    private function buildAnswersForm()
    {
        $ff = $this->ui_factory->input()->field();
        $this->ctrl->setParameterByClass(self::class, 'sub_cmd', 'questionOverview');
        return [
            $this->ui_factory->panel()->standard(
                $this->lng->txt('errortext'),
                $this->ui_factory->legacy()->content(
                    "I'm an example and ((me contains)) ((erors))."
                )
            ),
            $this->ui_factory->input()->container()->form()->standard(
                $this->ctrl->getFormActionByClass(self::class, 'editQuestion'),
                [
                    'contains' => $ff->section(
                        [
                            'correct_text' => $ff->text(
                                'Correct Text'
                            )->withRequired(true),
                            'points' => $ff->numeric(
                                'Points',
                            )->withStepSize(0.0001)
                            ->withRequired(true)
                        ],
                        'me contains'
                    ),
                    'erors' => $ff->section(
                        [
                            'correct_text' => $ff->text(
                                'Correct Text'
                            )->withRequired(true),
                            'points' => $ff->numeric(
                                'Points',
                            )->withStepSize(0.0001)
                            ->withRequired(true)
                        ],
                        'erors'
                    )
                ]
            )->withAdditionalFormAction('', $this->lng->txt('previous'))
            ->withSubmitLabel($this->lng->txt('save'))
        ];
    }

    private function buildQuestionOverview()
    {
        /** @var ILIAS\DI\Container $DIC */
        global $DIC;
        [$url_builder, $token] = (new ILIAS\UI\URLBuilder(new ILIAS\Data\URI($DIC->http()->request()->getUri()->__toString())))
            ->acquireParameter(['table'], 'test');
        $this->ctrl->setParameterByClass(self::class, 'edit', '1');
        return [
            $this->ui_factory->panel()->standard(
                'Basic Answer Form Properties',
                [
                    $this->ui_factory->listing()->descriptive([
                        $this->lng->txt('errortext') => (new ilUIMarkdownPreviewGUI())->render(
                            "I'm an example and ((me contains)) ((erors))."
                        ),
                         'Points Subtracted for Wrong Selections' => '1'
                    ]),
                    $this->ui_factory->button()->standard(
                        'Edit Basic Answer Form Properties',
                        $this->ctrl->getFormActionByClass(self::class)
                    )
                ]
            ),
            $this->ui_factory->table()->data(
                $this,
                'Errors',
                [
                    'text' => $this->ui_factory->table()->column()->text('Text'),
                    'correct_text' => $this->ui_factory->table()->column()->text('Correct Text'),
                    'points' => $this->ui_factory->table()->column()->text('Available Points'),
                ]
            )->withActions([
                $this->ui_factory->table()->action()->standard(
                    'Edit',
                    $url_builder,
                    $token
                )
            ])->withRequest($DIC->http()->request())
        ];
    }

    public function getRows(\ILIAS\UI\Component\Table\DataRowBuilder $row_builder, array $visible_column_ids, \ILIAS\Data\Range $range, \ILIAS\Data\Order $order, mixed $additional_viewcontrol_data, mixed $filter_data, mixed $additional_parameters): \Generator
    {
        yield from [
             $row_builder->buildDataRow(
                 '6bd5c18f-653d-47e4-be95-1c9b6a2663e4',
                 [
                    'text' => 'me contains',
                    'correct_text' => 'I contain',
                    'points' => '2',
                ]
             ),
            $row_builder->buildDataRow(
                '0d439578-a36d-4eb2-8308-beb17ed381e3',
                [
                    'text' => 'erors',
                    'correct_text' => 'errors',
                    'points' => '1',
                ]
            )
        ];
    }

    public function getTotalRowCount(mixed $additional_viewcontrol_data, mixed $filter_data, mixed $additional_parameters): ?int
    {
        return 2;
    }

    /**
     * @param ilPropertyFormGUI $form
     * @return ilPropertyFormGUI
     */
    public function populateAnswerSpecificFormPart(ilPropertyFormGUI $form): ilPropertyFormGUI
    {
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->lng->txt("errors_section"));
        $form->addItem($header);

        $errordata = new ilErrorTextWizardInputGUI($this->lng->txt("errors"), "errordata");
        $errordata->setKeyName($this->lng->txt('text_wrong'));
        $errordata->setValueName($this->lng->txt('text_correct'));
        $errordata->setValues($this->object->getErrorData());
        $form->addItem($errordata);

        // points for wrong selection
        $points_wrong = new ilNumberInputGUI($this->lng->txt("points_wrong"), "points_wrong");
        $points_wrong->allowDecimals(true);
        $points_wrong->setMaxValue(0);
        $points_wrong->setMaxvalueShouldBeLess(true);
        $points_wrong->setValue($this->object->getPointsWrong());
        $points_wrong->setInfo($this->lng->txt("points_wrong_info"));
        $points_wrong->setSize(6);
        $points_wrong->setRequired(true);
        $form->addItem($points_wrong);
        return $form;
    }

    /**
     * @param $form ilPropertyFormGUI
     * @return ilPropertyFormGUI
     */
    public function populateQuestionSpecificFormPart(ilPropertyFormGUI $form): ilPropertyFormGUI
    {
        // errortext
        $errortext = new ilTextAreaInputGUI($this->lng->txt("errortext"), "errortext");
        $errortext->setValue($this->object->getErrorText());
        $errortext->setRequired(true);
        $errortext->setInfo($this->lng->txt("errortext_info"));
        $errortext->setRows(10);
        $errortext->setCols(80);
        $form->addItem($errortext);

        if (!$this->object->getSelfAssessmentEditingMode()) {
            // textsize
            $textsize = new ilNumberInputGUI($this->lng->txt("textsize"), "textsize");
            $textsize->setValue($this->object->getTextSize() ?? 100.0);
            $textsize->setInfo($this->lng->txt("textsize_errortext_info"));
            $textsize->setSize(6);
            $textsize->setSuffix("%");
            $textsize->setMinValue(10);
            $textsize->setRequired(true);
            $form->addItem($textsize);
        }
        return $form;
    }

    /**
    * Parse the error text
    */
    public function analyze(): void
    {
        $this->setAdditionalContentEditingModeFromPost();
        $this->writePostData(true);
        $this->saveTaxonomyAssignments();
        $this->object->setErrorsFromParsedErrorText();
        $this->tabs->activateTab('edit_question');
        $this->editQuestion();
    }

    public function getSolutionOutput(
        int $active_id,
        ?int $pass = null,
        bool $graphical_output = false,
        bool $result_output = false,
        bool $show_question_only = true,
        bool $show_feedback = false,
        bool $show_correct_solution = false,
        bool $show_manual_scoring = false,
        bool $show_question_text = true,
        bool $show_inline_feedback = true
    ): string {
        $user_solutions = $this->getUsersSolutionFromPreviewOrDatabase($active_id, $pass);
        return $this->renderSolutionOutput(
            $user_solutions,
            $active_id,
            $pass,
            $graphical_output,
            $result_output,
            $show_question_only,
            $show_feedback,
            $show_correct_solution,
            $show_manual_scoring,
            $show_question_text,
            false,
            $show_inline_feedback,
        );
    }

    public function renderSolutionOutput(
        mixed $user_solutions,
        int $active_id,
        ?int $pass,
        bool $graphical_output = false,
        bool $result_output = false,
        bool $show_question_only = true,
        bool $show_feedback = false,
        bool $show_correct_solution = false,
        bool $show_manual_scoring = false,
        bool $show_question_text = true,
        bool $show_autosave_title = false,
        bool $show_inline_feedback = false,
    ): ?string {
        $template = new ilTemplate("tpl.il_as_qpl_errortext_output_solution.html", true, true, "components/ILIAS/TestQuestionPool");

        $selections = [
            'user' => $user_solutions ?
                $user_solutions :
                $this->getUsersSolutionFromPreviewOrDatabase($active_id, $pass)
        ];
        $selections['best'] = $this->object->getBestSelection();

        $reached_points = $this->object->getPoints();
        if ($active_id > 0 && !$show_correct_solution) {
            $reached_points = $this->object->getReachedPoints($active_id, $pass);
        }

        if ($result_output === true) {
            $resulttext = ($reached_points == 1) ? "(%s " . $this->lng->txt("point") . ")" : "(%s " . $this->lng->txt("points") . ")";
            $template->setVariable("RESULT_OUTPUT", sprintf($resulttext, $reached_points));
        }

        if ($this->object->getTextSize() >= 10) {
            $template->setVariable("STYLE", " style=\"font-size: " . $this->object->getTextSize() . "%;\"");
        }

        if ($show_question_text === true) {
            $template->setVariable("QUESTIONTEXT", $this->renderLatex($this->object->getQuestionForHTMLOutput()));
        }

        $correctness_icons = [
            'correct' => $this->generateCorrectnessIconsForCorrectness(self::CORRECTNESS_OK),
            'not_correct' => $this->generateCorrectnessIconsForCorrectness(self::CORRECTNESS_NOT_OK)
        ];
        $errortext = $this->object->assembleErrorTextOutput($selections, $graphical_output, $show_correct_solution, false, $correctness_icons);

        $template->setVariable("ERRORTEXT", $errortext);
        $questionoutput = $template->get();

        $solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html", true, true, "components/ILIAS/TestQuestionPool");

        $feedback = '';
        if ($show_feedback) {
            if (!$this->isTestPresentationContext()) {
                $fb = $this->getGenericFeedbackOutput($active_id, $pass);
                $feedback .= mb_strlen($fb) ? $fb : '';
            }

            $fb = $this->getSpecificFeedbackOutput([]);
            $feedback .= mb_strlen($fb) ? $fb : '';
        }
        if (mb_strlen($feedback)) {
            $cssClass = (
                $this->hasCorrectSolution($active_id, $pass) ?
                ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_CORRECT : ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_WRONG
            );

            $solutiontemplate->setVariable("ILC_FB_CSS_CLASS", $cssClass);
            $solutiontemplate->setVariable("FEEDBACK", ilLegacyFormElementsUtil::prepareTextareaOutput($feedback, true));
        }

        $solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

        $solutionoutput = $solutiontemplate->get();
        if (!$show_question_only) {
            // get page object output
            $solutionoutput = $this->getILIASPage($solutionoutput);
        }
        return $solutionoutput;
    }

    public function getPreview(
        bool $show_question_only = false,
        bool $show_inline_feedback = false
    ): string {
        $selections = [
            'user' => $this->getUsersSolutionFromPreviewOrDatabase()
         ];

        return $this->generateQuestionOutput($selections, $show_question_only);
    }

    public function getTestOutput(
        int $active_id,
        int $pass,
        bool $is_question_postponed = false,
        array|bool $user_post_solutions = false,
        bool $show_specific_inline_feedback = false
    ): string {
        $selections = [
            'user' => $this->getUsersSolutionFromPreviewOrDatabase($active_id, $pass)
         ];

        return $this->outQuestionPage(
            '',
            $is_question_postponed,
            $active_id,
            $this->generateQuestionOutput($selections, true)
        );
    }

    private function generateQuestionOutput($selections, $show_question_only): string
    {
        $template = new ilTemplate("tpl.il_as_qpl_errortext_output.html", true, true, "components/ILIAS/TestQuestionPool");

        if ($this->object->getTextSize() >= 10) {
            $template->setVariable("STYLE", " style=\"font-size: " . $this->object->getTextSize() . "%;\"");
        }
        $template->setVariable("QUESTIONTEXT", $this->renderLatex($this->object->getQuestionForHTMLOutput()));
        $errortext = $this->object->assembleErrorTextOutput($selections);
        if ($this->getTargetGuiClass() !== null) {
            $this->ctrl->setParameterByClass($this->getTargetGuiClass(), 'errorvalue', '');
        }
        $template->setVariable("ERRORTEXT", $errortext);
        $template->setVariable("ERRORTEXT_ID", "qst_" . $this->object->getId());
        $template->setVariable("ERRORTEXT_VALUE", join(',', $selections['user']));

        $this->tpl->addOnLoadCode('il.test.player.errortext.init()');
        $this->tpl->addJavascript('assets/js/errortext.js');
        $questionoutput = $template->get();

        if ($show_question_only) {
            return $questionoutput;
        }

        return $this->getILIASPage($questionoutput);
    }

    private function getUsersSolutionFromPreviewOrDatabase(int $active_id = 0, ?int $pass = null): array
    {
        if (is_object($this->getPreviewSession())) {
            return (array) $this->getPreviewSession()->getParticipantsSolution();
        }

        if ($active_id > 0) {
            $selections = [];
            $solutions = $this->object->getSolutionValues($active_id, $pass ?? 0, true);
            foreach ($solutions as $solution) {
                $selections[] = $solution['value1'];
            }
            return $selections;
        }

        return [];
    }

    public function getSpecificFeedbackOutput(array $user_solution): string
    {
        if (!$this->object->feedbackOBJ->specificAnswerFeedbackExists()) {
            return '';
        }

        $feedback = '<table class="test_specific_feedback"><tbody>';
        $elements = $this->object->getErrorData();
        foreach ($elements as $index => $element) {
            $feedback .= '<tr>';
            $feedback .= '<td class="text-nowrap">' . $index . '. ' . $element->getTextWrong() . ':</td>';
            $feedback .= '<td>' . $this->object->feedbackOBJ->getSpecificAnswerFeedbackTestPresentation(
                $this->object->getId(),
                0,
                $index
            ) . '</td>';

            $feedback .= '</tr>';
        }
        $feedback .= '</tbody></table>';

        return $this->renderLatex(ilLegacyFormElementsUtil::prepareTextareaOutput($feedback, true));
    }

    /**
     * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
     * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
     * make sense in the given context.
     *
     * E.g. array('cloze_type', 'image_filename')
     *
     * @return string[]
     */
    public function getAfterParticipationSuppressionAnswerPostVars(): array
    {
        return [];
    }

    /**
     * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
     * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
     * make sense in the given context.
     *
     * E.g. array('cloze_type', 'image_filename')
     *
     * @return string[]
     */
    public function getAfterParticipationSuppressionQuestionPostVars(): array
    {
        return [];
    }

    /**
     * Returns an html string containing a question specific representation of the answers so far
     * given in the test for use in the right column in the scoring adjustment user interface.
     * @param array $relevant_answers
     * @return string
     */
    public function getAggregatedAnswersView(array $relevant_answers): string
    {
        $errortext = $this->object->getErrorText();

        $passdata = []; // Regroup answers into units of passes.
        foreach ($relevant_answers as $answer_chosen) {
            $passdata[$answer_chosen['active_fi'] . '-' . $answer_chosen['pass']][$answer_chosen['value2']][] = $answer_chosen['value1'];
        }

        $html = '';
        foreach ($passdata as $key => $pass) {
            $passdata[$key] = $this->object->createErrorTextOutput($pass);
            $html .= $passdata[$key] . '<hr /><br />';
        }

        return $html;
    }

    public function getAnswersFrequency($relevant_answers, $question_index): array
    {
        $answers_by_active_and_pass = [];

        foreach ($relevant_answers as $row) {
            $key = $row['active_fi'] . ':' . $row['pass'];

            if (!isset($answers_by_active_and_pass[$key])) {
                $answers_by_active_and_pass[$key] = ['user' => []];
            }

            $answers_by_active_and_pass[$key]['user'][] = $row['value1'];
        }

        $answers = [];

        foreach ($answers_by_active_and_pass as $answer) {
            $error_text = '<div class="errortext">' . $this->object->assembleErrorTextOutput($answer) . '</div>';
            $error_text_hashed = md5($error_text);

            if (!isset($answers[$error_text_hashed])) {
                $answers[$error_text_hashed] = [
                    'answer' => $error_text, 'frequency' => 0
                ];
            }

            $answers[$error_text_hashed]['frequency']++;
        }

        return array_values($answers);
    }

    public function populateCorrectionsFormProperties(ilPropertyFormGUI $form): void
    {
        $errordata = new ilAssErrorTextCorrectionsInputGUI($this->lng->txt('errors'), 'errordata');
        $errordata->setKeyName($this->lng->txt('text_wrong'));
        $errordata->setValueName($this->lng->txt('text_correct'));
        $errordata->setValues($this->object->getErrorData());
        $form->addItem($errordata);

        // points for wrong selection
        $points_wrong = new ilNumberInputGUI($this->lng->txt('points_wrong'), 'points_wrong');
        $points_wrong->allowDecimals(true);
        $points_wrong->setMaxValue(0);
        $points_wrong->setMaxvalueShouldBeLess(true);
        $points_wrong->setValue($this->object->getPointsWrong());
        $points_wrong->setInfo($this->lng->txt('points_wrong_info'));
        $points_wrong->setSize(6);
        $points_wrong->setRequired(true);
        $form->addItem($points_wrong);
    }

    /**
     * @param ilPropertyFormGUI $form
     */
    public function saveCorrectionsFormProperties(ilPropertyFormGUI $form): void
    {
        $existing_errordata = $this->object->getErrorData();
        $this->object->flushErrorData();
        $new_errordata = $this->request_data_collector->raw('errordata');
        $errordata = [];
        foreach ($new_errordata['points'] as $index => $points) {
            $errordata[$index] = $existing_errordata[$index]->withPoints(
                (float) str_replace(',', '.', $points)
            );
        }
        $this->object->setErrorData($errordata);
        $this->object->setPointsWrong((float) str_replace(',', '.', $form->getInput('points_wrong')));
    }
}
