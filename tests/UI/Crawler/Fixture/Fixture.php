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

class ProperEntryFixture
{
    public array $properEntryYamlArray = array(
        array(
            "description" => array(
                "purpose" => "Description of Purpose",
                "composition" => "Description of Composition with line break",
                "effect" => "Effect Description on one line",
                "rivals" => array(
                    "icon" => "Icon Description"
                )

            ),
            "background" => "\"Some wild background with quotes\" and links <a href='http:test'>test</a>",
            "featurewiki" => array("http://www.ilias.de/docu/goto_docu_wiki_wpage_3208_1357.html"),
            "context" => array("Some Context"),
            "rules" => array(
                "usage" => array(
                    1 => "Usage Rule 1",
                    2 => "Usage Rule 2",
                    3 => "Usage Rule 3 multi line"
                ),
                "style" => array(
                    4 => "Style Rule"
                )
            ),
            "namespace" => "\\ILIAS\\UI\\Crawler\\Fixture\\ProperEntry",
            "function_name" => "properEntry"

        )
    );

    public string $properEntryYamlString =
"description:
  purpose: >
      Description of Purpose
  composition: >
      Description of Composition
      with line break
  effect: Effect Description on one line
  rivals:
      icon: >
          Icon Description
background: >
    \"Some wild background with quotes\"
    and links <a href='http:test'>test</a>
context:
  - Some Context
featurewiki:
      - http://www.ilias.de/docu/goto_docu_wiki_wpage_3208_1357.html
rules:
  usage:
      1: Usage Rule 1
      2: Usage Rule 2
      3: >
        Usage Rule 3 multi line
  style:
      4: Style Rule
namespace:   \\ILIAS\\UI\\Crawler\\Fixture\\ProperEntry
function_name: properEntry";
}
