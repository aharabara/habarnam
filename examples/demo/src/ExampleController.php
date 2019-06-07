<?php
namespace App;

use Base\Application;
use Base\Components\Button;
use Base\Components\Text;
use Base\Core\BaseController;
use Base\Core\Workspace;

class ExampleController extends BaseController{

    /** @var Text */
    protected $text;
    /** @var Text */
    protected $confirmText;
    /** @var Button[] */
    protected $buttons;

    public function __construct(Application $app, Workspace $workspace)
    {
        parent::__construct($app, $workspace);
        $this->text = $app->findFirst('#time-text', 'main');
        $this->confirmText = $app->findFirst('#confirm-text', 'confirm');
        $this->buttons = $app->findAll('button', 'confirm');
    }

    public function time()
    {
        $this->text->setText("Time : ".(new \DateTimeImmutable())->format('Y/M/D H:s:i'));
    }

    public function likeMe()
    {
        $this->confirmText->setText("Star me on github!!! Go aharabara/habarnam."
);
        foreach ($this->buttons as $button) {
            $button->visibility(false);
        }
    }

    public function helpMe()
    {
        $this->confirmText->setText("Help me to make it better! Go aharabara/habarnam.");
        foreach ($this->buttons as $button) {
            $button->visibility(false);
        }
    }
}