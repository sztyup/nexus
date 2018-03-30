<?php

namespace App\Doctrine;

use Illuminate\Support\Str;
use LaravelDoctrine\ORM\Configuration\LaravelNamingStrategy;

class NamingStrategy extends LaravelNamingStrategy
{
    public function classToTableName($className)
    {
        $site = $this->getSiteNameFromEntityClass($className);

        if ($site) {
            return Str::slug($site) . '_' . parent::classToTableName($className);
        }

        return parent::classToTableName($className);
    }

    private function getSiteNameFromEntityClass($className)
    {
        $className = is_object($className) ? get_class($className) : $className;

        $parts = explode('\\', $className);
        $ns = array_slice($parts, -2, 1)[0];

        if ($ns == 'Entities') {
            return null;
        } else {
            return $ns;
        }
    }
}
