<?php declare(strict_types=1);
/**
 * PHP version 7.2
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */

namespace PhUml\Code\Attributes;

use PhUml\Code\Modifiers\CanBeStatic;
use PhUml\Code\Modifiers\HasVisibility;
use PhUml\Code\Modifiers\Visibility;
use PhUml\Code\Modifiers\WithStaticModifier;
use PhUml\Code\Modifiers\WithVisibility;
use PhUml\Code\Name;
use PhUml\Code\Variables\HasType;
use PhUml\Code\Variables\TypeDeclaration;
use PhUml\Code\Variables\Variable;

/**
 * It represents an instance variable
 */
final class Attribute implements HasType, HasVisibility, CanBeStatic
{
    use WithVisibility;
    use WithStaticModifier;

    /** @var Variable */
    private $variable;

    public function __construct(Variable $variable, Visibility $modifier, bool $isStatic)
    {
        $this->variable = $variable;
        $this->modifier = $modifier;
        $this->isStatic = $isStatic;
    }

    public static function public(Variable $variable): Attribute
    {
        return new static($variable, Visibility::public(), false);
    }

    public static function staticPublic(Variable $variable): Attribute
    {
        return new static($variable, Visibility::public(), true);
    }

    public static function protected(Variable $variable): Attribute
    {
        return new static($variable, Visibility::protected(), false);
    }

    public static function staticProtected(Variable $variable): Attribute
    {
        return new static($variable, Visibility::protected(), true);
    }

    public static function private(Variable $variable): Attribute
    {
        return new static($variable, Visibility::private(), false);
    }

    public static function staticPrivate(Variable $variable): Attribute
    {
        return new static($variable, Visibility::private(), true);
    }

    public function isAReference(): bool
    {
        return $this->variable->isAReference();
    }

    public function referenceName(): Name
    {
        return $this->variable->referenceName();
    }

    public function hasTypeDeclaration(): bool
    {
        return $this->variable->hasTypeDeclaration();
    }

    public function type(): TypeDeclaration
    {
        return $this->variable->type();
    }

    public function __toString()
    {
        return sprintf(
            '%s%s',
            $this->modifier,
            $this->variable
        );
    }
}
