<?php

namespace Bigfoot\Bundle\ImportBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class BigfootImportExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $nb_ligne_par_lot = (isset($config['nb_ligne_par_lot']['ftp']['csv'])) ? $config['nb_ligne_par_lot']['ftp']['csv'] : null;

        $container->setParameter('nb_ligne_par_lot.ftp.csv', $nb_ligne_par_lot);
        $container->setParameter('import.max_execution_time', $config['max_execution_time']);
    }
}
