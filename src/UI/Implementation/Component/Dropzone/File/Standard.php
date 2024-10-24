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

namespace ILIAS\UI\Implementation\Component\Dropzone\File;

use ILIAS\UI\Implementation\Component\Input\NameSource;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;
use ILIAS\UI\Component\Input\Field\File as FileInput;
use ILIAS\UI\Component\Dropzone\File\Standard as StandardDropzone;
use ILIAS\UI\Component\Input\Field\Factory as FieldFactory;
use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Component\Input\Container\Form\FormInput;

/**
 * @author  Thibeau Fuhrer <thibeau@sr.solutions>
 */
class Standard extends File implements StandardDropzone
{
    protected ?Button $upload_button = null;
    protected string $message;
    protected bool $bulky = false;

    public function __construct(
        SignalGeneratorInterface $signal_generator,
        FieldFactory $field_factory,
        NameSource $name_source,
        string $title,
        string $message,
        string $post_url,
        FileInput $file_input,
        ?FormInput $additional_input
    ) {
        parent::__construct(
            $signal_generator,
            $field_factory,
            $name_source,
            $title,
            $post_url,
            $file_input,
            $additional_input,
        );
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function withUploadButton(Button $button): self
    {
        $clone = clone $this;
        $clone->upload_button = $button;
        return $clone;
    }

    public function getUploadButton(): ?Button
    {
        return $this->upload_button;
    }

    public function withBulky(bool $bulky): StandardDropzone
    {
        $clone = clone $this;
        $clone->bulky = $bulky;
        return $clone;
    }

    public function isBulky(): bool
    {
        return $this->bulky;
    }
}
