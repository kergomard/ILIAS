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

namespace ILIAS\Questions\AnswerFormTypes\Cloze\Response;

use ILIAS\Questions\AnswerForm\Persistence\AnswerFormSpecificTableTypes;
use ILIAS\Questions\AnswerFormTypes\Cloze\Properties\Properties;
use ILIAS\Questions\Persistence\Factory as PersistenceFactory;
use ILIAS\Questions\Persistence\Query;
use ILIAS\Data\UUID\Factory as UuidFactory;
use ILIAS\Data\UUID\Uuid;
use ILIAS\HTTP\Wrapper\RequestWrapper;

class Factory
{
    public function __construct(
        private readonly UuidFactory $uuid_factory,
        private readonly PersistenceFactory $persistence_factory
    ) {
    }

    public function fromQuery(
        Uuid $response_id,
        Properties $answer_form_properties,
        ?Query $query
    ): AnswerForm {
        $table_definitions = $answer_form_properties
            ->getDefinition()
            ->getTableDefinitions();

        if ($query === null) {
            return new AnswerForm(
                $table_definitions,
                $response_id,
                $answer_form_properties->getAnswerFormId()
            );
        }

        return $query->retrieveCurrentRecord(
            $this->persistence_factory->table(
                $query->getTableNameBuilder(
                    $table_definitions->getTableSubNameSpace()
                ),
                AnswerFormSpecificTableTypes::Responses
            ),
            $query->getRefinery()->custom()->transformation(
                function (
                    array $vs
                ) use (
                    $table_definitions,
                    $response_id,
                    $answer_form_properties
                ): AnswerForm {
                    return new AnswerForm(
                        $table_definitions,
                        $response_id,
                        $answer_form_properties->getAnswerFormId(),
                        $this->retrieveAnswerInputResponsesFromValues(
                            $answer_form_properties,
                            $vs
                        )
                    );
                }
            )
        );
    }

    public function fromPost(
        Uuid $response_id,
        Properties $answer_form_properties,
        RequestWrapper $post_wrapper
    ): AnswerForm {
        return new AnswerForm(
            $answer_form_properties
                ->getDefinition()
                ->getTableDefinitions(),
            $response_id,
            $answer_form_properties->getAnswerFormId(),
            $answer_form_properties->getGaps()->retrieveResponsesFromPost(
                $post_wrapper,
                $this->uuid_factory
            ),
        );
    }

    public function getBestResponse(
        Properties $answer_form_properties,
    ): AnswerForm {
        return new AnswerForm(
            $answer_form_properties
                ->getDefinition()
                ->getTableDefinitions(),
            $this->uuid_factory->uuid4(),
            $answer_form_properties->getAnswerFormId(),
            $answer_form_properties->getGaps()->getBestResponses(),
        );
    }

    private function retrieveAnswerInputResponsesFromValues(
        Properties $answer_form_properties,
        array $values
    ): array {
        return array_map(
            function (array $vs) use ($answer_form_properties): AnswerInput {
                $answer_form_id = $this->uuid_factory
                    ->fromString($vs['answer_input_id']);

                return new AnswerInput(
                    $answer_form_properties
                        ->getGaps()
                        ->getGapById(
                            $answer_form_id
                        ),
                    $vs['selected_answer_option'] === null
                        ? null
                        : $this->uuid_factory
                            ->fromString($vs['selected_answer_option']),
                    $vs['text']
                );
            },
            $values
        );
    }
}
