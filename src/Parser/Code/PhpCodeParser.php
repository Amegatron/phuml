<?php declare(strict_types=1);
/**
 * PHP version 7.4
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */

namespace PhUml\Parser\Code;

use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhUml\Code\Codebase;
use PhUml\Parser\Code\Builders\ClassDefinitionBuilder;
use PhUml\Parser\Code\Builders\Filters\PrivateVisibilityFilter;
use PhUml\Parser\Code\Builders\Filters\ProtectedVisibilityFilter;
use PhUml\Parser\Code\Builders\InterfaceDefinitionBuilder;
use PhUml\Parser\Code\Builders\Members\FilteredAttributesBuilder;
use PhUml\Parser\Code\Builders\Members\FilteredConstantsBuilder;
use PhUml\Parser\Code\Builders\Members\FilteredMethodsBuilder;
use PhUml\Parser\Code\Builders\Members\NoAttributesBuilder;
use PhUml\Parser\Code\Builders\Members\NoConstantsBuilder;
use PhUml\Parser\Code\Builders\Members\NoMethodsBuilder;
use PhUml\Parser\Code\Builders\Members\ParametersBuilder;
use PhUml\Parser\Code\Builders\Members\TypeBuilder;
use PhUml\Parser\Code\Builders\Members\VisibilityBuilder;
use PhUml\Parser\Code\Builders\Members\VisibilityFilters;
use PhUml\Parser\Code\Builders\MembersBuilder;
use PhUml\Parser\Code\Builders\TraitDefinitionBuilder;
use PhUml\Parser\CodeFinder;
use PhUml\Parser\CodeParserConfiguration;
use PhUml\Parser\SourceCode;

/**
 * It traverses the AST of all the files and interfaces found by the `CodeFinder` and builds a
 * `Codebase` object
 *
 * In order to create the collection of definitions it uses the following visitors
 *
 * - The `ClassVisitor` which builds `ClassDefinition`s
 * - The `InterfaceVisitor` which builds `InterfaceDefinition`s
 * - The `TraitVisitor` which builds `TraitDefinition`s
 */
final class PhpCodeParser
{
    private Parser $parser;

    private PhpTraverser $traverser;

    public static function fromConfiguration(CodeParserConfiguration $configuration): PhpCodeParser
    {
        if ($configuration->hideAttributes()) {
            $constantsBuilder = new NoConstantsBuilder();
            $attributesBuilder = new NoAttributesBuilder();
        }
        if ($configuration->hideMethods()) {
            $methodsBuilder = new NoMethodsBuilder();
        }
        $filters = [];
        if ($configuration->hidePrivate()) {
            $filters[] = new PrivateVisibilityFilter();
        }
        if ($configuration->hideProtected()) {
            $filters[] = new ProtectedVisibilityFilter();
        }
        $visibilityBuilder = new VisibilityBuilder();
        $typeBuilder = new TypeBuilder();
        $filters = new VisibilityFilters($filters);
        $methodsBuilder ??= new FilteredMethodsBuilder(
            new ParametersBuilder($typeBuilder),
            $typeBuilder,
            $visibilityBuilder,
            $filters
        );
        $constantsBuilder ??= new FilteredConstantsBuilder($visibilityBuilder, $filters);
        $attributesBuilder ??= new FilteredAttributesBuilder(
            $visibilityBuilder,
            $typeBuilder,
            $filters
        );
        $membersBuilder = new MembersBuilder($constantsBuilder, $attributesBuilder, $methodsBuilder);

        return new PhpCodeParser(
            new ClassDefinitionBuilder($membersBuilder),
            new InterfaceDefinitionBuilder($membersBuilder),
            new TraitDefinitionBuilder($membersBuilder)
        );
    }

    private function __construct(
        ClassDefinitionBuilder $classBuilder = null,
        InterfaceDefinitionBuilder $interfaceBuilder = null,
        TraitDefinitionBuilder $traitBuilder = null
    ) {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new PhpTraverser(
            $classBuilder ?? new ClassDefinitionBuilder(),
            $interfaceBuilder ?? new InterfaceDefinitionBuilder(),
            $traitBuilder ?? new TraitDefinitionBuilder()
        );
    }

    public function parse(SourceCode $sourceCode): Codebase
    {
        foreach ($sourceCode->fileContents() as $code) {
            /** @var Stmt[] $nodes Since the parser is run in throw errors mode */
            $nodes = $this->parser->parse($code);
            $this->traverser->traverse($nodes);
        }
        return $this->traverser->codebase();
    }
}
