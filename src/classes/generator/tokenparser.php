<?php

use PhUml\Code\Attribute;
use PhUml\Code\ClassDefinition;
use PhUml\Code\InterfaceDefinition;
use PhUml\Code\Method;
use PhUml\Code\Variable;

class plStructureTokenparserGenerator extends plStructureGenerator
{
    /** @var ClassDefinition[] */
    private $classes;

    /** @var InterfaceDefinition[] */
    private $interfaces;

    /** @var array */
    private $parserStruct;

    /** @var int */
    private $lastToken;

    public function createStructure(array $files): array
    {
        $this->initGlobalAttributes();
        foreach ($files as $file) {
            $this->initParserAttributes();
            $tokens = token_get_all(file_get_contents($file));
            $this->process($tokens);
            $this->storeClassOrInterface();
        }
        $this->fixObjectConnections();

        return array_merge($this->classes, $this->interfaces);
    }

    private function process(array $tokens): void
    {
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $this->processComplex(...$token);
            } else {
                $this->processSimple($token);
            }
        }
    }

    private function processSimple(string $token): void
    {
        switch ($token) {
            case '(':
                break;
            case ',':
                $this->resetTypeHint();
                break;
            case '=':
                $this->resetToken();
                break;
            case ')':
                $this->saveMethodDefinition();
                break;
            default:
                // Ignore everything else
                $this->lastToken = null;
        }
    }

    private function processComplex(int $type, string $value): void
    {
        switch ($type) {
            case T_WHITESPACE:
                break;
            case T_VAR:
            case T_ARRAY:
            case T_CONSTANT_ENCAPSED_STRING:
            case T_LNUMBER:
            case T_DNUMBER:
            case T_PAAMAYIM_NEKUDOTAYIM:
                $this->resetToken();
                break;
            case T_FUNCTION:
                $this->startMethodDefinition($type);
                break;
            case T_INTERFACE:
            case T_CLASS:
                $this->startClassOrInterfaceDefinition($type);
                break;
            case T_IMPLEMENTS:
            case T_EXTENDS:
                $this->startExtendsOrImplementsDeclaration($type);
                break;
            case T_VARIABLE:
                $this->saveAttributeOrParameter($value);
                break;
            case T_STRING:
                $this->saveIdentifier($value);
                break;
            case T_PUBLIC:
            case T_PROTECTED:
            case T_PRIVATE:
                $this->saveModifier($type, $value);
                break;
            case T_DOC_COMMENT:
                $this->saveDocBlock($value);
                break;
            default:
                // Ignore everything else
                $this->lastToken = null;
                // And reset the docblock
                $this->parserStruct['docblock'] = null;
        }
    }

    private function initGlobalAttributes(): void
    {
        $this->classes = [];
        $this->interfaces = [];
    }

    private function initParserAttributes(): void
    {
        $this->parserStruct = [
            'class' => null,
            'interface' => null,
            'function' => null,
            'attributes' => [],
            'functions' => [],
            'typehint' => null,
            'params' => [],
            'implements' => [],
            'extends' => null,
            'modifier' => 'public',
            'docblock' => null,
        ];

        $this->lastToken = [];
    }

    private function resetTypeHint(): void
    {
        $this->parserStruct['typehint'] = null;
    }

    private function resetToken(): void
    {
        if ($this->lastToken !== T_FUNCTION) {
            $this->lastToken = null;
        }
    }

    private function startMethodDefinition(int $type): void
    {
        switch ($this->lastToken) {
            case null:
            case T_PUBLIC:
            case T_PROTECTED:
            case T_PRIVATE:
                $this->lastToken = $type;
                break;
            default:
                $this->lastToken = null;
        }
    }

    private function startClassOrInterfaceDefinition(int $type): void
    {
        if ($this->lastToken === null) {
            // New initial interface or class token
            // Store the class or interface definition if there is any in the
            // parser arrays ( There might be more than one class/interface per
            // file )
            $this->storeClassOrInterface();

            // Remember the last token
            $this->lastToken = $type;
        } else {
            $this->lastToken = null;
        }
    }

    private function startExtendsOrImplementsDeclaration(int $type): void
    {
        if ($this->lastToken === null) {
            $this->lastToken = $type;
        } else {
            $this->lastToken = null;
        }
    }

    private function saveMethodDefinition(): void
    {
        if ($this->lastToken === T_FUNCTION) {
            // The function declaration has been closed

            // Add the current function
            $this->parserStruct['functions'][] = [
                $this->parserStruct['function'],
                $this->parserStruct['modifier'],
                $this->parserStruct['params'],
                $this->parserStruct['docblock']
            ];
            // Reset the last token
            $this->lastToken = null;
            //Reset the modifier state
            $this->parserStruct['modifier'] = 'public';
            // Reset the params array
            $this->parserStruct['params'] = [];
            $this->parserStruct['typehint'] = null;
            // Reset the function name
            $this->parserStruct['function'] = null;
            // Reset the docblock
            $this->parserStruct['docblock'] = null;
        } else {
            $this->lastToken = null;
        }
    }

    private function saveAttributeOrParameter(string $identifier): void
    {
        switch ($this->lastToken) {
            case T_PUBLIC:
            case T_PROTECTED:
            case T_PRIVATE:
                // A new class attribute
                $this->parserStruct['attributes'][] = [
                    $identifier,
                    $this->parserStruct['modifier'],
                    $this->parserStruct['docblock'],
                ];
                $this->lastToken = null;
                $this->parserStruct['modifier'] = 'public';
                $this->parserStruct['docblock'] = null;
                break;
            case T_FUNCTION:
                // A new function parameter
                $this->parserStruct['params'][] = [
                    $this->parserStruct['typehint'],
                    $identifier,
                ];
                break;
        }
    }

    private function saveIdentifier(string $identifier): void
    {
        switch ($this->lastToken) {
            case T_IMPLEMENTS:
                // Add interface to implements array
                $this->parserStruct['implements'][] = $identifier;
                // We do not reset the last token here, because
                // there might be multiple interfaces
                break;
            case T_EXTENDS:
                // Set the superclass
                $this->parserStruct['extends'] = $identifier;
                // Reset the last token
                $this->lastToken = null;
                break;
            case T_FUNCTION:
                // Add the current function only if there is no function name already
                // Because if we know the function name already this is a type hint
                if ($this->parserStruct['function'] === null) {
                    // Function name
                    $this->parserStruct['function'] = $identifier;
                } else {
                    // Type hint
                    $this->parserStruct['typehint'] = $identifier;
                }
                break;
            case T_CLASS:
                // Set the class name
                $this->parserStruct['class'] = $identifier;
                // Reset the last token
                $this->lastToken = null;
                break;
            case T_INTERFACE:
                // Set the interface name
                $this->parserStruct['interface'] = $identifier;
                // Reset the last Token
                $this->lastToken = null;
                break;
            default:
                $this->lastToken = null;
        }
    }

    private function saveModifier(int $type, string $modifier): void
    {
        if ($this->lastToken === null) {
            $this->lastToken = $type;
            $this->parserStruct['modifier'] = $modifier;
        } else {
            $this->lastToken = null;
        }
    }

    private function saveDocBlock(string $comment): void
    {
        if ($this->lastToken === null) {
            $this->parserStruct['docblock'] = $comment;
        } else {
            $this->lastToken = null;
            $this->parserStruct['docblock'] = null;
        }
    }

    private function storeClassOrInterface(): void
    {
        // First we need to check if we should store interface data found so far
        if ($this->parserStruct['interface'] !== null) {
            // Init data storage
            $functions = [];

            // Create the data objects
            foreach ($this->parserStruct['functions'] as $function) {
                // Create the needed parameter objects
                $params = [];
                foreach ($function[2] as $param) {
                    $params[] = new Variable($param[1], $param[0]);
                }
                $functions[] = new Method(
                    $function[0],
                    $function[1],
                    $params
                );
            }
            $interface = new InterfaceDefinition(
                $this->parserStruct['interface'],
                $functions,
                $this->parserStruct['extends']
            );

            // Store in the global interface array
            $this->interfaces[$this->parserStruct['interface']] = $interface;
        } // If there is no interface, we maybe need to store a class
        else if ($this->parserStruct['class'] !== null) {
            // Init data storage
            $functions = [];
            $attributes = [];

            // Create the data objects
            foreach ($this->parserStruct['functions'] as $function) {
                // Create the needed parameter objects
                $params = [];
                foreach ($function[2] as $param) {
                    $params[] = new Variable($param[1], $param[0]);
                }
                $functions[] = new Method(
                    $function[0],
                    $function[1],
                    $params
                );
            }
            foreach ($this->parserStruct['attributes'] as $attribute) {
                $type = null;
                // If there is a docblock try to isolate the attribute type
                if ($attribute[2] !== null) {
                    // Regular expression that extracts types in array annotations
                    $regexp = '/^[\s*]*@var\s+array\(\s*(\w+\s*=>\s*)?(\w+)\s*\).*$/m';
                    if (preg_match($regexp, $attribute[2], $matches)) {
                        $type = $matches[2];
                    } else if ($return = preg_match('/^[\s*]*@var\s+(\S+).*$/m', $attribute[2], $matches)) {
                        $type = trim($matches[1]);
                    }
                }
                $attributes[] = new Attribute(
                    $attribute[0],
                    $attribute[1],
                    $type
                );
            }
            $class = new ClassDefinition(
                $this->parserStruct['class'],
                $attributes,
                $functions,
                $this->parserStruct['implements'],
                $this->parserStruct['extends']
            );

            $this->classes[$this->parserStruct['class']] = $class;
        }

        $this->initParserAttributes();
    }

    private function fixObjectConnections(): void
    {
        foreach ($this->classes as $class) {
            $implements = [];
            foreach ($class->implements as $key => $impl) {
                $implements[$key] = array_key_exists($impl, $this->interfaces)
                    ? $this->interfaces[$impl]
                    : $this->interfaces[$impl] = new InterfaceDefinition($impl);
            }
            $class->implements = $implements;

            if ($class->extends === null) {
                continue;
            }
            $class->extends = array_key_exists($class->extends, $this->classes)
                ? $this->classes[$class->extends]
                : ($this->classes[$class->extends] = new ClassDefinition($class->extends));
        }
        foreach ($this->interfaces as $interface) {
            if ($interface->extends === null) {
                continue;
            }
            $interface->extends = array_key_exists($interface->extends, $this->interfaces)
                ? $this->interfaces[$interface->extends]
                : ($this->interfaces[$interface->extends] = new InterfaceDefinition($interface->extends));
        }
    }
}
