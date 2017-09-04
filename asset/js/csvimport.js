(function ($) {
    var activeElement = null;

    $(document).ready(function() {
        var actionsHtml = '<ul class="actions"><li><a aria-label="'
            + Omeka.jsTranslate('Remove mapping') + '" title="' + Omeka.jsTranslate('Remove mapping') + '" class="o-icon-delete remove-mapping" href="#" style="display: inline;"></a></li><li><a aria-label="'
            + Omeka.jsTranslate('Undo remove mapping') + '" title="' + Omeka.jsTranslate('Undo remove mapping') + '" class="o-icon-undo restore-mapping" href="#" style="display: none;"></a></li></ul>';

        $('#property-selector li.selector-child').on('click', function(e){
            e.stopPropagation();
            //looks like a stopPropagation on the selector-parent forces
            //me to bind the event lower down the DOM, then work back
            //up to the li
            var targetLi = $(e.target).closest('li.selector-child');

            //first, check if the property is already added
            var hasMapping = activeElement.find('ul.mappings li[data-property-id="' + targetLi.data('property-id') + '"]');
            if (hasMapping.length === 0) {
                var elementId = activeElement.data('element-id');
                var newInput = $('<input type="hidden" name="column-property[' + elementId + '][]" ></input>');
                newInput.val(targetLi.data('property-id'));
                var newMappingLi = $('<li class="mapping" data-property-id="' + targetLi.data('property-id') + '">' + targetLi.data('child-search') + actionsHtml  + '</li>');
                newMappingLi.append(newInput);
                activeElement.find('ul.mappings').append(newMappingLi);
                Omeka.closeSidebar($(this));
            }
        });

        $('.sidebar-close').on('click', function() {
            $('tr.mappable.active').removeClass('active');
        });

        $('#resource-class-selector li.selector-child').on('click', function(e){
            e.stopPropagation();
            //looks like a stopPropagation on the selector-parent forces
            //me to bind the event lower down the DOM, then work back
            //up to the li
            var targetLi = $(e.target).closest('li.selector-child');
            if (activeElement == null) {
                alert(Omeka.jsTranslate('Select an item type at the left before choosing a resource class.'));
            } else {
                //first, check if a class is already added
                //var hasMapping = activeElement.find('ul.mappings li');
                activeElement.find('ul.mappings li').remove();
                activeElement.find('input').remove();
                //hasMapping.remove();
                var typeId = activeElement.data('item-type-id');
                var newInput = $('<input type="hidden" name="type-class[' + typeId + ']" ></input>');
                newInput.val(targetLi.data('class-id'));
                activeElement.find('td.mapping').append(newInput);
                activeElement.find('ul.mappings').append('<li class="mapping" data-class-id="' + targetLi.data('class-id') + '">' + targetLi.data('child-search') + '</li>');
            }
        });

        // Clear default mappings
        $('body').on('click', '.clear-defaults', function(e) {
            e.stopPropagation();
            e.preventDefault();
            var fieldset = $(this).parents('fieldset');
            fieldset.find('li.mapping.default').remove();
        });

        // Remove mapping
        $('.section').on('click', 'a.remove-mapping', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var mappingToRemove = $(this).parents('li.mapping');
            mappingToRemove.find('input').prop('disabled', true);
            mappingToRemove.addClass('delete');
            mappingToRemove.find('.restore-mapping').show();
            $(this).hide();
        });

        // Restore a removed mapping
        $('.section').on('click', 'a.restore-mapping', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var mappingToRemove = $(this).parents('li.mapping');
            mappingToRemove.find('.remove-mapping').show();
            mappingToRemove.find('span.restore-mapping').hide();
            mappingToRemove.find('input').prop('disabled', false);
            mappingToRemove.removeClass('delete');
            $(this).hide();
        });

        $('.sidebar-chooser').on('click', 'a', function(e) {
            e.preventDefault();
            $('.property-mapping input.value-language').val('');
            if (activeElement !== null) {
                activeElement.removeClass('active');
            }
            activeElement = $(e.target).closest('tr.mappable');
            activeElement.addClass('active');
            if (activeElement.hasClass('element')) {
                $('#resource-class-selector').removeClass('active');
            }

            if (activeElement.hasClass('item-type')) {
                $('#resource-class-selector').addClass('active');
                $('#property-selector').removeClass('active');
            }

            var actionElement = $(this);
            $('.sidebar-chooser li').removeClass('active');
            actionElement.parent().addClass('active');
            var target = '#' + actionElement.data('sidebar');

            var sidebar = $(target);
            var columnName = actionElement.data('column');
            if (sidebar.find('.column-name').length > 0) {
                $('.column-name').text(columnName);
            } else {
                sidebar.find('h3').append('<span class="column-name">' + columnName + '</span>');
            }

            var currentSidebar = $('.sidebar.active');
            if (currentSidebar.attr('id') != target) {
                currentSidebar.removeClass('active');
            }
            Omeka.openSidebar(sidebar);
        });

        $('.sidebar.flags li').on('click', function(e){
            // hijack the multivalue option because it is handled separately below
            var targetLi = $(e.target).closest('li');
            if (targetLi.hasClass('column-multivalue')) {
                return;
            }
            e.stopPropagation();
            e.preventDefault();
            //looks like a stopPropagation on the selector-parent forces
            //me to bind the event lower down the DOM, then work back
            //up to the li

            if (activeElement == null) {
                alert(Omeka.jsTranslate('Select an element at the left before choosing a property.'));
            } else {
                var flagName = targetLi.find('span').text();
                var flagType = targetLi.data('flag-type');
                if (! flagType) {
                    flagType = targetLi.data('flag');
                }
                //first, check if the flag is already added
                //or if there is already any media mapping
                var hasFlag = activeElement.find('ul.mappings li.' + flagType);
                if (hasFlag.length === 0) {
                    var elementId = activeElement.data('element-id');
                    //elementId, or index? @TODO: check the naming conventions
                    //much is copied from Omeka2Importer, and might need clarification

                    var index = elementId;
                    var name = targetLi.data('flag') + "[" + index + "]";
                    //special handling for Media, which can add flags for different media types
                    var value;
                    if (flagType == 'media') {
                        name = 'media[' + index + ']';
                        value = targetLi.data('flag');
                    } else {
                        value = 1;
                    }
                    var newInput = $('<input type="hidden" name="' + name + '" value="' + value +'" ></input>');
                    var newMappingLi = $('<li class="mapping ' + flagType + '">' + flagName  + actionsHtml  + '</li>');
                    newMappingLi.append(newInput);
                    activeElement.find('ul.mappings').append(newMappingLi);
                    Omeka.closeSidebar($(this));
                }
            }
        });

        $('.sidebar').on('click', '.button.language', function(e) {
            setLanguage(e);
        });

        $('.sidebar').on('click', '.button.column-url', function(e){
            e.stopPropagation();
            e.preventDefault();
            activeElement.find('input.column-url').prop('disabled', false);
            activeElement.find('li.column-url').show();
            activeElement.find('input.column-reference').prop('disabled', true);
            activeElement.find('li.column-reference').hide();
        });

        $('.sidebar').on('click', '.button.column-multivalue', function(e){
            e.stopPropagation();
            e.preventDefault();
            activeElement.find('input.column-multivalue').prop('disabled', false);
            activeElement.find('li.column-multivalue').show();
        });

        $('.sidebar').on('click', '.button.column-reference', function(e){
            e.stopPropagation();
            e.preventDefault();
            activeElement.find('input.column-reference').prop('disabled', false);
            activeElement.find('li.column-reference').show();
            activeElement.find('input.column-url').prop('disabled', true);
            activeElement.find('li.column-url').hide();
        });

        $('ul.options').on('click', 'a.remove-option', function(e){
            e.stopPropagation();
            e.preventDefault();
            var optionToRemove = $(this).parents('li.option');
            optionToRemove.find('input.column-option').prop('disabled', true);
            optionToRemove.addClass('delete');
            optionToRemove.find('.restore-option').show();
            optionToRemove.find('.remove-option').hide();
        });

        $('ul.options').on('click', 'a.restore-option', function(e){
            e.stopPropagation();
            e.preventDefault();
            var optionToRestore = $(this).parents('li.option');
            optionToRestore.find('input.column-option').prop('disabled', false);
            optionToRestore.removeClass('delete');
            optionToRestore.find('.remove-option').show();
            optionToRestore.find('.restore-option').hide();
        });

        /*
         * Modified from resource-form.js in core
         */

        $('input.value-language').on('keyup', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.setCustomValidity(
                Omeka.langIsValid(this.value) ? '' : Omeka.jsTranslate('Please enter a valid language tag.')
            );
        });

        /*
         * Prevent accidental form submission when entering a language tag
         * and hitting enter by setting the language as if clicking the button
         */

        $('input.value-language').on('keypress', function(e) {
            if (e.keyCode == 13 ) {
                setLanguage(e);
            }
        });

        function setLanguage(e) {
            e.stopPropagation();
            e.preventDefault();
            var valueLanguageElement = document.getElementById('value-language');
            var lang = $(valueLanguageElement).val();
            if (lang == '') {
                valueLanguageElement.setCustomValidity(Omeka.jsTranslate('Please enter a valid language tag.'));
            }
            if (typeof valueLanguageElement.reportValidity === 'function') {
                var valid = valueLanguageElement.reportValidity();
            } else {
                var valid = valueLanguageElement.checkValidity();
                if (! valid) {
                    alert(Omeka.jsTranslate('Please enter a valid language tag.'));
                }
            }

            if (valid && lang != '') {
                var languageInput = activeElement.find('input.column-language');
                languageInput.val(lang);
                activeElement.find('li.column-language').show();
                activeElement.find('span.column-language').html(lang);
                languageInput.prop('disabled', false);
            }
        }

    });
})(jQuery);
