/**
 * Initially based on Omeka S omeka2importer.js and resource-core.js.
 */
(function ($) {

    $(document).ready(function() {
        /*
         * Init.
         */

        var activeElement = null;

        var actionsHtml = '<ul class="actions">'
            + '<li><a aria-label="' + Omeka.jsTranslate('Remove mapping') + '" title="' + Omeka.jsTranslate('Remove mapping') + '" class="o-icon-delete remove-mapping" href="#" style="display: inline;"></a></li>'
            + '<li><a aria-label="' + Omeka.jsTranslate('Undo remove mapping') + '" title="' + Omeka.jsTranslate('Undo remove mapping') + '" class="o-icon-undo restore-mapping" href="#" style="display: none;"></a></li>'
            + '</ul>';

        $('#action').closest('.field').before(
            '<div class="field">'
            + '<div class="field-meta">'
            + '<button type="button" name="advanced-settings" id="advanced-settings" value="show">'
            + Omeka.jsTranslate('Advanced settings')
            + '</button>'
            + '</div>'
            + '</div>'
        );
        $('.advanced-settings').closest('.field').hide();

        setMultivalueSeparatorByDefault();

        /*
         * Main import form.
         */

        displayUserList();

        $('#automap_check_user_list').on('change', function(e){
            displayUserList();
        });

        function displayUserList() {
            if ($('#automap_check_user_list').prop('checked')) {
                $('#automap_user_list').closest('.field').show();
            } else {
                $('#automap_user_list').closest('.field').hide();
            }
        }

        /*
         * Basic import settings tab.
         */

        // Manage advanced settings.
        $('#advanced-settings').on('click', function(e) {
            e.preventDefault();
            $('.advanced-settings').closest('.field').show();
            $(this).closest('.field').remove();
        });

        /*
         * Mapping form tab.
         */

        /*
         * Sidebar chooser (buttons on each mappable element).
         */

        $('.sidebar-chooser').on('click', 'a', function(e) {
            e.preventDefault();
            $('.property-mapping input.value-language').val('');
            if (activeElement !== null) {
                activeElement.removeClass('active');
            }
            activeElement = $(e.target).closest('tr.mappable');
            activeElement.addClass('active');

            var actionElement = $(this);
            $('.sidebar-chooser li').removeClass('active');
            actionElement.parent().addClass('active');
            var target = '#' + actionElement.data('sidebar');

            var sidebar = $(target);
            var columnName = actionElement.data('column');
            if (sidebar.find('.column-name').length > 0) {
                $('.column-name').text(columnName);
            } else {
                sidebar.find('h3').append(' <span class="column-name">' + columnName + '</span>');
            }

            var currentSidebar = $('.sidebar.active');
            if (currentSidebar.attr('id') != target) {
                currentSidebar.removeClass('active');
            }
            Omeka.openSidebar(sidebar);
        });

        /*
         * Sidebar actions (data mapping and options on the active element).
         */

        $('.sidebar-close').on('click', function() {
            $('tr.mappable.active').removeClass('active');
        });

        // Generic sidebar actions.
        $('.sidebar.flags li').on('click', function(e){
            // Hijack the multivalue option because it is handled separately below.
            var targetLi = $(e.target).closest('li');
            if (targetLi.hasClass('column-multivalue')) {
                return;
            }
            // Hijack the resource identifier options, handled separately.
            var flagData = targetLi.data('flag');
            if (flagData === 'column-resource_identifier_property'
                || flagData === 'column-resource_identifier_type'
                || flagData === 'column-item_sets_property'
            ) {
                return;
            }
            // Check the resource identifier.
            if (flagData === 'column-resource_identifier' && !checkResourceIdentifier()) {
                return;
            }
            // Check the item set identifier.
            if (flagData === 'column-item_sets' && !checkItemSetIdentifier()) {
                return;
            }
            // Check the item identifier.
            if (flagData === 'column-items' && !checkItemIdentifier()) {
                return;
            }

            e.stopPropagation();
            e.preventDefault();
            // Looks like a stopPropagation on the selector-parent forces me to
            // bind the event lower down the DOM, then work back up to the li.

            if (activeElement == null) {
                alert(Omeka.jsTranslate('Select an element at the left before choosing a property.'));
                return;
            }

            var flagName = targetLi.find('span').text();
            var flagType = targetLi.data('flag-type');
            if (! flagType) {
                flagType = flagData;
            }
            if (flagType == undefined) {
                return;
            }

            // First, check if the flag is already added or if there is
            // already a mapping.
            var hasFlag = activeElement.find('ul.mappings li.' + flagType);

            // If there is a similar flag that doesn't support multimapping,
            // remove it.
            if (hasFlag.length) {
                var flagUnique = targetLi.data('flag-unique')
                    || flagType === 'resource-data'
                    || flagType === 'media-source'
                    || flagType === 'user-data';
                if (flagUnique){
                    activeElement.find('ul.mappings .' + flagType).remove();
                    hasFlag = activeElement.find('ul.mappings li.' + flagType);
                }
            }

            if (hasFlag.length === 0) {
                var elementId = activeElement.data('element-id');
                // elementId, or index? @TODO: check the naming conventions
                // much is copied from Omeka2Importer, and might need clarification

                var index = elementId;
                var name = flagData + "[" + index + "]";
                // Special handling for Media source, which can add flags
                // for different media types.
                var value;
                if (flagType == 'media-source') {
                    flagName = Omeka.jsTranslate('Media source') + ' (' + flagName + ')';
                    value = targetLi.data('value');
                } else if (flagData == 'column-resource_identifier') {
                    var resourceIdentifierPropertyId = $('#column-resource_identifier_property').chosen().val();
                    var resourceIdentifierProperty = $('#column-resource_identifier_property option[value=' + resourceIdentifierPropertyId + ']');
                    resourceIdentifierProperty = resourceIdentifierProperty.data('term') || resourceIdentifierProperty.text();
                    var resourceIdentifierType = $('#column-resource_identifier_type').val();
                    flagName = Omeka.jsTranslate('Identifier for') + '"' + resourceIdentifierType + '" [' + resourceIdentifierProperty + ']';
                    value = '{"property":"' + resourceIdentifierProperty + '","type":"' + resourceIdentifierType + '"}';
                } else if (flagData == 'column-item_sets') {
                    var resourceIdentifierPropertyId = $('#column-item_sets_property').chosen().val();
                    var resourceIdentifierProperty = $('#column-item_sets_property option[value=' + resourceIdentifierPropertyId + ']');
                    resourceIdentifierProperty = resourceIdentifierProperty.data('term') || resourceIdentifierProperty.text();
                    var resourceIdentifierType = 'item_sets';
                    flagName = Omeka.jsTranslate('Item set') + ' [' + resourceIdentifierProperty + ']';
                    value = resourceIdentifierProperty;
                } else if (flagData == 'column-items') {
                    var resourceIdentifierPropertyId = $('#column-items_property').chosen().val();
                    var resourceIdentifierProperty = $('#column-items_property option[value=' + resourceIdentifierPropertyId + ']');
                    resourceIdentifierProperty = resourceIdentifierProperty.data('term') || resourceIdentifierProperty.text();
                    var resourceIdentifierType = 'items';
                    flagName = Omeka.jsTranslate('Item') + ' [' + resourceIdentifierProperty + ']';
                    value = resourceIdentifierProperty;
                } else {
                    value = 1;
                }
                var newInput = $('<input type="hidden" name="' + name +'" ></input>').val(value);
                var newMappingLi = $('<li class="mapping ' + flagType + '">' + flagName  + actionsHtml  + '</li>');
                newMappingLi.append(newInput);
                // For ergonomy, group elements by type.
                var existingMappingLi = activeElement.find('ul.mappings .' + flagType).filter(':last');
                if (existingMappingLi.length) {
                    existingMappingLi.after(newMappingLi);
                } else {
                    activeElement.find('ul.mappings').append(newMappingLi);
                }
                Omeka.closeSidebar($(this));
            }
        });

        // Specific sidebar actions for property selector.
        $('#property-selector li.selector-child').on('click', function(e){
            e.stopPropagation();
            // Looks like a stopPropagation on the selector-parent forces me to
            // bind the event lower down the DOM, then work back up to the li.
            var targetLi = $(e.target).closest('li.selector-child');

            // First, check if the property is already added.
            var hasMapping = activeElement.find('ul.mappings li[data-property-id="' + targetLi.data('property-id') + '"]');
            if (hasMapping.length === 0) {
                var elementId = activeElement.data('element-id');
                var newInput = $('<input type="hidden" name="column-property[' + elementId + '][]" ></input>');
                newInput.val(targetLi.data('property-id'));
                var newMappingLi = $('<li class="mapping property" data-property-id="' + targetLi.data('property-id') + '">' + targetLi.data('child-search') + actionsHtml  + '</li>');
                newMappingLi.append(newInput);
                // For ergonomy, group elements by type.
                var existingMappingLi = activeElement.find('ul.mappings .property').filter(':last');
                if (existingMappingLi.length) {
                    existingMappingLi.after(newMappingLi);
                } else {
                    activeElement.find('ul.mappings').append(newMappingLi);
                }
                Omeka.closeSidebar($(this));
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

        $('.sidebar').on('click', '.button.column-reference', function(e){
            e.stopPropagation();
            e.preventDefault();
            activeElement.find('input.column-reference').prop('disabled', false);
            activeElement.find('li.column-reference').show();
            activeElement.find('input.column-url').prop('disabled', true);
            activeElement.find('li.column-url').hide();
        });

        $('.sidebar').on('click', '.button.column-text', function(e){
            e.stopPropagation();
            e.preventDefault();
            activeElement.find('input.column-url').prop('disabled', true);
            activeElement.find('input.column-reference').prop('disabled', true);
            activeElement.find('li.column-url').hide();
            activeElement.find('li.column-reference').hide();
            activeElement.find('li.column-url').removeClass('delete');
            activeElement.find('li.column-reference').removeClass('delete');
            activeElement.find('li .remove-option').css({ display: 'inline' });
            activeElement.find('li .restore-option').css({ display: 'none' });
        });

        $('.sidebar').on('click', '.button.column-multivalue', function(e){
            e.stopPropagation();
            e.preventDefault();
            activeElement.find('input.column-multivalue').prop('disabled', false);
            activeElement.find('li.column-multivalue').show();
        });

        // Set/unset multivalue separator for all columns.
        $('#multivalue_by_default').on('change', function(e) {
            setMultivalueSeparatorByDefault();
        });

        function setMultivalueSeparatorByDefault() {
            var multivalueSwitch = $('#multivalue_by_default').prop('checked');
            var targetRows = $('.element.mappable li.column-multivalue');
            targetRows.removeClass('delete');
            targetRows.find('.remove-option').css({ display: 'inline' });
            targetRows.find('.restore-option').css({ display: 'none' });
            if (multivalueSwitch) {
                $('.sidebar .button.column-multivalue').each(function() {
                    $('.element.mappable').find('input.column-multivalue').prop('disabled', false);
                    $('.element.mappable').find('li.column-multivalue').show();
                });
            } else {
                $('.sidebar .button.column-multivalue').each(function() {
                    $('.element.mappable').find('input.column-multivalue').prop('disabled', true);
                    $('.element.mappable').find('li.column-multivalue').hide();
                });
            }
        }

        /*
         * Actions on mapped columns.
         */

        // Clear default mappings.
        $('body').on('click', '.clear-defaults', function(e) {
            e.stopPropagation();
            e.preventDefault();
            var fieldset = $(this).parents('fieldset');
            fieldset.find('li.mapping.default').remove();
        });

        // Remove mapping.
        $('.section').on('click', 'a.remove-mapping', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var mappingToRemove = $(this).parents('li.mapping');
            mappingToRemove.find('input').prop('disabled', true);
            mappingToRemove.addClass('delete');
            mappingToRemove.find('.restore-mapping').show();
            $(this).hide();
        });

        // Restore a removed mapping.
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

        // Remove option.
        $('ul.options').on('click', 'a.remove-option', function(e){
            e.stopPropagation();
            e.preventDefault();
            var optionToRemove = $(this).parents('li.option');
            optionToRemove.find('input.column-option').prop('disabled', true);
            optionToRemove.addClass('delete');
            optionToRemove.find('.restore-option').show();
            optionToRemove.find('.remove-option').hide();
        });

        // Restore option.
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
            if ('' === this.value || Omeka.langIsValid(this.value)) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity(Omeka.jsTranslate('Please enter a valid language tag'))
            }
        });
        // Prevent accidental form submission when entering a language tag and
        // and hitting enter by setting the language as if clicking the button.
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

        function checkResourceIdentifier() {
            return checkIdentifier('column-resource_identifier_property', 'column-resource_identifier_type');
        }

        function checkItemSetIdentifier() {
            return checkIdentifier('column-item_sets_property');
        }

        function checkItemIdentifier() {
            return checkIdentifier('column-items_property');
        }

        function checkIdentifier(elementProperty, elementType) {
            var valid = true;
            var elementResourceIdentifierProperty = document.getElementById(elementProperty);
            var valueResourceIdentifierProperty = $('#' + elementProperty).chosen().val();
            if (valueResourceIdentifierProperty === '') {
                elementResourceIdentifierProperty.setCustomValidity(Omeka.jsTranslate('Please enter a valid resource identifier property.'));
                elementResourceIdentifierProperty.reportValidity();
                valid = false;
            }
            if (typeof elementType !== undefined) {
                var elementResourceIdentifierType = document.getElementById(elementType);
                var valueResourceIdentifierType = $('#' + elementType).val();
                var resourceTypes = ['resources', 'item_sets', 'items', 'media'];
                if (resourceTypes.indexOf(valueResourceIdentifierType) >= 0) {
                    elementResourceIdentifierType.setCustomValidity(Omeka.jsTranslate('Please enter a valid resource type for identifier.'));
                    elementResourceIdentifierType.reportValidity();
                    valid = false;
                }
            }
            if (!valid) {
                alert(Omeka.jsTranslate('Please enter a valid resource property and/or type for identifier.'));
            }
            return valid;
        }

    });
})(jQuery);
