<?php
declare(strict_types=1);

namespace Ahorn\FriendlyCaptcha\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;

/**
 * EEL helper providing Neos 8/9 version-compatible dimension value access.
 *
 * Registered as "Ahorn.FriendlyCaptcha" in Fusion's EEL default context.
 *
 * Usage in Fusion/EEL:
 *   ${Ahorn.FriendlyCaptcha.currentDimensionValue(node, 'language')}
 *   ${Ahorn.FriendlyCaptcha.isNeos9()}
 */
class NodeDimensionHelper implements ProtectedContextAwareInterface
{
    /**
     * Cached result of the Neos version check.
     */
    private static ?bool $isNeos9 = null;

    /**
     * Returns true if the running Neos instance is version 9 or higher
     * (event-sourced content repository).
     *
     * Detection is based on the presence of the Neos 9 DimensionSpacePoint class,
     * which does not exist in Neos 8.x.
     */
    public function isNeos9(): bool
    {
        if (self::$isNeos9 === null) {
            self::$isNeos9 = class_exists(
                \Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint::class
            );
        }

        return self::$isNeos9;
    }

    /**
     * Returns the current (requested) dimension value for a given node,
     * compatible with both Neos 8 and Neos 9.
     *
     * - Neos 9: reads node->dimensionSpacePoint->coordinates[$dimension]
     *   (the requested/subgraph DSP, so fallback nodes return the requested language,
     *   not the origin language)
     *
     * - Neos 8: reads node->getContext()->getTargetDimensions()[$dimension]
     *   (equivalent: the dimension the context was resolved for)
     *
     * @param mixed  $node      The document or content node (may be null)
     * @param string $dimension Dimension identifier, e.g. 'language'
     * @return string|null      The dimension value, or null if not determinable
     */
    public function currentDimensionValue(mixed $node, string $dimension): ?string
    {
        if ($node === null) {
            return null;
        }

        if ($this->isNeos9()) {
            // Neos 9: Node::$dimensionSpacePoint is a public readonly DimensionSpacePoint
            return $node->dimensionSpacePoint->coordinates[$dimension] ?? null;
        }

        // Neos 8: Node implements NodeInterface with getContext()
        if (method_exists($node, 'getContext')) {
            return $node->getContext()->getTargetDimensions()[$dimension] ?? null;
        }

        return null;
    }

    /**
     * Required by ProtectedContextAwareInterface.
     * Allow all public methods to be callable from EEL expressions.
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
