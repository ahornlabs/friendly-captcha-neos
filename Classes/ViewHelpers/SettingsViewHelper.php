<?php
declare(strict_types=1);

namespace Ahorn\FriendlyCaptcha\ViewHelpers;

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

class SettingsViewHelper extends AbstractViewHelper
{
    #[Flow\InjectConfiguration(path: 'siteKey')]
    protected ?string $siteKey = null;

    #[Flow\InjectConfiguration(path: 'theme')]
    protected ?string $theme = null;

    #[Flow\InjectConfiguration(path: 'apiEndpoint')]
    protected ?string $apiEndpoint = null;

    #[Flow\InjectConfiguration(path: 'startVerification')]
    protected ?string $startVerification = null;

    #[Flow\InjectConfiguration(path: 'language')]
    protected ?string $language = null;

    public function render(): array
    {
        return [
            'siteKey'           => $this->siteKey ?? '',
            'theme'             => $this->theme ?? 'auto',
            'apiEndpoint'       => $this->apiEndpoint ?? 'global',
            'startVerification' => $this->startVerification ?? 'auto',
            'language'          => $this->resolveLanguage(),
        ];
    }

    private function resolveLanguage(): string
    {
        $node = $this->templateVariableContainer->get('node');
        $dimensionLanguage = $node?->getDimensions()['language'][0] ?? null;

        $language = $this->language ?? $dimensionLanguage ?? 'de';

        return explode('_', $language)[0];
    }
}
