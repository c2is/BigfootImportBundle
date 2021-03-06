<?php

namespace Bigfoot\Bundle\ImportBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Imports settings
 * @Author: S.huot s.huot@c2is.fr
 * @Author: S.Plançon s.plancon@c2is.fr
 */
class DataSourceType extends AbstractType
{
    /**
     * Set the form made up of a name, a protocol, a domain, a port, a username and a password
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('protocol', ProtocolType::class, array(
                'placeholder' => 'Choose protocol'
            ))
            ->add('domain')
            ->add('port')
            ->add('username')
            ->add('password')
        ;
    }

    /**
     * Set the default options
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
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
