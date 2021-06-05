<?php declare(strict_types=1);
/**
 * PHP version 7.2
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */

namespace PhUml\Parser\Code\Builders\Filters;

/**
 * It will exclude private methods or attributes
 */
final class ProtectedVisibilityFilter implements VisibilityFilter
{
    /** @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Property $member */
    public function accept($member): bool
    {
        return ! $member->isProtected();
    }
}
