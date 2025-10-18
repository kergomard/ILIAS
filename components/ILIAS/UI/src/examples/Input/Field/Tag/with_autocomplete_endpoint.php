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

namespace ILIAS\UI\examples\Input\Field\Tag;

use ILIAS\UI\URLBuilder;
use ILIAS\Filesystem\Stream\Streams;

/**
 * ---
 * description: >
 *   The example shows how to create and render a basic tag input field and attach it to a
 *   form. This example does not contain any data processing.
 *
 * expected output: >
 *   ILIAS shows an input field titled "Tag Input with Autocomplete". A completion of
 *   the tags will be displayed by ILIAS if an A, B, I or R is typed into the field.
 *   It is also possible to insert tags of your own and confirm those through hitting
 *   the Enter button on your keyboard. Afterwards the tags will be highlighted with color.
 *   An "X" is displayed directly next to each tag. Clicking the "X" will remove the tag.
 *   Clicking "Save" will reload the page and will set the Tag in the input field back to "Interesting".
 * ---
 */
function with_autocomplete_endpoint()
{
    /** @var \ILIAS\DI\Container $DIC */
    global $DIC;
    $ui = $DIC['ui.factory'];
    $renderer = $DIC['ui.renderer'];

    /** @var \ILIAS\User\Search\Search  $search */
    $search = $DIC['user']->getSearch();

    return  $renderer->render(
        $ui->input()->container()->form()->standard(
            "#",
            [$search->getInput('User Search', $search->getDefaultEndpointConfigurator([\ilObjUserFolderGUI::class]))]
        )
    );
}
