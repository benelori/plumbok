<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: brzuchal
 * Date: 18.12.16
 * Time: 17:31
 */
namespace Plumbok\Compiler\Code;

use Doctrine\Common\Annotations\DocParser;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\Mixed_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property as ClassProperty;

/**
 * Class PropertyReader
 * @package Plumbok\Compiler\Code
 * @author Michał Brzuchalski <michal.brzuchalski@gmail.com>
 */
class PropertyReader
{
    /**
     * @var DocParser
     */
    private $parser;
    /**
     * @var Context
     */
    private $context;

    /**
     * PropertyReader constructor.
     * @param DocParser $parser
     * @param Context $context
     */
    public function __construct(DocParser $parser, Context $context)
    {
        $this->parser = $parser;
        $this->context = $context;
    }

    /**
     * @param ClassProperty $property
     * @return array
     */
    public function readAnnotations(ClassProperty $property) : array
    {
        return $property->getDocComment() ? $this->parser->parse($property->getDocComment()->getText()) : [];
    }

    /**
     * @param ClassProperty $property
     * @return DocBlock
     */
    public function readDocBlock(ClassProperty $property) : DocBlock
    {
        return DocBlockFactory::createInstance()->create((string)$property->getDocComment(), $this->context);
    }

    /**
     * @param ClassProperty[] $classProperties
     * @param ClassMethod[] $classMethods
     * @return array|Property[]
     */
    public function readProperties($classProperties, $classMethods) : array
    {
        $properties = [];
        foreach ($classProperties as $property) {
            $propertyDocBlock = $this->readDocBlock($property);
            /** @var DocBlock\Tags\Var_[] $varTags */
            if (count($varTags = $propertyDocBlock->getTagsByName('var'))) {
                $type = $varTags[0]->getType();
            }
            foreach ($property->props as $prop) {
                $setter = '';
                foreach ($classMethods as $method) {
                    if ($method->name->name == 'set' . ucfirst($prop->name->name)) {
                        $setter = $method->name->name;
                    }
                }
                $properties[] = new Property($prop->name->name, $type ?? new Mixed_(), $prop->default !== null, $setter, $this->readAnnotations($property));
            }
        }

        return $properties;
    }
}
