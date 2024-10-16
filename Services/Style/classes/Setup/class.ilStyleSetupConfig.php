<?php

/* Copyright (c) 2019 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

use ILIAS\Setup;
use ILIAS\Data\Password;

class ilStyleSetupConfig implements Setup\Config
{
    /**
     * @var bool
     */
    protected $manage_system_styles;

    /**
     * @var string|null
     */
    protected $path_to_scss;

    public function __construct(
        bool $manage_system_styles,
        ?string $path_to_scss
    ) {
        $this->manage_system_styles = $manage_system_styles;
        $this->path_to_scss = $this->toLinuxConvention($path_to_scss);
    }

    protected function toLinuxConvention(?string $p): ?string
    {
        if (!$p) {
            return null;
        }
        return preg_replace("/\\\\/", "/", $p);
    }

    public function getManageSystemStyles(): bool
    {
        return $this->manage_system_styles;
    }

    public function getPathToScss(): ?string
    {
        return $this->path_to_scss;
    }
}
