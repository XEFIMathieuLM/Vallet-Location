<?php

namespace Tests\Concerns;

use DOMDocument;
use DOMElement;
use DOMXPath;

trait InspectsPrimaryActions
{
    protected function assertAtMostOnePrimaryAction(string $html): void
    {
        $labels = $this->primaryActionLabels($html);

        $this->assertLessThanOrEqual(1, count($labels), 'Plusieurs actions jaunes hors modale : '.implode(', ', $labels));
    }

    /**
     * @return list<string>
     */
    protected function primaryActionLabels(string $html): array
    {
        $document = new DOMDocument;
        $hasPreviousErrorSetting = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($hasPreviousErrorSetting);

        $primaryButtons = (new DOMXPath($document))->query('//*[@data-flux-button][@data-variant="primary"][not(ancestor::ui-modal)]');

        $labels = [];
        foreach ($primaryButtons === false ? [] : $primaryButtons as $primaryButton) {
            if ($primaryButton instanceof DOMElement) {
                $labels[] = trim((string) preg_replace('/\s+/', ' ', $primaryButton->textContent));
            }
        }

        return $labels;
    }
}
