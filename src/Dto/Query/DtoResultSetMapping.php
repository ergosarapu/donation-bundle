<?php

namespace ErgoSarapu\DonationBundle\Dto\Query;

use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;

class DtoResultSetMapping
{
    /**
     * @param class-string $dtoClassName
     */
    public function __construct(private string $dtoClassName)
    {
    }

    public function getMapping(): ResultSetMapping
    {
        $class = new \ReflectionClass($this->dtoClassName);
        $constructor = $class->getConstructor();

        if (null === $constructor) {
            throw new \Exception('Constructor is null');
        }

        $rsm = new ResultSetMapping();
        $i = 0;
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!($type instanceof \ReflectionNamedType)) {
                throw new \Exception();
            }
            $type = $type->getName();
            if ('int' === $type) {
                $type = 'integer';
            } elseif ('bool' === $type) {
                $type = 'boolean';
            } elseif ('DateTime' === $type) {
                $type = 'date';
            }
            $rsm->addScalarResult($parameter->getName(), $i + 1, $type);
            $rsm->newObjectMappings[$parameter->getName()] = [
                'className' => $this->dtoClassName,
                'objIndex' => 0,
                'argIndex' => $i,
            ];
            ++$i;
        }

        return $rsm;
    }   
}
