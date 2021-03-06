<?php

declare(strict_types=1);

namespace YiiGraphQL\Validator\Rules;

use YiiGraphQL\Error\Error;
use YiiGraphQL\Language\AST\FragmentDefinitionNode;
use YiiGraphQL\Language\AST\NodeKind;
use YiiGraphQL\Language\AST\OperationDefinitionNode;
use YiiGraphQL\Language\Visitor;
use YiiGraphQL\Validator\ValidationContext;
use function sprintf;

class NoUnusedFragments extends ValidationRule
{
    /** @var OperationDefinitionNode[] */
    public $operationDefs;

    /** @var FragmentDefinitionNode[] */
    public $fragmentDefs;

    public function getVisitor(ValidationContext $context)
    {
        $this->operationDefs = [];
        $this->fragmentDefs  = [];

        return [
            NodeKind::OPERATION_DEFINITION => function ($node) {
                $this->operationDefs[] = $node;

                return Visitor::skipNode();
            },
            NodeKind::FRAGMENT_DEFINITION  => function (FragmentDefinitionNode $def) {
                $this->fragmentDefs[] = $def;

                return Visitor::skipNode();
            },
            NodeKind::DOCUMENT             => [
                'leave' => function () use ($context) {
                    $fragmentNameUsed = [];

                    foreach ($this->operationDefs as $operation) {
                        foreach ($context->getRecursivelyReferencedFragments($operation) as $fragment) {
                            $fragmentNameUsed[$fragment->name->value] = true;
                        }
                    }

                    foreach ($this->fragmentDefs as $fragmentDef) {
                        $fragName = $fragmentDef->name->value;
                        if (! empty($fragmentNameUsed[$fragName])) {
                            continue;
                        }

                        $context->reportError(new Error(
                            self::unusedFragMessage($fragName),
                            [$fragmentDef]
                        ));
                    }
                },
            ],
        ];
    }

    public static function unusedFragMessage($fragName)
    {
        return sprintf('Fragment "%s" is never used.', $fragName);
    }
}
