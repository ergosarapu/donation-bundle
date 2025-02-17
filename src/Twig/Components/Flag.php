<?php

namespace ErgoSarapu\DonationBundle\Twig\Components;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Flag as EAFlag;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Flag extends EAFlag
{
    private ?string $viewBox = null;

    private ?string $body = null;

    private function init(): void {
        if ($this->body !== null) {
            return;
        }

        $this->body = $this->getFlagAsSvg();
        preg_match('/viewBox="([^"]+)"/', $this->body, $matches);
        $this->viewBox = $matches[1];
        $this->body = preg_replace('/<svg[^>]*>/', '', $this->body);
        $this->body = str_replace('</svg>', '', $this->body);
    }

    public function getFlagAsSvgBodyOnly(): string {
        $this->init();
        return $this->body;
    }

    public function getViewBox(): ?string {
        $this->init();
        return $this->viewBox;
    }
}
