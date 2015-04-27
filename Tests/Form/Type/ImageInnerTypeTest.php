<?php

namespace Netinfluence\UploadBundle\Tests\Form\Type;

use Netinfluence\UploadBundle\Form\Type\ImageInnerType;
use Netinfluence\UploadBundle\Form\Type\ImageType;
use Netinfluence\UploadBundle\Model\FormFile;
use Netinfluence\UploadBundle\Validation\ImageConstraints;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ImageInnerTypeTest
 */
class ImageInnerTypeTest extends TypeTestCase
{
    /**
     * @var ImageInnerType
     */
    private $sut;

    public function setUp()
    {
        parent::setUp();

        $thumbnailGenerator = \Phake::mock('Netinfluence\UploadBundle\Generator\ThumbnailGeneratorInterface');
        \Phake::when($thumbnailGenerator)->getUrl($this->anything(), array(120, 90))->thenReturn('url/thumbnail.jpg');

        $constraints = new ImageConstraints(array());

        $this->sut = new ImageInnerType($thumbnailGenerator, $constraints);
    }

    public function tearDown()
    {
        $this->sut = null;
    }

    public function test_it_set_default_options()
    {
        $resolver = new OptionsResolver();
        $this->sut->setDefaultOptions($resolver);

        $options = $resolver->resolve();

        $this->assertEquals(false, $options['required']);
        $this->assertEquals(ImageType::DEFAULT_DATA_CLASS, $options['data_class']);
    }

    public function test_it_validates_data_class_option()
    {
        $resolver = new OptionsResolver();
        $this->sut->setDefaultOptions($resolver);

        $options =  $resolver->resolve(array(
            'data_class' => 'Netinfluence\UploadBundle\Model\FormFile'
        ));

        $this->assertEquals('Netinfluence\UploadBundle\Model\FormFile', $options['data_class']);

        $this->setExpectedException('\Exception');

        $resolver->resolve(array(
            'data_class' => 'Netinfluence\UploadBundle\Model\TemporaryFile'
        ));
    }

    public function test_it_submits_data_to_temporary_form_file()
    {
        $form = $this->factory->create($this->sut);

        $form->submit(array(
            'path' => 'some/path/image.jpg',
            'temporary' => 1,
        ));

        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();
        $this->assertEquals('Netinfluence\UploadBundle\Model\FormFile', get_class($data));
        $this->assertEquals('some/path/image.jpg', $data->getPath());
        $this->assertEquals(true, $data->isTemporary());
    }

    public function test_it_submits_data_to_final_form_file_by_default()
    {
        $form = $this->factory->create($this->sut);

        $form->submit(array(
            'path' => 'some/path/image.jpg',
            // nothing: we should use the default value
        ));

        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();
        $this->assertEquals('Netinfluence\UploadBundle\Model\FormFile', get_class($data));
        $this->assertEquals('some/path/image.jpg', $data->getPath());
        $this->assertEquals(false, $data->isTemporary());
    }

    public function test_it_generates_thumbnail_url()
    {
        $file = new FormFile();
        $file->setPath('url/img.jpg');

        $form = $this->factory->create($this->sut, $file, array(
            'thumbnail_height' => 90,
            'thumbnail_width'  => 120,
        ));

        $view = $form->createView();

        $this->assertEquals('url/thumbnail.jpg', $view->vars['thumbnail_url']);

        // those should also be displayed in the view
        $this->assertEquals(90, $view->vars['thumbnail_height']);
        $this->assertEquals(120, $view->vars['thumbnail_width']);

        $this->assertInstanceOf('Netinfluence\UploadBundle\Validation\ImageConstraints', $view->vars['image_constraints']);
    }
}
