<?php declare(strict_types=1);
/**
 * PHP version 7.2
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */

namespace PhUml\Parser\Code\Builders\Members;

/**
 * It will ignore the attributes of a definition, and therefore its filters
 */
final class NoAttributesBuilder extends AttributesBuilder
{
    public function __construct()
    {
        parent::__construct(new VisibilityBuilder(), []);
    }

    public function build(array $definitionAttributes): array
    {
        return [];
    }
}
