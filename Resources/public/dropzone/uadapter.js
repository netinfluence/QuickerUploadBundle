// Disabling autoDiscover, otherwise Dropzone will try to attach twice.
Dropzone.autoDiscover = false;

$(function() {
    $('.ni-ub').each(function() {
        var $form = $(this);
        var $dropzone = $form.find('.ni-ub-dz');

        // we go read options from HTML data-* attributes
        var options = $dropzone.data();

        // But we force some
        $.extend(options, {
            fallback: null,
            paramName: 'file'
        });

        var dropzone = new Dropzone('#'+$dropzone.attr('id'), options);

        dropzone.on('error', function(file, errorMessage) {
            // Small inconsistency within Dropzone!
            if (typeof errorMessage === "object") {
                errorMessage = errorMessage.error;
            }

            $form.find('.ni-ub-error').html(errorMessage);

            dropzone.removeFile(file);
        });

        dropzone.on('addedfile', function() {
            $form.find('.ni-ub-error').html('');
        });

        /*
            Some handling is different depending whether under we have a single file
            or a collection
         */

        var isCollection = ($form.find('[data-collection]') !== []);
        var successHandler;
        var removeHandler;

        if (isCollection) {
            successHandler = function(file, response) {
                var $collection = $form.find('[data-collection]');
                var prototype = $collection.data('prototype');
                var number = $collection.length;
                var newChild = prototype.replace(/__name__/g, number);

                var $newChild = $(newChild).appendTo($collection);

                $newChild.find('input[data-path]').val(response.path);
                $newChild.find('input[data-temporary]').val(1);

                // Store some file properties we will reuse later
                file.ub = {
                    formId: $newChild.attr('id'),
                    number: response.number,
                    path: response.path,
                    temporary: true
                }
            };

            removeHandler = function(file) {
                // Temporary files must be removed from server sandbox
                if (file.ub.temporary) {
                    $.ajax(options.removeUrl, {
                        data: {
                            path: file.ub.path
                        }
                    });
                }
                // Non temporary will be removed via traditional form workflow

                $form.find('#'+file.ub.formId).remove();
            };
        } else {
            successHandler = function(file, response) {
                $form.find('input[data-path]').val(response.path);

                // Anyway a temporary file was stored
                $form.find('input[data-temporary]').val(1);

                // Store some file properties we will reuse later
                file.ub = {
                    path: response.path,
                    temporary: true
                }
            };

            removeHandler = function(file) {
                // Temporary files must be removed from server sandbox
                if (file.ub.temporary) {
                    $.ajax(options.removeUrl, {
                        data: {
                            path: file.ub.path
                        }
                    });
                }
                // Non temporary will be removed via traditional form workflow

                $form.find('input[data-path]').val('');
                $form.find('input[data-temporary]').val('');
            };
        }

        dropzone.on('success', successHandler);
        dropzone.on('removedfile', removeHandler);
    });
});
