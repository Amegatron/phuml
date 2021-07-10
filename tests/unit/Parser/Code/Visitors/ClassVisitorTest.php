<?php declare(strict_types=1);
/**
 * PHP version 7.4
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */

namespace PhUml\Parser\Code\Visitors;

use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;
use PhUml\Code\Codebase;
use PhUml\Parser\Code\Builders\ClassDefinitionBuilder;

final class ClassVisitorTest extends TestCase
{
    /** @test */
    function it_ignores_anonymous_classes()
    {
        $builder = new ClassDefinitionBuilder();
        $codebase = new Codebase();
        $visitor = new ClassVisitor($builder, $codebase);
        $anonymousClass = new Class_(null);

        $visitor->leaveNode($anonymousClass);

        $this->assertEmpty($codebase->definitions());
    }
}
