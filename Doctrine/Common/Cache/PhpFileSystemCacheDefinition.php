<?php
/**
 * Created by PhpStorm.
 * User: mikus
 * Date: 2017.04.24.
 * Time: 9:47
 */

namespace AppBundle\Doctrine\Common\Cache;

use Doctrine\Bundle\DoctrineCacheBundle\DependencyInjection\Definition\CacheDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PhpFileSystemCacheDefinition extends CacheDefinition
{
    /**
     * {@inheritDoc}
     */
    public function configure($name, array $config, Definition $service, ContainerBuilder $container)
    {
        $provider = $config['custom_provider'];
        $args = array();

        if ($provider['type'] == "php_file_system") {
            $args[] = $provider['options']['directory'];
            $args[] = $provider['options']['extension'];

            if (array_key_exists('umask', $provider['options'])) {
                $args[] = $provider['options']['umask'];
            }

            $service->setArguments($args);
        }
    }
}