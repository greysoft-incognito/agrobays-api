<?php

namespace App\Actions;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Scalar\LNumber;
use Winter\LaravelConfigWriter\ArrayFile as ArrayFileBase;

class ArrayFile extends ArrayFileBase
{
    public const SORT_ASC = 'asc';
    public const SORT_DESC = 'desc';

    /**
     * Generate an AST node, using `PhpParser` classes, for a value
     *
     * @param mixed $value
     * @throws \RuntimeException If $type is not one of 'string', 'boolean', 'integer', 'function', 'const', 'null', or 'array'
     * @return ConstFetch|LNumber|String_|Array_|FuncCall
     */
    protected function makeAstNode(string $type, $value)
    {
        switch (strtolower($type)) {
            case 'string':
                return new String_($value);
            case 'boolean':
                return new ConstFetch(new Name($value ? 'true' : 'false'));
            case 'integer':
                return new LNumber($value);
            case 'double':
                return new DNumber($value);
            case 'function':
                return new FuncCall(
                    new Name($value->getName()),
                    array_map(function ($arg) {
                        return new Arg($this->makeAstNode($this->getType($arg), $arg));
                    }, $value->getArgs())
                );
            case 'const':
                return new ConstFetch(new Name($value->getName()));
            case 'null':
                return new ConstFetch(new Name('null'));
            case 'array':
                return $this->castArray($value);
            default:
                throw new \RuntimeException("An unimlemented replacement type ($type) was encountered");
        }
    }
}
