<?php
/**
 * PHP version 7.1
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */

namespace PhUml\Graphviz\Builders;

use PHPUnit\Framework\TestCase;
use PhUml\Code\Codebase;
use PhUml\Graphviz\Edge;
use PhUml\Graphviz\Node;
use PhUml\TestBuilders\A;

class ClassGraphBuilderTest extends TestCase
{
    /** @test */
    function it_extracts_the_elements_for_a_simple_class()
    {
        $class = A::classNamed('ClassName');
        $graphElements = new ClassGraphBuilder();

        $dotElements = $graphElements->extractFrom($class, new Codebase());

        $this->assertEquals([new Node($class)], $dotElements);
    }

    /** @test */
    function it_extracts_the_elements_for_a_class_with_a_parent()
    {
        $parent = A::classNamed('ParentClass');
        $class = A::class('ChildClass')->extending($parent->name())->build();
        $codebase = new Codebase();
        $codebase->add($parent);
        $graphElements = new ClassGraphBuilder();

        $dotElements = $graphElements->extractFrom($class, $codebase);

        $this->assertEquals([
            new Node($class),
            Edge::inheritance($parent, $class),
        ], $dotElements);
    }

    /** @test */
    function it_extracts_the_elements_for_a_class_implementing_interfaces()
    {
        $firstInterface = A::interfaceNamed('FirstInterface');
        $secondInterface = A::interfaceNamed('FirstInterface');
        $class = A::class('AClass')
            ->implementing($firstInterface->name(), $secondInterface->name())
            ->build();
        $codebase = new Codebase();
        $codebase->add($firstInterface);
        $codebase->add($secondInterface);
        $graphElements = new ClassGraphBuilder();

        $dotElements = $graphElements->extractFrom($class, $codebase);

        $this->assertEquals([
            new Node($class),
            Edge::implementation($firstInterface, $class),
            Edge::implementation($secondInterface, $class),
        ], $dotElements);
    }

    /** @test */
    function it_extracts_the_elements_for_a_class_with_associations_in_the_constructor()
    {
        $reference = A::classNamed('AnotherClass');
        $class = A::class('AClass')
            ->withAPublicMethod(
                '__construct',
                A::parameter('$reference')->withType($reference->name())->build()
            )
            ->build();
        $classGraphBuilder = new ClassGraphBuilder(new EdgesBuilder());
        $codebase = new Codebase();
        $codebase->add($reference);

        $dotElements = $classGraphBuilder->extractFrom($class, $codebase);

        $this->assertEquals([
            Edge::association($reference, $class),
            new Node($class),
        ], $dotElements);
    }

    /** @test */
    function it_extracts_the_elements_for_a_class_with_associations_in_the_attributes()
    {
        $firstReference = A::classNamed('FirstClass');
        $secondReference = A::classNamed('SecondClass');
        $class = A::class('AClass')
            ->withAPrivateAttribute('$firstReference', $firstReference->name())
            ->withAPrivateAttribute('$secondReference', $secondReference->name())
            ->build();
        $classGraphBuilder = new ClassGraphBuilder(new EdgesBuilder());
        $codebase = new Codebase();
        $codebase->add($firstReference);
        $codebase->add($secondReference);

        $dotElements = $classGraphBuilder->extractFrom($class, $codebase);

        $this->assertEquals([
            Edge::association($firstReference, $class),
            Edge::association($secondReference, $class),
            new Node($class),
        ], $dotElements);
    }

    /** @test */
    function it_extracts_the_elements_of_a_class_with_all_types_of_associations()
    {
        $firstReference = A::classNamed('FirstClass');
        $secondReference = A::classNamed('SecondClass');
        $thirdReference = A::classNamed('ThirdClass');
        $fourthReference = A::classNamed('FourthClass');
        $firstInterface = A::interfaceNamed('FirstInterface');
        $secondInterface = A::interfaceNamed('FirstInterface');
        $parent = A::classNamed('ParentClass');

        $class = A::class('AClass')
            ->withAPrivateAttribute('$firstReference', $firstReference->name())
            ->withAPrivateAttribute('$secondReference', $secondReference->name())
            ->withAPublicMethod(
                '__construct',
                A::parameter('$thirdReference')->withType($thirdReference->name())->build(),
                A::parameter('$fourthReference')->withType($fourthReference->name())->build()
            )
            ->implementing($firstInterface->name(), $secondInterface->name())
            ->extending($parent->name())
            ->build();
        $classGraphBuilder = new ClassGraphBuilder(new EdgesBuilder());
        $codebase = new Codebase();
        $codebase->add($firstReference);
        $codebase->add($secondReference);
        $codebase->add($thirdReference);
        $codebase->add($fourthReference);
        $codebase->add($parent);
        $codebase->add($firstInterface);
        $codebase->add($secondInterface);

        $dotElements = $classGraphBuilder->extractFrom($class, $codebase);

        $this->assertEquals([
            Edge::association($firstReference, $class),
            Edge::association($secondReference, $class),
            Edge::association($thirdReference, $class),
            Edge::association($fourthReference, $class),
            new Node($class),
            Edge::inheritance($parent, $class),
            Edge::implementation($firstInterface, $class),
            Edge::implementation($secondInterface, $class),
        ], $dotElements);
    }

    /** @test */
    function it_extracts_association_to_same_class_from_different_classes()
    {
        $reference = A::classNamed('AReference');
        $class = A::class('AClass')
            ->withAPrivateAttribute('$firstReference', $reference->name())
            ->build()
        ;
        $anotherClass = A::class('AnotherClass')
            ->withAPrivateAttribute('$firstReference', $reference->name())
            ->build()
        ;
        $codebase = new Codebase();
        $codebase->add($reference);
        $classGraphBuilder = new ClassGraphBuilder(new EdgesBuilder());

        $dotElements = $classGraphBuilder->extractFrom($class, $codebase);
        $dotElements = array_merge($dotElements, $classGraphBuilder->extractFrom($anotherClass, $codebase));

        $this->assertEquals([
            Edge::association($reference, $class),
            new Node($class),
            Edge::association($reference, $anotherClass),
            new Node($anotherClass),
        ], $dotElements);
    }

    /** @test */
    function it_ignores_associations_if_specified()
    {
        $class = A::class('AClass')
            ->withAPrivateAttribute('$firstReference', 'FirstClass')
            ->withAPrivateAttribute('$secondReference', 'SecondClass')
            ->withAPublicMethod(
                '__construct',
                A::parameter('$thirdReference')->withType('ThirdClass')->build(),
                A::parameter('$fourthReference')->withType('FourthClass')->build()
            )
            ->build();
        $graphElements = new ClassGraphBuilder();

        $dotElements = $graphElements->extractFrom($class, new Codebase());

        $this->assertEquals([new Node($class)], $dotElements);
    }
}
