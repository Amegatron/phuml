<?php declare(strict_types=1);
/**
 * PHP version 7.4
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */

namespace PhUml\Parser\Code;

use PhUml\Code\ClassDefinition;
use PhUml\Code\Codebase;
use PhUml\Code\InterfaceDefinition;
use PhUml\Code\Name;
use PhUml\Code\TraitDefinition;

/**
 * It checks the parent of a definition, the interfaces it implements, and the traits it uses
 * looking for external definitions
 *
 * An external definition is a class, trait or interface from a third party library, or a built-in class or interface
 */
final class ExternalDefinitionsResolver implements RelationshipsResolver
{
    public function resolve(Codebase $codebase): void
    {
        foreach ($codebase->definitions() as $definition) {
            if ($definition instanceof ClassDefinition) {
                $this->resolveForClass($definition, $codebase);
            } elseif ($definition instanceof InterfaceDefinition) {
                $this->resolveExternalInterfaces($definition->parents(), $codebase);
            } elseif ($definition instanceof TraitDefinition) {
                $this->resolveExternalTraits($definition->traits(), $codebase);
            }
        }
    }

    /**
     * It resolves for its parent class, its interfaces and traits
     */
    private function resolveForClass(ClassDefinition $definition, Codebase $codebase): void
    {
        $this->resolveExternalInterfaces($definition->interfaces(), $codebase);
        $this->resolveExternalTraits($definition->traits(), $codebase);
        $this->resolveExternalParentClass($definition, $codebase);
    }

    /** @param Name[] $interfaces */
    private function resolveExternalInterfaces(array $interfaces, Codebase $codebase): void
    {
        array_map(function (Name $interface) use ($codebase): void {
            if (! $codebase->has($interface)) {
                $codebase->add(new InterfaceDefinition($interface));
            }
        }, $interfaces);
    }

    /** @param Name[] $traits */
    private function resolveExternalTraits(array $traits, Codebase $codebase): void
    {
        array_map(function (Name $trait) use ($codebase): void {
            if (! $codebase->has($trait)) {
                $codebase->add(new TraitDefinition($trait));
            }
        }, $traits);
    }

    private function resolveExternalParentClass(ClassDefinition $definition, Codebase $codebase): void
    {
        if (! $definition->hasParent()) {
            return;
        }
        $parent = $definition->parent();
        if (! $codebase->has($parent)) {
            $codebase->add(new ClassDefinition($parent));
        }
    }
}
