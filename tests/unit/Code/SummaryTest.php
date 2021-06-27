<?php declare(strict_types=1);
/**
 * PHP version 7.2
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */

namespace PhUml\Code;

use PHPUnit\Framework\TestCase;
use PhUml\TestBuilders\A;

final class SummaryTest extends TestCase
{
    /** @test */
    function it_generates_a_summary_from_a_codebase()
    {
        $parentClass = A::class('ParentClass')
            ->withAProtectedAttribute('$attribute')
            ->withAPublicAttribute('$value', 'float')
            ->withAPublicAttribute('isValid')
            ->withAProtectedMethod('getAttribute')
            ->withAPrivateMethod('privateAction')
            ->withAConstant('TEST')
            ->build();
        $parentInterface = A::interface('ParentInterface')
            ->withAPublicMethod('dance')
            ->withAConstant('TYPED_TEST', 'float')
            ->build();
        $interface = A::interface('SomeAbility')
            ->withAPublicMethod('fly')
            ->extending($parentInterface->name())
            ->build();

        $codebase = new Codebase();
        $codebase->add($parentClass);
        $codebase->add($parentInterface);
        $codebase->add($interface);
        $codebase->add(A::class('ChildClass')
            ->withAPrivateAttribute('$name', 'string')
            ->withAPrivateAttribute('$salary')
            ->withAProtectedAttribute('$age', 'int')
            ->withAPublicMethod('getName')
            ->withAPublicMethod('getAge')
            ->implementing($interface->name())
            ->extending($parentClass->name())
            ->build());
        $summary = new Summary();

        $summary->from($codebase);

        $this->assertEquals(2, $summary->interfaceCount());
        $this->assertEquals(2, $summary->classCount());
        $this->assertEquals(4, $summary->publicFunctionCount());
        $this->assertEquals(2, $summary->publicAttributeCount());
        $this->assertEquals(1, $summary->publicTypedAttributes());
        $this->assertEquals(1, $summary->protectedFunctionCount());
        $this->assertEquals(2, $summary->protectedAttributeCount());
        $this->assertEquals(1, $summary->protectedTypedAttributes());
        $this->assertEquals(1, $summary->privateFunctionCount());
        $this->assertEquals(2, $summary->privateAttributeCount());
        $this->assertEquals(1, $summary->privateTypedAttributes());
        $this->assertEquals(6, $summary->functionCount());
        $this->assertEquals(6, $summary->attributeCount());
        $this->assertEquals(3, $summary->typedAttributeCount());
        $this->assertEquals(3, $summary->attributesPerClass());
        $this->assertEquals(3, $summary->functionsPerClass());
    }

    /** @test */
    function it_generates_a_summary_from_an_empty_codebase()
    {
        $codebase = new Codebase();
        $summary = new Summary();

        $summary->from($codebase);

        $this->assertEquals(0, $summary->interfaceCount());
        $this->assertEquals(0, $summary->classCount());
        $this->assertEquals(0, $summary->publicFunctionCount());
        $this->assertEquals(0, $summary->publicAttributeCount());
        $this->assertEquals(0, $summary->publicTypedAttributes());
        $this->assertEquals(0, $summary->protectedFunctionCount());
        $this->assertEquals(0, $summary->protectedAttributeCount());
        $this->assertEquals(0, $summary->protectedTypedAttributes());
        $this->assertEquals(0, $summary->privateFunctionCount());
        $this->assertEquals(0, $summary->privateAttributeCount());
        $this->assertEquals(0, $summary->privateTypedAttributes());
        $this->assertEquals(0, $summary->functionCount());
        $this->assertEquals(0, $summary->attributeCount());
        $this->assertEquals(0, $summary->typedAttributeCount());
        $this->assertEquals(0, $summary->attributesPerClass());
        $this->assertEquals(0, $summary->functionsPerClass());
    }
}
