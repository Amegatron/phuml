<?php declare(strict_types=1);
/**
 * PHP version 7.4
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */

namespace PhUml\Fakes;

use PhUml\Code\Attributes\Constant;
use PhUml\Code\InterfaceDefinition;
use PhUml\Code\Methods\Method;
use PhUml\Code\Name;

final class NumericIdInterface extends InterfaceDefinition
{
    private static int $id = 0;

    private int $identifier;

    /**
     * @param Method[] $methods
     * @param Constant[] $constants
     * @param Name[] $parents
     */
    public function __construct(
        Name $name,
        array $methods = [],
        array $constants = [],
        array $parents = []
    ) {
        parent::__construct($name, $methods, $constants, $parents);
        self::$id++;
        $this->identifier = self::$id;
    }

    public function identifier(): string
    {
        return (string) $this->identifier;
    }

    public static function reset(): void
    {
        self::$id = 0;
    }
}
