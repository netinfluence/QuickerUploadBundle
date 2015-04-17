<?php

namespace Netinfluence\UploadBundle\Form\Type;

use Netinfluence\UploadBundle\Form\DataTransformer\BooleanToHiddenTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ImageType
 */
class ImageType extends AbstractType
{
    /**
     * By default, behind the form we will have an instance of this object
     */
    const DEFAULT_DATA_CLASS = 'Netinfluence\UploadBundle\Model\FormFile';

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('path', 'hidden')
            ->add(
                $builder
                    // this more complex syntax is required when adding a transformer
                    ->create('temporary', 'hidden', array(
                        'empty_data' => false
                    ))
                    // We add a transformer to be sure there is no type screw-up
                    ->addViewTransformer(new BooleanToHiddenTransformer())
            )
        ;
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => self::DEFAULT_DATA_CLASS,
            'required'  => false
        ));

        $dataClass = self::DEFAULT_DATA_CLASS;

        // Important: use 2.3 syntax, passing an array, it will convert internally
        $resolver->setAllowedValues(array(
            'data_class' => function($value) use ($dataClass) {
                if (! is_a($value, $dataClass, true)) {
                    // We throw an Exception for a more precise feedback than OptionResolver one
                    throw new \Exception(sprintf(
                        'Form type "netinfluence_upload_image" must be mapped to objects implementing Netinfluence\\UploadBundle\\Model\\UplodableInterface. Wrong value "%s" received for "data_class".',
                        $dataClass
                    ));
                }

                return true;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'netinfluence_upload_image';
    }
}
