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

namespace ILIAS\UI\examples\MainControls\ModeInfo;

use ILIAS\DI\Container;

/**
 * ---
 * expected output: >
 *   ILIAS shows a button "See UI in fullscreen-mode".
 *   When clicked, a new page is shown with
 *   - only one entry in the mainbar
 *   - only the help-glyph in the metabar
 *   - very(!) little content
 *   - and a colored frame around the entire page.
 *   On the top of the frame, there is colored area "Active Mode Info"
 *   with a close-glyph. Clicking the close-glyph will return to the
 *   UI documentation.
 * ---
 */
function edit_question(): string
{
    /** @var \ILIAS\DI\Container $DIC */
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $icon = $f->symbol()->icon()->standard('root', '')->withSize('large');
    $target = new \ILIAS\Data\URI(
        $DIC->http()->request()->getUri()->__toString() . '&new_mode_info=' . QUESTION_EDIT_ACTIVE
    );
    return $renderer->render([
        $f->divider()->horizontal(),
        $f->link()->bulky($icon, 'See UI in fullscreen-mode', $target),
        $f->legacy()->content('<p><b>press the link above to init a page with Mode Info</b></p><p><br/></p>'),
        $f->divider()->horizontal()
    ]);
}

const QUESTION_EDIT_ACTIVE = 2;
const QUESTION_EDIT_INACTIVE = 1;

global $DIC;
$request_wrapper = $DIC->http()->wrapper()->query();
$refinery = $DIC->refinery();

if ($request_wrapper->has('new_mode_info')
    && $request_wrapper->retrieve('new_mode_info', $refinery->kindlyTo()->int()) === QUESTION_EDIT_ACTIVE
) {
    \ilInitialisation::initILIAS();
    echo(renderQuestionEdit($DIC));
    exit();
}

function renderQuestionEdit(\ILIAS\DI\Container $dic)
{
    $cmd = $dic->http()->wrapper()->query()->retrieve(
        'sub_cmd',
        $dic->refinery()->byTrying([
            $dic->refinery()->kindlyTo()->string(),
            $dic->refinery()->always('')
        ])
    );
    $edit = $cmd === 'editQuestion';
    $create = $cmd === 'createQuestion';
    $f = $dic->ui()->factory();
    $ff = $f->input()->field();
    $data_factory = new \ILIAS\Data\Factory();
    $renderer = $dic->ui()->renderer();
    $dic->ui()->mainTemplate()->addCss('components/ILIAS/COPage/css/content.css');

    $panel_content = $f->legacy()->content("Mode Info is Active");
    $question_list = $f->mainControls()->slate()->legacy(
        "List of Questions",
        $f->symbol()->icon()->standard('', '')->withAbbreviation('QL'),
        $f->legacy()->content(
            $renderer->render(
                $f->panel()->secondary()->listing(
                    'List of Questions',
                    [$f->item()->group(
                        '',
                        [
                            $f->item()->standard($f->link()->standard('My First Question', '#')),
                            $f->item()->standard($f->link()->standard('My Second Question', '#')),
                            $f->item()->standard($f->link()->standard('My Third Question', '#')),
                            $f->item()->standard($f->link()->standard('My Fourth Question', '#'))
                        ]
                    )]
                )
            )
        )
    );

    $main_controls = $f->mainControls()->mainBar();
    if ($edit) {
        $tools = $f->mainControls()->slate()->legacy(
            "Editor",
            $f->symbol()->icon()->standard('editing', ''),
            $f->legacy()->content(
                <<<HTML
<div id="copg-editor-slate-error"></div>
<div id="copg-editor-slate-content">
	<div id="iltinymenu">
		<h2 class="ilHeader">Edit Text</h2>
		<div style="position:relative;">
			<div id="iltinymenu_bd">
				<div id="ilTinyMenuButtons">
					<div class="ilFloatLeft">
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3d583_84031711" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="save.return">Finish Text Editing</button>
					</div>
					<div class="ilFloatRight">
						<img style="display:none;" data-copg-ed-type="top-loader" src="./assets/images/media/loader.svg" alt="">
					</div>
					<div class="ilClearFloat"></div>
				</div>
				<p id="copg-auto-save" class="subtitle">&nbsp;</p>
				<div class="ilTinyMenuSection ilTinyParagraphClassSelector">
					<h3 class="ilTinyInfo">Paragraph</h3>
					<div class="dropdown" id="il_ui_fw_68a86b03d35400_82131735">
						<button class="btn btn-default dropdown-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="il_ui_fw_68a86b03d35400_82131735_menu" aria-label="Paragraph Format Selection: Standard">Standard <span class="caret"></span></button>
						<ul id="il_ui_fw_68a86b03d35400_82131735_menu" class="dropdown-menu">
							<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d32f45_67754500" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="par.class" data-copg-ed-par-class="Standard"><div class="ilCOPgEditStyleSelectionItem"><p class="ilc_text_block_Standard" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Standard</p></div></button></li>
							<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d33388_08884232" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="par.class" data-copg-ed-par-class="Headline1"><div class="ilCOPgEditStyleSelectionItem"><h1 class="ilc_heading1_Headline1" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Headline 1</h1></div></button></li>
							<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d33754_25215205" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="par.class" data-copg-ed-par-class="Headline2"><div class="ilCOPgEditStyleSelectionItem"><h2 class="ilc_heading2_Headline2" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Headline 2</h2></div></button></li>
							<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d33b29_69943526" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="par.class" data-copg-ed-par-class="Headline3"><div class="ilCOPgEditStyleSelectionItem"><h3 class="ilc_heading3_Headline3" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Headline 3</h3></div></button></li>
							<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d33f72_53562154" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="par.class" data-copg-ed-par-class="Book"><div class="ilCOPgEditStyleSelectionItem"><p class="ilc_text_block_Book" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Book</p></div></button></li>
							<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d34422_96874324" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="par.class" data-copg-ed-par-class="Numbers"><div class="ilCOPgEditStyleSelectionItem"><p class="ilc_text_block_Numbers" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Numbers</p></div></button></li>
							<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d34813_58688535" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="par.class" data-copg-ed-par-class="Verse"><div class="ilCOPgEditStyleSelectionItem"><p class="ilc_text_block_Verse" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Verse/Stanza</p></div></button></li>
							<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d34ce8_71255958" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="par.class" data-copg-ed-par-class="List"><div class="ilCOPgEditStyleSelectionItem"><p class="ilc_text_block_List" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">List</p></div></button></li>
							<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d35163_57997882" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="par.class" data-copg-ed-par-class="TableContent"><div class="ilCOPgEditStyleSelectionItem"><p class="ilc_text_block_TableContent" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Table Content</p></div></button></li>
						</ul>
					</div>
				</div>
				<div class="ilClearFloat"></div>
					<div class="ilTinyMenuSection">
						<h3 class="ilTinyInfo">Character</h3>
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2ae98_97063486" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" aria-label="Strongly Emphasised" data-copg-ed-par-format="Strong"><span class="ilc_text_inline_Strong">str</span></button>
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2b554_02605262" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" aria-label="Emphasised" data-copg-ed-par-format="Emph"><span class="ilc_text_inline_Emph">emp</span></button>
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2baa2_72433038" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" aria-label="Important" data-copg-ed-par-format="Important"><span class="ilc_text_inline_Important">imp</span></button>
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2bfb0_35121728" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" aria-label="Superscript" data-copg-ed-par-format="Sup">x<sup>2</sup></button>
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2c4e2_78823320" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" aria-label="Subscript" data-copg-ed-par-format="Sub">x<sub>2</sub></button>
						<div class="dropdown" id="il_ui_fw_68a86b03d2e7d0_24405817"><button class="btn btn-default dropdown-toggle" type="button" aria-label="More Styles for Characters" aria-haspopup="true" aria-expanded="false" aria-controls="il_ui_fw_68a86b03d2e7d0_24405817_menu"><i>A</i><span class="caret"></span></button>
							<ul id="il_ui_fw_68a86b03d2e7d0_24405817_menu" class="dropdown-menu">
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2d230_00762559" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" data-copg-ed-par-format="Comment"><span class="ilc_text_inline_Comment" style="font-size:90%; margin-top:2px; margin-bottom:2px; position:static;">Comment</span></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2d707_49384243" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" data-copg-ed-par-format="Quotation"><span class="ilc_text_inline_Quotation" style="font-size:90%; margin-top:2px; margin-bottom:2px; position:static;">Quotation</span></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2dad9_60969982" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" data-copg-ed-par-format="Accent"><span class="ilc_text_inline_Accent" style="font-size:90%; margin-top:2px; margin-bottom:2px; position:static;">Accentuated</span></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2de50_22220925" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" data-copg-ed-par-format="Code"><code class="ilc_text_inline_Code" style="font-size:90%; margin-top:2px; margin-bottom:2px; position:static;">Code</code></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2e208_91202699" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" data-copg-ed-par-format="Mnemonic"><span class="ilc_text_inline_Mnemonic" style="font-size:90%; margin-top:2px; margin-bottom:2px; position:static;">Mnemonic</span></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2e5a5_84972121" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.format" data-copg-ed-par-format="Attention"><span class="ilc_text_inline_Attention" style="font-size:90%; margin-top:2px; margin-bottom:2px; position:static;">Attention</span></button></li>
							</ul>
						</div>
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2ed41_52871731" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="selection.removeFormat" aria-label="Remove Formatting"><i><strong><u>T</u></strong><sub>x</sub></i></button>
					</div>
					<div class="ilTinyMenuSection">
						<h3 class="ilTinyInfo">Lists</h3>
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2f393_53319910" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="list.bullet" aria-label="Bullet List">
							<a class="glyph" aria-label="Insert Bulletpoint-List - Click to insert a bulletpoint-list.">
								<span class="glyphicon glyphicon-bulletlist" aria-hidden="true"></span>
							</a>
						</button>
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2f982_32114376" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="list.number" aria-label="Numbered List">
							<a class="glyph" aria-label="Insert Numbered List - Click to insert a numbered list.">
								<span class="glyphicon glyphicon-numberedlist" aria-hidden="true"></span>
							</a>
						</button>
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d2ffa5_60043757" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="list.outdent" aria-label="Outdent List" disabled="">
							<a class="glyph" aria-label="-listoutdent-">
								<span class="glyphicon glyphicon-listoutdent" aria-hidden="true"></span>
							</a>
						</button>
						<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d30613_66404574" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="list.indent" aria-label="Indent List" disabled="">
							<a class="glyph" aria-label="-listindent-">
								<span class="glyphicon glyphicon-listindent" aria-hidden="true"></span>
							</a>
						</button>
					</div>
					<div class="ilTinyMenuSection">
						<h3 class="ilTinyInfo">More</h3>
						<div class="dropdown" id="il_ui_fw_68a86b03d31002_34234017">
							<button class="btn btn-default dropdown-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="il_ui_fw_68a86b03d31002_34234017_menu">Link<i class="mce-ico mce-i-link"></i><span class="caret"></span></button>
							<ul id="il_ui_fw_68a86b03d31002_34234017_menu" class="dropdown-menu">
								<li>
									<button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d30de0_50799986" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="link.external">External Link</button>
								</li>
							</ul>
						</div>
					</div>
					<div class="ilClearFloat"></div>
					<div class="ilTinyMenuSection ilSectionClassSelector">
						<h3 class="ilTinyInfo">Surrounding Section</h3>
						<div class="dropdown" id="il_ui_fw_68a86b03d3ce30_81221618"><button class="btn btn-default dropdown-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="il_ui_fw_68a86b03d3ce30_81221618_menu">No Section <span class="caret"></span></button>
							<ul id="il_ui_fw_68a86b03d3ce30_81221618_menu" class="dropdown-menu">
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d383d2_36543134" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class=""><div class="ilCOPgEditStyleSelectionItem"><div class="" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">None</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d38b43_32745688" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Block"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Block" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Block</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d39060_99379097" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Mnemonic"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Mnemonic" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Mnemonic</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d394e6_36379696" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Remark"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Remark" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Remark</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d398d1_43256493" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Example"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Example" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Example</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d39c43_05985901" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Additional"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Additional" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Additional Information</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d39fb2_85098933" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Special"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Special" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Special</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3a311_48222780" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Attention"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Attention" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Attention</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3a671_65780498" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Background"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Background" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Background</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3a9e2_59067142" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Citation"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Citation" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Citation</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3ad57_26768502" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Confirmation"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Confirmation" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Confirmation</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3b0c6_21793974" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Information"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Information" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Information</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3b420_95334416" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Interaction"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Interaction" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Interaction</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3b792_98592547" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Link"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Link" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Link</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3bb07_88749309" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Literature"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Literature" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Literature</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3be96_52951474" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Separator"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Separator" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Separator</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3c253_93213323" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="StandardCenter"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_StandardCenter" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Standard Center</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3c6b7_93581498" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="Excursus"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_Excursus" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Excursus</div></div></button></li>
								<li><button class="btn btn-default" data-action="" id="il_ui_fw_68a86b03d3cbc6_94852853" data-copg-ed-type="par-action" data-copg-ed-component="" data-copg-ed-action="sec.class" data-copg-ed-par-class="AdvancedKnowledge"><div class="ilCOPgEditStyleSelectionItem"><div class="ilc_section_AdvancedKnowledge" style="margin-top:2px; margin-bottom:2px; text-indent:0px; position:static; float:none; width: auto;">Advanced Knowledge</div></div></button></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
HTML
            )
        );
        $main_controls = $main_controls->withToolsButton($f->button()->bulky($f->symbol()->glyph()->tileView(), 'Tools', ''))
            ->withAdditionalToolEntry('editor', $tools);
    }

    $question_create = $f->button()->bulky(
        $f->symbol()->icon()->standard('', '')->withAbbreviation('CQ'),
        "Create Question",
        $dic->http()->request()->getUri()->__toString() . '&sub_cmd=createQuestion'
    );

    $question_title = '';
    if (!$create) {
        $question_title = ': My First Question';
    }

    $header = "<div id='mainspacekeeper'><div style='padding: 15px;'><div class='media il_HeaderInner'><img id='headerimage' class='media-object' src='./assets/images/standard/icon_qpl.svg' alt='' title=''><h1 class='il-page-content-header media-heading ilHeader '>My Question Pool{$question_title}</h1><div class='media-body'><div class='ilHeaderDesc'></div></div></div>";
    if (!$edit && !$create) {
        $header .= "<ul id='ilTab' class='nav ilCollapsable hidden-print'><li id='tab_question' class='active'><a href='#'>Question</a> <span class='ilAccHidden'>(Selected)</span></li><li id='tab_statistics' class=''><a href='#'>Statistics</a></li></ul>";
    }

    $content = <<<HTML
<form name="ilAssQuestionPreview" action="ilias.php?baseClass=ilrepositorygui&amp;cmdNode=wt:oz:4h&amp;cmdClass=ilAssQuestionPreviewGUI&amp;cmd=post&amp;fallbackCmd=show&amp;ref_id=540&amp;q_id=5982&amp;rtoken=45160b5bae17f4611a15a5870ad4f12bbdabf8943dd4d4e4376649f2b30acfce#focus" method="post" enctype="multipart/form-data">
    <a class="small" id="ilPageShowAdvContent" style="display:none; text-align:right;" href="#"><span>Show Advanced Knowledge</span><span>Hide Advanced Knowledge</span></a><h1 class="ilc_page_title_PageTitle">My First Question</h1><!--COPage-PageTop--><!--ilPageTocH31--><h3 id="ilPageTocA31" class="ilc_Paragraph ilc_heading3_Headline3"><!--PageTocPH-->Richtig ist richtig, falsch ist&nbsp; falsch<!--Break--></h3><div class="ilc_question_Standard">	 <div class="ilc_question_SingleChoice">
        <div class="ilc_answers answers answer-table ilClearFloat">
            <div class="ilc_qanswer_Answer">
                <div>
                    <input type="radio" name="multiple_choice_result5982ID" value="0" id="answer_0">
                </div>
                <div>
                    <label for="answer_0" class="answertext">
                        Richtig
                    </label>
                </div>
            </div>
            <div class="ilc_qanswer_Answer">
                <div>
                    <input type="radio" name="multiple_choice_result5982ID" value="1" id="answer_1">
                </div>
                <div>
                    <label for="answer_1" class="answertext">
                        Falsch
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="ilAssQuestionRelatedNavigationContainer">
        <input type="submit" class="ilc_qsubmit_Submit btn btn-default" name="cmd[instantResponse]" id="directfeedback" value="Check">
    </div>
        </div><div style="clear:both;"><!--Break--></div><script></script><div class="il-copg-mob-fullscreen-modal"><dialog class="c-modal il-modal-roundtrip" tabindex="-1" id="il_ui_fw_68a5dcbe133dd1_34713471">
        <div class="modal-dialog" role="document" data-replace-marker="component">
            <div class="modal-content">
                <div class="modal-header">
                    <button formmethod="dialog" class="close" aria-label="Close"><span aria-hidden="true">×</span></button>
                    <h1 class="modal-title">Full Screen</h1>
                </div>
                <div class="modal-body">
                    <iframe class="il-copg-mob-fullscreen" id="il-copg-mob-fullscreen-qpl-5982"></iframe>
                </div>
                <div class="modal-footer">
                    <form>
                        <button formmethod="dialog" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </dialog>
</div></form>
HTML;
    if ($edit) {
        $content = <<<HTML
        <div id="il_EditPage" class="copg-state-page">
<form action="ilias.php?baseClass=ilrepositorygui&amp;cmdNode=wt:oz:4g:uz&amp;cmdClass=ilpageeditorgui&amp;cmd=post&amp;ref_id=540&amp;q_id=5982&amp;rtoken=0ff1ba4f7f6a418d1566b373e814902d5a146cbdfd4c9c66463d97ec9444db52" name="objectItems" method="post">
<div class="ilc_page_cont_PageContainer" id="il-edit-cont">
<div class="ilc_page_Page">
<!--COPage-PageTop--><div data-copg-ed-type="add-area" data-hierid="pg" data-pcid="" id="add"><div class="il_droparea" id="TARGETpg:" style="display: none;"></div><div class="dropdown"><button class="btn btn-default dropdown-toggle copg-add" type="button" aria-label="Actions" aria-haspopup="true" aria-expanded="false" aria-controls="il_ui_fw_68a87183eaa361_91099572_menu" style=""><span class="glyphicon glyphicon-plus-sign"></span></button>
<ul class="dropdown-menu" style="display: none;" data-copg-d-d-shown="0">
	<li><a href="#">label</a></li>
</ul>
</div>
</div><div id="pc3146392662523651786"><div class="il_editarea" data-copg-ed-type="pc-area" id="CONTENT1:3146392662523651786" data-hierid="1" data-pcid="3146392662523651786" data-cname="Paragraph" data-characteristic="Headline3" draggable="true"><div class="ilEditLabel">Text				(Headline 3)			<!--Dummy--></div><!--ilPageTocH31--><h3 id="ilPageTocA31" style="position:static;" class="ilc_Paragraph ilc_heading3_Headline3"><!--PageTocPH--><a name="jump1"><!--Break--></a>Richtig ist richtig, falsch ist&nbsp; falsch<!--Break--></h3></div></div><div data-copg-ed-type="add-area" data-hierid="1" data-pcid="3146392662523651786" id="add3146392662523651786"><div class="il_droparea" id="TARGET1:3146392662523651786" style="display: none;"></div><div class="dropdown"><button class="btn btn-default dropdown-toggle copg-add" type="button" aria-label="Actions" aria-haspopup="true" aria-expanded="false" aria-controls="il_ui_fw_68a87183eaa361_91099572_menu" style=""><span class="glyphicon glyphicon-plus-sign"></span></button>
<ul class="dropdown-menu" style="display: none;" data-copg-d-d-shown="0">
	<li><a href="#">label</a></li>
</ul>
</div>
</div><div id="pc7f5617949ba5915450d02f9259186a63"><div class="il_editarea" data-copg-ed-type="pc-area" id="CONTENT3:7f5617949ba5915450d02f9259186a63" data-hierid="3" data-pcid="7f5617949ba5915450d02f9259186a63" data-cname="Question" data-characteristic="" draggable="true"><div class="ilEditLabel">Question<!--Dummy--></div><div class="ilc_question_Standard"><a name="jump3"><!--Break--></a>	 <div class="ilc_question_SingleChoice">

	<div class="ilc_answers answers answer-table ilClearFloat">

		<div class="ilc_qanswer_Answer">
			<div>
				<input type="radio" name="multiple_choice_result5982ID" value="0" id="answer_0">
			</div>
			<div>
				<label for="answer_0" class="answertext">


					Richtig
				</label>
			</div>
		</div>


		<div class="ilc_qanswer_Answer">
			<div>
				<input type="radio" name="multiple_choice_result5982ID" value="1" id="answer_1">
			</div>
			<div>
				<label for="answer_1" class="answertext">


					Falsch
				</label>
			</div>
		</div>
	</div>
</div>
	<br><div class="ilOverlay il_editmenu ilNoDisplay" id="contextmenu_3"><a href="#" class="ilGroupedListLE" onmouseover="M_in(this);" onmouseout="M_out(this);" onclick="doActionForm('cmd[exec]', 'command', 'edit', '', 'Question', ''); return false;">Edit</a></div></div></div></div><div data-copg-ed-type="add-area" data-hierid="3" data-pcid="7f5617949ba5915450d02f9259186a63" id="add7f5617949ba5915450d02f9259186a63"><div class="il_droparea" id="TARGET3:7f5617949ba5915450d02f9259186a63" style="display: none;"></div><div class="dropdown"><button class="btn btn-default dropdown-toggle copg-add" type="button" aria-label="Actions" aria-haspopup="true" aria-expanded="false" aria-controls="il_ui_fw_68a87183eaa361_91099572_menu" style=""><span class="glyphicon glyphicon-plus-sign"></span></button>
<ul class="dropdown-menu" style="display: block;" data-copg-d-d-shown="1"><li><a href="#">Insert Answer Form</a></li><li><a href="#">Insert Text</a></li><li><a href="#">Insert Image/Media</a></li><li><a href="#">Insert File List</a></li><li><a href="#">Insert Data Table</a></li><li><a href="#">Insert Section</a></li><li><a href="#">Insert Accordion/Carousel</a></li><li><a href="#">Insert Column Layout</a></li><li><a href="#">Insert Interactive Image</a></li><li><a href="#">Insert Code</a></li><li><a href="#">Insert Advanced Table</a></li><li><a href="#">Insert Advanced List</a></li></ul>
</div>
</div><div style="clear:both;"><!--Break--></div><script></script><div class="il-copg-mob-fullscreen-modal"><dialog class="c-modal il-modal-roundtrip" tabindex="-1" id="il_ui_fw_68a8718272f4f5_77784737">
	<div class="modal-dialog" role="document" data-replace-marker="component">
		<div class="modal-content">
			<div class="modal-header">
				<button formmethod="dialog" class="close" aria-label="Close"><span aria-hidden="true">×</span></button>
				<h1 class="modal-title">Full Screen</h1>
			</div>
			<div class="modal-body">

				<iframe class="il-copg-mob-fullscreen" id="il-copg-mob-fullscreen-qpl-5982"></iframe>


			</div>
			<div class="modal-footer">
				<form>


					<button formmethod="dialog" class="btn btn-default" data-dismiss="modal">Cancel</button>
				</form>
			</div>
		</div>
	</div>
</dialog>
</div><script type="module" src="./components/ILIAS/COPage/PC/InteractiveImage/js/presentation/src/presentation.js"></script>
</div>
</div></form>
<table class="fullwidth" id="ilPageEditActionBar">
</table>
<!-- form used for ajax actions -->
<form name="ajaxform" id="ajaxform" method="post" action="ilias.php?baseClass=ilrepositorygui&amp;cmdNode=wt:oz:4g:uz&amp;cmdClass=ilpageeditorgui&amp;cmd=post&amp;ref_id=540&amp;q_id=5982&amp;rtoken=0ff1ba4f7f6a418d1566b373e814902d5a146cbdfd4c9c66463d97ec9444db52&amp;cmdMode=asynch">
<input type="hidden" id="ajaxform_target" name="target[]" value="">
<input type="hidden" id="ajaxform_cmd" name="" value="">
<input type="hidden" id="ajaxform_exec" name="" value="Ok">
<input type="hidden" id="ajaxform_content" name="ajaxform_content">
<input type="hidden" id="ajaxform_char" name="ajaxform_char">
<input type="hidden" id="ajaxform_hier_id" name="ajaxform_hier_id">
</form>
<form name="ajaxform2" id="ajaxform2" method="post" action="ilias.php?baseClass=ilrepositorygui&amp;cmdNode=wt:oz:4g:uz&amp;cmdClass=ilpageeditorgui&amp;cmd=post&amp;ref_id=540&amp;q_id=5982&amp;rtoken=0ff1ba4f7f6a418d1566b373e814902d5a146cbdfd4c9c66463d97ec9444db52&amp;cmdMode=asynch">
</form>
<!-- form used for menu actions actions -->
<form style="visibility:hidden;" name="cmform" id="cmform" method="post" action="ilias.php?baseClass=ilrepositorygui&amp;cmdNode=wt:oz:4g:uz&amp;cmdClass=ilpageeditorgui&amp;cmd=post&amp;ref_id=540&amp;q_id=5982&amp;rtoken=0ff1ba4f7f6a418d1566b373e814902d5a146cbdfd4c9c66463d97ec9444db52">
<input type="hidden" id="cmform_target" name="target[]" value="">
<input type="hidden" id="cmform_cmd" name="" value="">
<input type="hidden" id="cmform_exec" name="" value="Ok">
</form>
<!-- content of this layer will be changed to create a form to submit the action of the selected menuitem -->
<!-- tiny style -->
<style type="text/css">
td.mceIframeContainer {
	background-color:#FFFFFF;
}
</style>
<div id="ilIntLinkModal" data-show-signal="il_signal_68a871826c2f76_53426113" data-close-signal="il_signal_68a871826c2f80_99253057"></div>
</div>
HTML;
    } elseif ($create) {
        $content = $dic->ui()->renderer()->render(
            $f->input()->container()->form()->standard(
                '#',
                [
                    $ff->section(
                        [
                            $ff->text('Title'),
                            $ff->text('Author')->withValue($dic->user()->fullname),
                            $ff->select(
                                'Lifecycle',
                                [
                                    'draft' => 'Draft',
                                    'to_review' => 'To Be Reviewed',
                                    'rejected' => 'Rejected',
                                    'final' => 'Final',
                                    'shareable' => 'Shareable',
                                    'outdated' => 'Outdated'
                                ]
                            )->withValue('draft'),
                            $ff->textarea('Remarks')
                        ],
                        'Create Question'
                    )
                ]
            )
        );
    }

    $page = $f->layout()->page()->standard(
        [
            $f->legacy()->content($header),
            $edit || $create ? $f->legacy()->content('') : $f->input()->container()->form()->standard(
                '#',
                [
                    $ff->section(
                        [
                            $ff->text('Title')->withValue('My First Question')->withRequired(true),
                            $ff->text('Author')->withValue($dic->user()->fullname),
                            $ff->select(
                                'Lifecycle',
                                [
                                    'draft' => 'Draft',
                                    'to_review' => 'To Be Reviewed',
                                    'rejected' => 'Rejected',
                                    'final' => 'Final',
                                    'shareable' => 'Shareable',
                                    'outdated' => 'Outdated'
                                ]
                            )->withValue('shareable')->withRequired(true),
                            $ff->textarea('Remarks')
                        ],
                        'Edit Basic Question Properties'
                    )
                ]
            ),
            $edit || $create ? $f->legacy()->content($content) : $f->panel()->standard(
                'Preview',
                $f->legacy()->content($content)
            )->withActions(
                $f->dropdown()->standard([
                    $f->link()->standard(
                        'Edit',
                        $dic->http()->request()->getUri()->__toString() . '&sub_cmd=editQuestion'
                    ),
                    $f->link()->standard('Reset Preview', '#')
                ])
            ),
            $f->legacy()->content("</div></div>")
        ],
        $f->mainControls()->metaBar()->withAdditionalEntry(
            'help',
            $f->button()->bulky($f->symbol()->glyph()->help(), 'Help', '#')
        ),
        $main_controls->withAdditionalEntry("list", $question_list)
            ->withAdditionalEntry("edit", $question_create),
        $f->breadcrumbs([]),
        $f->image()->responsive("assets/images/logo/HeaderIcon.svg", "ILIAS"),
        $f->image()->responsive("assets/images/logo/HeaderIconResponsive.svg", "ILIAS"),
        "./assets/images/logo/favicon.ico",
        $dic->ui()->factory()->toast()->container(),
        $dic->ui()->factory()->mainControls()->footer()->withAdditionalText("Footer"),
        'Question View Demo', //page title
        'ILIAS', //short title
        'Mode Info Demo' //view title
    )->withHeaders(true)
    ->withUIDemo(true);


    /**
     * a Mode Info needs to know what happens when you exit the mode
     */
    $back = str_replace(
        'new_mode_info=' . QUESTION_EDIT_ACTIVE,
        'new_mode_info=' . QUESTION_EDIT_INACTIVE,
        $dic->http()->request()->getUri()->getQuery()
    );

    $mode_info = $f->mainControls()->modeInfo(
        "Edit Questions",
        $data_factory->uri($dic->http()->request()->getUri()->withQuery($back)->__toString())
    );

    /**
     * the Mode Info is attached to the page
     */
    $page = $page->withModeInfo($mode_info);

    return $renderer->render($page);
}
