<?php

namespace Northrook\Symfony\Service\Document;

use function Northrook\squish;

class DocumentContent
{
    private string $contentHtml;

    public function setContent( ?string $string ) : void {
        $this->contentHtml = squish( $string );
    }

    public function generateTitle(
        int     $maxLength = 255,
        ?string $template = null,
    ) : string {
        return __METHOD__;
    }

    // Implement a simple rule system.
    // Example:
    // Must end with period. Try end with period.
    // If stop word at length but not followed by period, add one.

    public function generateDescription(
        int     $maxLength = 255,
        ?string $template = null,
    ) : string {
        return __METHOD__;
    }

    public function generateBlurb(
        int     $maxLength = 255,
        ?string $template = null,
    ) : string {
        return __METHOD__;
    }

    public function generateKeywords() {

    }

    public function generateOutline() : array  {
        return [];
    }
}