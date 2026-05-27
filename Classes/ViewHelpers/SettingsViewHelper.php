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
        $dimensionLanguage = $this->resolveNodeDimensionValue($node, 'language');

        // Use configured language override first, then the page dimension, then empty string
        // (FriendlyCaptcha auto-detects from the browser when lang is empty)
        $language = $this->language ?? $dimensionLanguage ?? '';

        return $language !== '' ? explode('_', $language)[0] : '';
    }

    /**
     * Returns the current dimension value for the given node,
     * compatible with both Neos 8 and Neos 9.
     *
     * Neos 9: node->dimensionSpacePoint->coordinates[$dimension]
     * Neos 8: node->getContext()->getTargetDimensions()[$dimension]
     */
    private function resolveNodeDimensionValue(mixed $node, string $dimension): ?string
    {
        if ($node === null) {
            return null;
        }

        // Neos 9: DimensionSpacePoint class exists and Node has a public $dimensionSpacePoint property
        if (class_exists(\Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint::class)
            && property_exists($node, 'dimensionSpacePoint')
        ) {
            return $node->dimensionSpacePoint->coordinates[$dimension] ?? null;
        }

        // Neos 8: Node implements NodeInterface with getContext()
        if (method_exists($node, 'getContext')) {
            $targetDimensions = $node->getContext()->getTargetDimensions();
            $value = $targetDimensions[$dimension] ?? null;
            if (is_array($value)) {
                return $value[0] ?? null;
            }
            return is_string($value) ? $value : null;
        }

        return null;
    }
}
