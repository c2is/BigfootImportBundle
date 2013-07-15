<?php

namespace Bigfoot\Bundle\ImportBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Imports settings
 * @Author: S.huot s.huot@c2is.fr
 */
class DataSourceType extends AbstractType
{
    /**
     * Set the form made up of a name, a domain, a port, a username and a password
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('domain')
            ->add('port')
            ->add('username')
            ->add('password')
        ;
    }

    /**
     * Set the default options
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Bigfoot\Bundle\ImportBundle\Entity\DataSource'
        ));
    }

    /**
     * Set the name
     *
     * @return string
     */
    public function getName()
    {
        return 'bigfoot_bundle_importbundle_datasourcetype';
    }
}
