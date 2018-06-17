/**
 * Initially based on Omeka S omeka2importer.js and resource-core.js.
 *
 * @todo Remove dead code (now, the options (wrench) are directly set).
 */
(function ($) {

    $(document).ready(function() {
        /*
         * Init.
         */

        var activeElement = null;

        var defaultSidebarHtml = null;

        var actionsHtml = '<ul class="actions">'
            + '<li><a aria-label="' + Omeka.jsTranslate('Remove mapping') + '" title="' + Omeka.jsTranslate('Remove mapping') + '" class="o-icon-delete remove-mapping" href="#" style="display: inline;"></a></li>'
            + '<li><a aria-label="' + Omeka.jsTranslate('Undo remove mapping') + '" title="' + Omeka.jsTranslate('Undo remove mapping') + '" class="o-icon-undo restore-mapping" href="#" style="display: none;"></a></li>'
            + '</ul>';

        setMultivalueSeparatorByDefault();
        setLanguageByDefault();

        /*
         * Rebinding chosen selects and property selector after sidebar hydration.
         */

         function rebindInputs(sidebar) {
              // Remove old chosen html and rebind event.
              sidebar.find('.chosen-container').remove();
              sidebar.find('.chosen-select').chosen(chosenOptions);

              // Rebind property selector.
              $('.selector li.selector-parent').on('click', function(e) {
                  e.stopPropagation();
                  if ($(this).children('li')) {
                      $(this).toggleClass('show');
                  }
              });

              $('.selector-filter').on('keydown', function(e) {
                  if (e.keyCode == 13) {
                      e.stopPropagation();
                      e.preventDefault();
                  }
              });

              // Property selector, filter properties.
              $('.selector-filter').on('keyup', (function() {
                  var timer = 0;
                  return function() {
                      clearTimeout(timer);
                      timer = setTimeout(Omeka.filterSelector.bind(this), 400);
                  }
              })())

              // Specific sidebar actions for property selector.
              $('#property-selector li.selector-child').on('click', function(e){
                  e.stopPropagation();
                  $(this).addClass('selected');
              });
         }

        /*
         * Sidebar chooser (buttons on each mappable element).
         */

        $('.add-mapping.button, .column-header + .actions a').on('click', function(e) {
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
            var target = actionElement.data('sidebar-selector');

            var sidebar = $(target);
            if (!sidebar.hasClass('active') ) {
                defaultSidebarHtml = sidebar.html();
            }
            var columnName = activeElement.data('column');
            if (sidebar.find('.column-name').length > 0) {
                $('.column-name').text(columnName);
            } else {
                sidebar.find('h3').append(' <span class="column-name">' + columnName + '</span>');
            }

            var currentSidebar = $('.sidebar.active');
            if (currentSidebar.attr('id') != target) {
                currentSidebar.removeClass('active');
                sidebar.html(defaultSidebarHtml);
                rebindInputs(sidebar);
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
        $(document).on('click', '.toggle-nav button', function() {
            $('.active.toggle.button').removeAttr('disabled');
            $('.toggle-nav .active.button, .toggle-view.active').removeClass('active')
            var button = $(this);
            var target = $(button.data('toggle-selector'));
            button.addClass('active').attr('disabled', true);
            target.addClass('active');
            target.find(':input').removeAttr('disabled');
        });

        $(document).on('change', '.resource-type-select select', function() {
            var selectInput = $(this);
            var selectedOption = selectInput.find(':selected').val();
            selectInput.parent('.resource-type-select').siblings('.mapping').removeClass('active');
            if (selectedOption !== 'default') {
                $('.mapping.' + selectedOption).addClass('active');
            }
        });

        $(document).on('click', '.flags .confirm-panel button', function() {
            var sidebar = $(this).parents('.sidebar');

            sidebar.find('[data-flag-class]').each(function() {
                var flagInput = $(this);
                var flagLiClass = flagInput.data('flag-class');

                // If this is a specific resource data, process the inputs of
                // the selected resource data inputs only, and remove the other
                // inputs.
                var isSpecificInput = flagInput.parents('#specific-data').length === 1;
                var isSpecificInputForResourceType = false;
                var specificResourceType;
                if (isSpecificInput) {
                    specificResourceType = $('#data-resource-type-select').val();
                    isSpecificInputForResourceType = flagInput.closest('div').hasClass(specificResourceType);
                }

                // Note: the hidden data are not changed.
                // TODO Good rebind of data when opened, so there will no issues with hidden data.

                if (flagInput.is('select')) {
                    var flagLabel = flagInput.data('flag-label');
                    if (flagInput.hasClass('chosen-select')) {
                        if (flagInput.next('.chosen-container').parents('.toggle-view:hidden').length > 0) {
                            return;
                        }
                        var flagValue = flagInput.chosen().val();
                        if (flagValue == '') {
                            return;
                        }
                        var flagName = flagInput.chosen().data('flag-name');

                        // Show flag name instead of selected text for mapping using property selector.
                        if (flagInput.parents('.mapping').hasClass('property')) {
                            flagLabel += ' [' + flagValue + ']';
                        } else {
                            flagLabel += ' [' + flagInput.chosen().text() + ']';
                        }
                    }
                    else {
                        if (flagInput.parents('.toggle-view:hidden').length > 0) {
                            return;
                        }
                        var flagSelected = flagInput.find(':selected');
                        var flagValue = flagSelected.val();
                        var flagName = flagSelected.data('flag-name');
                        flagLabel += ' [' + flagSelected.text() + ']';
                    }

                    if (isSpecificInput && !isSpecificInputForResourceType) {
                        flagValue = '';
                    }
                    applyMappings(flagName, flagValue, flagLiClass, flagLabel);
                }

                if (flagInput.is('input[type=checkbox]')) {
                    if (flagInput.parents('.toggle-view:hidden').length > 0) {
                        return;
                    }
                    var checkboxId = flagInput.attr('id');
                    var flagName = flagInput.data('flag-name');
                    var flagLabel = $('label[for="' + checkboxId + '"]').text();
                    if (isSpecificInput && !isSpecificInputForResourceType) {
                        flagValue = '';
                    } else {
                        flagValue = flagInput.is(':checked') ? '1' : '';
                    }
                    applyMappings(flagName, flagValue, flagLiClass, flagLabel);
                }
            });

            sidebar.find('.selector-child.selected').each(function () {
                // Looks like a stopPropagation on the selector-parent forces me to
                // bind the event lower down the DOM, then work back up to the li.
                var targetLi = $(this);

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
                }
                targetLi.removeClass('selected');
            });

            function applyMappings(flagName, flagValue, flagLiClass, flagLabel) {
                // There may be multiple classes, so the search requires a "." between each class.
                var flagLiClassFind = '.' + flagLiClass.replace(/ /g, '.');
                var hasFlag = activeElement.find('ul.mappings li' + flagLiClassFind);
                if (flagValue === 'default' || flagValue === '') {
                    if (hasFlag.length) {
                        hasFlag.remove();
                    }
                    return;
                }

                if (hasFlag.length) {
                    var flagUnique = (flagLiClass === 'resource-data')
                        || (flagLiClass.indexOf('resource-data') >= 0)
                        || (flagLiClass === 'media-source')
                        || (flagLiClass === 'user-data');
                    if (flagUnique){
                        activeElement.find('ul.mappings ' + flagLiClassFind).remove();
                        hasFlag = activeElement.find('ul.mappings li' + flagLiClassFind);
                    }
                }

                if (hasFlag.length === 0 && flagName) {
                    var index = activeElement.data('element-id');
                    flagName = flagName + "[" + index + "]";
                    var newInput = $('<input type="hidden"></input>').attr('name', flagName).attr('value', flagValue);
                    var newMappingLi = $('<li class="mapping ' + flagLiClass + '">' + flagLabel  + actionsHtml  + '</li>');
                    newMappingLi.append(newInput);
                    var existingMappingLi = activeElement.find('ul.mappings ' + flagLiClassFind).filter(':last');
                    if (existingMappingLi.length) {
                        existingMappingLi.after(newMappingLi);
                    } else {
                        activeElement.find('ul.mappings').append(newMappingLi);
                    }
                }
            };

            Omeka.closeSidebar(sidebar);
            sidebar.html(defaultSidebarHtml);
        });

        $(document).on('click', '#column-options .confirm-panel button', function() {
            var sidebar = $(this).parents('.sidebar');

            var languageTextInput = $('#value-language');
            if (languageTextInput.hasClass('touched')) {
                var languageHiddenInput = activeElement.find('.column-language');
                var languageValue = languageTextInput.val();
                if (languageValue !== '') {
                    setLanguage(languageValue, languageTextInput);
                } else {
                    activeElement.find('li.column-language').hide();
                    languageHiddenInput.attr('disabled', true);
                }
            }

            sidebar.find('input[type=checkbox]').each(function() {
                var checkboxInput = $(this);
                if (checkboxInput.hasClass('touched')) {
                    var optionClass = '.' + checkboxInput.data('column-option');
                    var optionLi = activeElement.find(optionClass);
                    if (checkboxInput.is(':checked')) {
                        optionLi.show();
                        optionLi.find('input[type=hidden]').removeAttr('disabled');
                    } else {
                        optionLi.hide();
                        optionLi.find('input[type=hidden]').attr('disabled', true);
                    }
                }
            });

            sidebar.find('select').each(function() {
                var selectInput = $(this);
                if (selectInput.hasClass('touched')) {
                    var selectedOption = selectInput.find(':selected');
                    var selectedOptionValue = selectedOption.val();
                    var optionClass = '.' + selectInput.data('column-option');
                    var optionLi = activeElement.find(optionClass);
                    if (selectedOptionValue !== 'default') {
                        optionLi.show();
                        optionLi.find('.option-label').text(selectedOption.text());
                        optionLi.find('input[type=hidden]').attr('disabled', true);
                        optionLi.find('.' + selectedOptionValue).removeAttr('disabled');
                    } else {
                        optionLi.hide();
                        optionLi.find('input[type=hidden]').attr('disabled', true);
                    }
                }
            });

            Omeka.closeSidebar(sidebar);
            sidebar.html(defaultSidebarHtml);
        });

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

        /**
         * Manage options via a direct update of the mapping.
         */

        // Set/unset multivalue separator for all columns.
        $(document).on('change', '#multivalue_by_default', function(e) {
            setMultivalueSeparatorByDefault();
        });

        function setMultivalueSeparatorByDefault() {
            var switcher = $('#multivalue_by_default').prop('checked');
            var element = $('.element.mappable');
            var multivalueSeparator = $('#multivalue_separator').val();
            var targetRows = $('.element.mappable li.column-multivalue');
            targetRows.removeClass('delete');
            targetRows.find('.remove-option').css({ display: 'inline' });
            targetRows.find('.restore-option').css({ display: 'none' });
            if (switcher && multivalueSeparator !== '') {
                element.find('li.column-multivalue').show();
                element.find('.column-multivalue span.column-multivalue').text(multivalueSeparator);
                element.find('input.column-multivalue').prop('disabled', false);
                element.find('input.column-multivalue').val(multivalueSeparator);
            } else {
                element.find('li.column-multivalue').hide();
                element.find('.column-multivalue span.column-multivalue').text('');
                element.find('input.column-multivalue').prop('disabled', true);
                element.find('input.column-multivalue').val('');
            }
        }

        // Set/unset default language for all columns.
        $(document).on('change', '#language_by_default', function(e) {
            setLanguageByDefault();
        });

        function setLanguageByDefault() {
            var switcher = $('#language_by_default').prop('checked');
            var element = $('.element.mappable');
            var lang = $('#language').val();
            var targetRows = $('.element.mappable li.column-language');
            targetRows.removeClass('delete');
            targetRows.find('.remove-option').css({ display: 'inline' });
            targetRows.find('.restore-option').css({ display: 'none' });
            if (switcher && lang !== '' && Omeka.langIsValid(lang)) {
                element.find('li.column-language').show();
                element.find('.column-language span.column-language').text(lang);
                element.find('input.column-language').prop('disabled', false);
                element.find('input.column-language').val(lang);
            } else {
                element.find('li.column-language').hide();
                element.find('.column-language span.column-language').text('');
                element.find('input.column-language').prop('disabled', true);
                element.find('input.column-language').val('');
            }
        }

        $(document).on('click', '.o-icon-configure.sidebar-content', function(){
            var multivalueSeparator = $('#content').find('.element.mappable.active').find('.column-multivalue.option span.column-multivalue').text();
            $('#multivalue').val(multivalueSeparator);

            var lang = $('#content').find('.element.mappable.active').find('.column-language.option span.column-language').text();
            $('#value-language').val(lang);

            var importType = $('#content').find('.element.mappable.active').find('.column-import.option span.option-label').text();
            if (importType == '') {
                $('#column-import').val('default');
            } else if (importType == Omeka.jsTranslate('Import as URL reference')) {
                $('#column-import').val('column-url');
            } else if (importType == Omeka.jsTranslate('Import as Omeka S resource ID')) {
                $('#column-import').val('column-reference');
            }
        });

        $(document).on('keyup', '#multivalue', function(){
            var multivalueSeparator = $(this).val();
            var element = $('#content').find('.element.mappable.active');
            if (multivalueSeparator !== '') {
                element.find('li.column-multivalue').show();
                element.find('.column-multivalue span.column-multivalue').text(multivalueSeparator);
                element.find('input.column-multivalue').prop('disabled', false);
                element.find('input.column-multivalue').val(multivalueSeparator);
            } else {
                element.find('li.column-multivalue').hide();
                element.find('.column-multivalue span.column-multivalue').text('');
                element.find('input.column-multivalue').prop('disabled', true);
                element.find('input.column-multivalue').val('');
            }
        });

        $(document).on('keyup', '#value-language', function(){
            var lang = $(this).val();
            var element = $('#content').find('.element.mappable.active');
            if (lang !== '' && Omeka.langIsValid(lang)) {
                this.setCustomValidity('');
                element.find('li.column-language').show();
                element.find('.column-language span.column-language').text(lang);
                element.find('input.column-language').prop('disabled', false);
                element.find('input.column-language').val(lang);
            } else {
                if (lang === '') {
                    this.setCustomValidity('');
                } else {
                    this.setCustomValidity(Omeka.jsTranslate('Please enter a valid language tag'));
                }
                element.find('li.column-language').hide();
                element.find('.column-language span.column-language').text('');
                element.find('input.column-language').prop('disabled', true);
                element.find('input.column-language').val('');
            }
        });

        $(document).on('change', '#column-import', function(){
            var importType = $(this).val();
            var element = $('#content').find('.element.mappable.active');
            if (importType === 'default') {
                element.find('.column-import.option').hide();
                element.find('.column-import.option .column-url').prop('disabled', true);
                element.find('.column-import.option .column-reference').prop('disabled', true);
            } else if (importType == 'column-url') {
                element.find('.column-import.option').show();
                element.find('.column-import.option .option-label').text(Omeka.jsTranslate('Import as URL reference'));
                element.find('.column-import.option .column-url').prop('disabled', false);
                element.find('.column-import.option .column-reference').prop('disabled', true);
            } else if (importType == 'column-reference') {
                element.find('.column-import.option').show();
                element.find('.column-import.option .option-label').text(Omeka.jsTranslate('Import as Omeka S resource ID'));
                element.find('.column-import.option .column-url').prop('disabled', true);
                element.find('.column-import.option .column-reference').prop('disabled', false);
            }
        });

        /*
         * Modified from resource-form.js in core, unavailable here.
         */

        /*
         * Check validity of a language.
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

        function setLanguage(lang) {
            var valueLanguageElement = document.getElementById('value-language');
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

        /*
         * Manage identifiers.
         */

        function checkResourceIdentifier() {
            return checkIdentifier('column-resource_property');
        }

        function checkItemSetIdentifier() {
            return checkIdentifier('column-item_set_property');
        }

        function checkItemIdentifier() {
            return checkIdentifier('column-item_property');
        }

        function checkMediaIdentifier() {
            return checkIdentifier('column-media_property');
        }

        function checkIdentifier(elementProperty) {
            var valid = true;
            var elementResourceProperty = document.getElementById(elementProperty);
            var valueResourceProperty = $('#' + elementProperty).chosen().val();
            if (valueResourceProperty === '') {
                elementResourceProperty.setCustomValidity(Omeka.jsTranslate('Please enter a valid resource identifier property.'));
                elementResourceProperty.reportValidity();
                valid = false;
                alert(Omeka.jsTranslate('Please enter a valid resource property for the identifier.'));
            }
            return valid;
        }

    });
})(jQuery);
