# NetinfluenceUploadBundle

User and developer friendly file upload.

Features:

 * a nice AJAX-powered async upload powered by [DropzoneJS](http://www.dropzonejs.com/)
 * **totally integrated with Symfony2 forms** and transparent to use!
 * of course files are properly validated
 * multiple files upload too
 * files are uploaded to any storage of your choice (local filesystem, Amazon...) using [Gaufrette](https://github.com/KnpLabs/KnpGaufretteBundle)
 * files are stored in a sandbox first
 * integrates with Doctrine ORM: files are moved from sandbox upon entity save
 * when coming back to the form, files can be removed
 * when needed, thumbnails are generated using [LiipImagineBundle](https://github.com/liip/LiipImagineBundle)
 * internationalized in English and French, you can easily add more
 * very easily overridable and customizable, if you don"t want any of the above
 
[![Latest Stable Version](https://poser.pugx.org/netinfluence/upload-bundle/v/stable)](https://packagist.org/packages/netinfluence/upload-bundle)
[![Build Status](https://travis-ci.org/netinfluence/NetinfluenceUploadBundle.svg?branch=master)](https://travis-ci.org/netinfluence/NetinfluenceUploadBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/38fb65d4-d690-4abf-b370-da4aaf1a4b0f/mini.png)](https://insight.sensiolabs.com/projects/38fb65d4-d690-4abf-b370-da4aaf1a4b0f)
 
## Getting started

### Installing the bundle

Fetch this bundle using composer:
`composer.phar require netinfluence/upload-bundle`

Enable it and its vendors in `app/AppKernel.php`:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            
            new Netinfluence\UploadBundle\NetinfluenceUploadBundle(),
            new Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
            new Liip\ImagineBundle\LiipImagineBundle(),
        );

        // ...
    }
}
```

Also import its routing file, edit `app/config/routing.yml`:
```yml
# app/config/routing.yml
# ...
netinfluence_upload:
    resource: "@NetinfluenceUploadBundle/Resources/config/routing.xml"
    
_liip_imagine:
    resource: "@LiipImagineBundle/Resources/config/routing.xml"
```

### Setting Gaufrette filesystems

You have to set up where files are stored. This bundle relies on Gaufrette filesystems abstractions. In doubt refer to its [documentation](https://github.com/KnpLabs/KnpGaufretteBundle)  

You need 2 different filesystems:

 * `sandbox` will be used for temporary files - ie. files corresponding to uploads on forms not yet validated. Typically this is a local folder, as data is removed after being uploaded to final filesystem, only limited space is required.
 * `final` is the ultimate files destination. You could be using another folder, or Amazon S3...

You need to provide UploadBundle ID of valid `filesystem`. 
Note that those ID are generated in the form `gaufrette.ADAPTER_NAME_filesystem`.

Last thing, you need to configure LiipImagine bundle.
In our example, we use their default configuration, which is compatible with having images files stored in `web/` and cache thumbnails will be stored in `web/media/cache`.
Otherwise, for instance for using Amazon S3, make sure to check their [documentation](http://symfony.com/doc/master/bundles/LiipImagineBundle/index.html).

Here is a full working example:
```yml
# app/config/config.yml
# Setting up storage options
knp_gaufrette:
    adapters:
        temporary_folder:
            # here we use a local folder
            # be sure to have permission set up correctly!
            local:
                directory: "%kernel.root_dir%/../web/tmp"
                create: true
        storage_folder:
            # and we use another local folder
            local:
                directory: "%kernel.root_dir%/../web/uploads"
                create: true
    filesystems:
        sandbox:
            adapter:    temporary_folder
        storage:
            adapter:    storage_folder
   
# Passing those to our bundle
netinfluence_upload:
    filesystems:
        sandbox: gaufrette.sandbox_filesystem
        final: gaufrette.storage_filesystem
    validation: ~ # mandatory node

# Using default LiipImagine bundle config
liip_imagine:
    filter_sets:
        # you need to define the filter we will use to generate thumbnails
        # you can optionally customize it
        ni_ub_thumbnail: ~
```

### Getting started

Just use `netinfluence_upload_image` form type: 
```php
<?php
namespace Netinfluence\DemoBundle\Controller;

// ...

class MyController extends Controller
{
    public function formAction(Request $request)
    {
        // ...

        $form = $this->createFormBuilder()
            ->add('photo', 'netinfluence_upload_image')
            ->getForm()
        ;
        
        // ...
    }
}
```

*Of course you could also be using a Form class. Here we stick to a simple inline form example.* 

On your form page, include provided JS and CSS:
```jinja
{# if you don't have jQuery, you will need it too #}
<script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>

<link href="{{ asset('bundles/netinfluenceupload/dropzone/dropzone.min.css') }}" rel="stylesheet" media="screen" />
<script type="text/javascript" src="{{ asset('bundles/netinfluenceupload/dropzone/dropzone.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('bundles/netinfluenceupload/dropzone/uadapter.js') }}"></script>
```

Congrats! A nice javascript and AJAX-powered picker for one file only will be displayed.

### Fetching data back from the Form

#### Default setting

By default, you receive an instance of `Netinfluence\UploadBundle\Model\FormFile`: 
```php
<?php

// ...
class MyController extends Controller
{
    public function formAction(Request $request)
    {
        // ...

        $form = $this->createFormBuilder()
            ->add('photo', 'netinfluence_upload_image')
            ->getForm()
        ;
        
         if ($form->handleRequest($request) && $form->isValid()) {
            $file = $form->getData()['photo'];
            
            // Netinfluence\UploadBundle\Model\FormFile
            var_dump(get_class($file));
            
            // ...
         }
        
        // ...
    }
}
```

#### Using your own objects (preferred way)

Given that your objects implements `Netinfluence\UploadBundle\Model\UploadableInterface`, you can map those directly to the form:
```php
<?php
namespace Netinfluence\DemoBundle\Entity;

use Netinfluence\UploadBundle\Model\UploadableInterface;

class MyFile implements UploadableInterface
{
    /**
     * @var string path to file, as used by Gaufrette FS
     */
    protected $path;

    /**
     * @var boolean is this path temporary (to sandbox) or not
     * Note that this field does not need to be persisted to a DB or anything
     */
    protected $temporary;

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function isTemporary()
    {
        return $this->temporary;
    }

    public function setTemporary($temporary)
    {
        $this->temporary = $temporary;
    }
}
```

If you are using PHP >= 5.4, to simplify you could also be using `Netinfluence\UploadBundle\Model\MaybeTemporaryFileTrait`:
```php
<?php
namespace Netinfluence\DemoBundle\Entity;

use Netinfluence\UploadBundle\Model\UploadableInterface;
use Netinfluence\UploadBundle\Model\MaybeTemporaryFileTrait;

class MyFile implements UploadableInterface
{
    use MaybeTemporaryFileTrait;

    /**
     * @var string path to file, as used by Gaufrette FS
     */
    protected $path;

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }
}
```

And finally the controller example code:
```php
<?php
namespace Netinfluence\DemoBundle\Controller;

// ...
class MyController extends Controller
{
    public function formAction(Request $request)
    {
        // ...

        $form = $this->createFormBuilder()
            ->add('photo', 'netinfluence_upload_image', array(
                'data_class' => 'Netinfluence\DemoBundle\Entity\MyFile'
            ))
            ->getForm()
        ;
        
         if ($form->handleRequest($request) && $form->isValid()) {
            $file = $form->getData()['photo'];
            
            // Netinfluence\DemoBundle\Entity\MyFile
            var_dump(get_class($file));
            
            // ...
         }
        
        // ...
    }
}
```

### Storing and removing files

#### When using Doctrine + UploadableInterface

If you are using Doctrine ORM, and you entity implements `Netinfluence\UploadBundle\Model\UploadableInterface`, 
a listener provided by this bundle will automatically takes care of all of uploading teh file to the final filesystem and 
of removing from the sandbox filesystem on success.

In the same fashion, file removal is automatic after entity removal.

#### Manually

If you don't use Doctrine ORM, but want to persist `Netinfluence\UploadBundle\Model\UploadableInterface` file, call the `netinfluence_upload.file_manager` service:
```php
<?php
namespace Netinfluence\DemoBundle\Controller;

// ...
class MyController extends Controller
{
    public function formAction(Request $request)
    {
        // ...

        $form = $this->createFormBuilder()
            ->add('photo', 'netinfluence_upload_image', array(
                'data_class' => 'Netinfluence\DemoBundle\Entity\MyFile'
            ))
            ->getForm()
        ;
        
         if ($form->handleRequest($request) && $form->isValid()) {
            $file = $form->getData()['photo'];
            
            // FileManager will remove temporary file from sandbox,
            // and save it to the final one
            $this->get('netinfluence_upload.file_manager')->persist($file);
            
            // And if you want to remove:
            $this->get('netinfluence_upload.file_manager')->remove($file);
            
         }
        
        // ...
    }
}
```

## Multiple files upload

Use `netinfluence_upload_image_collection` form type instead.
```php
<?php
namespace Netinfluence\DemoBundle\Controller;

// ...

class MyController extends Controller
{
    public function formAction(Request $request)
    {
        // ...

        $form = $this->createFormBuilder()
            ->add('photos', 'netinfluence_upload_image_collection')
            ->getForm()
        ;
        
         if ($form->handleRequest($request) && $form->isValid()) {
            $files = $form->getData()['photos'];
            
            // this in an array
            var_dump(is_array($files));
            
            // Netinfluence\UploadBundle\Model\FormFile
            var_dump(get_class($files[0]));
            
            // ...
         }
    }
}
```

You can set a maximum number of files through `max_files`. By default it's 0 (unlimited).
It mimics Symfony2 `collection` type, the child being an `netinfluence_upload_image` type field, you can pass options to it: 
```php
<?php
namespace Netinfluence\DemoBundle\Controller;

// ...

class MyController extends Controller
{
    public function formAction(Request $request)
    {
        // ...

        $form = $this->createFormBuilder()
            ->add('photo', 'netinfluence_upload_image_collection', array(
                'max_files' => 3,
                'options'   => array(
                    'data_class' => 'Netinfluence\DemoBundle\Entity\MyFile'
                )
            ))
            ->getForm()
        ;
        
        if ($form->handleRequest($request) && $form->isValid()) {
            $files = $form->getData()['photos'];
        
            // this in an array of max 3 items
            var_dump(is_array($files));
        
            // Netinfluence\DemoBundle\Entity\MyFile
            var_dump(get_class($files[0]));
        
            // ...
        }
    }
}
```

## Add extra fields

You can modify the `FormBuilder` by providing a callable as `extra_fields` option:
```php
<?php
namespace Netinfluence\DemoBundle\Controller;

use Symfony\Component\Form\FormBuilderInterface;

// ...
class MyController extends Controller
{
    public function formAction(Request $request)
    {
        // ...

        $form = $this->createFormBuilder()
            ->add('photo', 'netinfluence_upload_image', array(
                'data_class'    => 'Netinfluence\DemoBundle\Entity\MyFile',
                'extra_fields   => function(FormBuilderInterface $builder, array $options) {
                    // Let's imagine your "MyFile" entity has a "number" field
                    $builder->add('number', 'hidden');
                }
            ))
            ->getForm()
        ;
        
         if ($form->handleRequest($request) && $form->isValid()) {
            $file = $form->getData()['photo'];
            
            // FileManager will remove temporary file from sandbox,
            // and save it to the final one
            $this->get('netinfluence_upload.file_manager')->persist($file);
            
            // And if you want to remove:
            $this->get('netinfluence_upload.file_manager')->remove($file);
            
         }
        
        // ...
    }
}
```

## Modifying uploaded file

You can manipulate the file about to be uploaded listening to the `FILE_VALIDATED_EVENT`, which is raised when the file sent by Ajax is successfully validated.
A listener with a priority of 50 will store file in sandbox, so you have to set an higher priority to be called before.

```php
<?php

namespace Netinfluence\DemoBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Netinfluence\UploadBundle\Event\Events;
use Netinfluence\UploadBundle\Event\TemporaryFileEvent;

/**
 * Class UploadFilter
 */
class UploadFilter implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            // We are called BEFORE the temporary file is stored
            Events::TEMPORARY_FILE_VALIDATED_EVENT => ['onFileValidated', 100]
        ];
    }

    /**
     * Store a file when it is validated
     *
     * @param TemporaryFileEvent $event
     * @throws \Exception
     */
    public function onFileValidated(TemporaryFileEvent $event)
    {
        $temporaryFile = $event->getTemporaryFile();
        $symfonyFile = $temporaryFile->getFile();
        $path = $symfonyFile->getPathname();

        // Here you can modify file at $path...
    }
}
```

```yml
# src/Netinfluence/DemoBundle/Resources/config/services.yml
services:
    netinfluence_storage.upload_filter:
        class: Netinfluence\DemoBundle\EventListener\UploadFilter
        tags:
            - { name: kernel.event_subscriber }
```

## Changing thumbnails size

Because changing images thumbnails size has a lot of consequences (most notably changing Imagine `ni_ub_thumbnail` filter), it must be done through form options:
```php
<?php
namespace Netinfluence\DemoBundle\Controller;

// ...

class MyController extends Controller
{
    public function formAction(Request $request)
    {
        // ...

        $form = $this->createFormBuilder()
            ->add('photo', 'netinfluence_upload_image_collection', array(
                'thumbnail_height' => 120, // default value
                'thumbnail_width' => 120 // default value
            ))
            ->getForm()
        ;
             
        // ...
    }
}
```

## Note about security

This bundle expose a few routes for uploading/deleting files.
If your form is within a private admin, you may want to restrict access to those routes too: 
```yml
# app/config/security.yml
security:
    access_control:
        - { path: ^/upload/, role: IS_AUTHENTICATED_REMEMBERED }
```

## Configuration reference

Here is the full bundle configuration, filled with the default values:
```yml
# app/config/config.yml
netinfluence_upload:
    filesystems:
        sandbox: # you should provide here a valid Gaufrette filesystem ID
        final: # you should provide here a valid Gaufrette filesystem ID too
    validation:
        # Validations rules applied to uploaded images
        image:
            # here you can use any Symfony2 constraint or custom one
            # Watch out, anything set here will override all default values so you may want to recopy those
            NotNull: ~
            Image:
                maxSize: 10M
                mimeTypes: ['image/gif', 'image/jpg', 'image/jpeg', 'image/png', 'image/bmp', 'image/x-windows-bmp']
    # Whether to overwrite or not files in Final filesystem. True is advised, as you may have files sent twice.
    overwrite: true
```
 
## Internals: File upload workflow

The whole bundle workflow can be summarised as this:

 * user is presented with a javascript upload input
 * when picking one or many files, those are sent to the server via AJAX
 * files are validated using rules from bundle config
 * if successful, they are stored in the `sandbox` filesystem
 * some data is sent back and placed in the form
 * when the form is finally submitted and valid, you will receive `FormFile` objects
 * you can now handle those yourself 
