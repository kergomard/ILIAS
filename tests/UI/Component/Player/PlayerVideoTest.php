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

require_once(__DIR__ . "/../../../../libs/composer/vendor/autoload.php");
require_once(__DIR__ . "/../../Base.php");

use ILIAS\UI\Component as C;
use ILIAS\UI\Implementation as I;
use ILIAS\UI\Component\Input\Field\Factory as FieldFactory;

/**
 * @author Alexander Killing <killing@leifos.de>
 */
class PlayerVideoTest extends ILIAS_UI_TestBase
{
    public function getUIFactory(): NoUIFactory
    {
        return new class (
            $this->createMock(C\Modal\InterruptiveItem\Factory::class),
            $this->createMock(FieldFactory::class),
        ) extends NoUIFactory {
            public function __construct(
                protected C\Modal\InterruptiveItem\Factory $item_factory,
                protected FieldFactory $field_factory,
            ) {
            }

            public function modal(): C\Modal\Factory
            {
                return new I\Component\Modal\Factory(
                    new I\Component\SignalGenerator(),
                    $this->item_factory,
                    $this->field_factory,
                );
            }
            public function button(): C\Button\Factory
            {
                return new I\Component\Button\Factory();
            }
        };
    }

    public function getFactory(): C\Player\Factory
    {
        return new I\Component\Player\Factory();
    }

    public function testImplementsFactoryInterface(): void
    {
        $f = $this->getFactory();

        $video = $f->video("/foo");

        $this->assertInstanceOf("ILIAS\\UI\\Component\\Player\\Video", $video);
    }

    public function testGetTitleGetSource(): void
    {
        $f = $this->getFactory();

        $video = $f->video("/foo");

        $this->assertEquals("/foo", $video->getSource());
    }

    public function testGetTitleGetPoster(): void
    {
        $f = $this->getFactory();

        $video = $f->video("/foo")->withPoster("bar.jpg");

        $this->assertEquals("bar.jpg", $video->getPoster());
    }

    public function testGetTitleGetSubtitleFile(): void
    {
        $f = $this->getFactory();

        $video = $f->video("/foo")->withAdditionalSubtitleFile("en", "subtitles.vtt");

        $this->assertEquals(["en" => "subtitles.vtt"], $video->getSubtitleFiles());
    }

    public function testRenderVideo(): void
    {
        $f = $this->getFactory();
        $r = $this->getDefaultRenderer();

        $video = $f->video("/foo");

        $html = $r->render($video);
        $expected = <<<EOT
<div class="il-video-container">
    <video class="il-video-player" id="id_1" src="/foo" style="max-width: 100%;" preload="metadata" >
    </video>
</div>
EOT;
        $this->assertHTMLEquals(
            $this->brutallyTrimHTML($expected),
            $this->brutallyTrimHTML($html)
        );
    }

    public function testRenderWithPoster(): void
    {
        $f = $this->getFactory();
        $r = $this->getDefaultRenderer();

        $video = $f->video("/foo")->withPoster("bar.jpg");

        $html = $r->render($video);

        $expected = <<<EOT
<div class="il-video-container">
    <video class="il-video-player" id="id_1" src="/foo" style="max-width: 100%;" preload="metadata" poster="bar.jpg">
    </video>
</div>
EOT;
        $this->assertHTMLEquals(
            $this->brutallyTrimHTML($expected),
            $this->brutallyTrimHTML($html)
        );
    }

    public function testRenderWithSubtitles(): void
    {
        $f = $this->getFactory();
        $r = $this->getDefaultRenderer();

        $video = $f->video("/foo")->withAdditionalSubtitleFile("en", "subtitles.vtt");

        $html = $r->render($video);
        $expected = <<<EOT
<div class="il-video-container">
    <video class="il-video-player" id="id_1" src="/foo" style="max-width: 100%;" preload="metadata" >
        <track kind="subtitles" src="subtitles.vtt" srclang="en" />
    </video>
</div>
EOT;
        $this->assertHTMLEquals(
            $this->brutallyTrimHTML($expected),
            $this->brutallyTrimHTML($html)
        );
    }
}
