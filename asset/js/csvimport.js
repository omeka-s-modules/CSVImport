/**
 * Initially based on Omeka S omeka2importer.js and resource-core.js.
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

        $('.column-header + .actions a').on('click', function(e) {
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
            resetSidebar();
            populateSidebar();
        });

        function resetSidebar() {
            $('.active.sidebar :input').val("");
        }

        function populateSidebar() {
            $('.active.element .options :input:not(:disabled)').each(function() {
                var optionInput = $(this);
                var optionName = optionInput.parents('.option').attr('class');
                optionName = optionName.replace(' option', '').replace('column-', '');
                var sidebarOptionInput = $('#column-options .' + optionName + ' :input');
                if (sidebarOptionInput.attr('type') == "checkbox") {
                    sidebarOptionInput.prop('checked', true);
                }
                if (sidebarOptionInput.attr('type') == "text") {
                    sidebarOptionInput.val(optionInput.val());
                }
                if (sidebarOptionInput.prop('type') == "select-one") {
                    var indexString = /(\[)(\d*)(\])/;
                    var selectOptionName = this.name.replace(indexString, '');
                    sidebarOptionInput.val(selectOptionName);
                }
            });
        }

        /*
         * Sidebar actions (data mapping and options on the active element).
         */

        $('.sidebar-close').on('click', function() {
            $('tr.mappable.active').removeClass('active');
        });

        // Generic sidebar actions.
        $(document).on('click', '.toggle-nav button', function() {
            $('.active.toggle.button').prop('disabled', false);
            $('.toggle-nav .active.button, .toggle-view.active').removeClass('active')
            var button = $(this);
            var target = $(button.data('toggle-selector'));
            button.addClass('active').prop('disabled', true);
            target.addClass('active');
            target.find(':input').prop('disabled', false);
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
                            var flagLabel = flagLabel + ' [' + flagValue + ']';
                        } else {
                            var flagLabel = flagLabel + ' [' + flagInput.chosen().text() + ']';
                        }
                    }
                    else {
                        if (flagInput.parents('.toggle-view:hidden').length > 0) {
                            return;
                        }
                        var flagSelected = flagInput.find(':selected');
                        var flagValue = flagSelected.val();
                        var flagName = flagSelected.data('flag-name');
                        var flagLabel = flagLabel + ' [' + flagSelected.text() + ']';
                    }

                    applyMappings(flagName, flagValue, flagLiClass, flagLabel);
                }

                if (flagInput.is('input[type="checkbox"]')) {
                    var flagName = flagInput.data('flag-name');
                    if (flagInput.parents('.toggle-view:hidden').length > 0) {
                        return;
                    }
                    var checkboxId = flagInput.attr('id');
                    var flagName = $('label[for="' + checkboxId + '"]').text();
                    var optionClass = '.' + flagInput.data('flag');
                    if (flagInput.is(':checked')) {
                        var flagValue = flagInput.val();
                        applyMappings(flagName, flagValue, flagLiClass, flagName);
                    }
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
                var hasFlag = activeElement.find('ul.mappings li.' + flagLiClass);
                if (flagValue == 'default') {
                    if (hasFlag.length) {
                        hasFlag.remove();
                    } else {
                        return;
                    }
                }
                if (hasFlag.length) {
                    var flagUnique = (flagLiClass === 'resource-data')
                        || (flagLiClass === 'media-source')
                        || (flagLiClass === 'user-data');
                    if (flagUnique){
                        activeElement.find('ul.mappings .' + flagLiClass).remove();
                        hasFlag = activeElement.find('ul.mappings li.' + flagLiClass);
                    }
                }
    
                if (hasFlag.length === 0) {
                    var index = activeElement.data('element-id');
                    flagName = flagName + "[" + index + "]";
                    var newInput = $('<input type="hidden"></input>').attr('name', flagName).attr('value', flagValue);
                    var newMappingLi = $('<li class="mapping ' + flagLiClass + '">' + flagLabel  + actionsHtml  + '</li>');
                    newMappingLi.append(newInput);
                    var existingMappingLi = activeElement.find('ul.mappings .' + flagLiClass).filter(':last');
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
                    languageHiddenInput.prop('disabled', true);
                }
            }

            sidebar.find('input[type="checkbox"]').each(function() {
                var checkboxInput = $(this);
                if (checkboxInput.hasClass('touched')) {
                    var optionClass = '.' + checkboxInput.data('column-option');
                    var optionLi = activeElement.find($(optionClass));
                    if (checkboxInput.is(':checked')) {
                        optionLi.show();
                        optionLi.find('input[type="hidden"]').prop('disabled', false);
                    } else {
                        optionLi.hide();
                        optionLi.find('input[type="hidden"]').prop('disabled', true);
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
                    optionLi.find('input[type="hidden"]').prop('disabled', true);
                    if (selectedOptionValue !== 'default') {
                        optionLi.show();
                        optionLi.find('.option-label').text(selectedOption.text());
                        optionLi.find('.' + selectedOptionValue).prop('disabled', false)
                    } else {
                        optionLi.hide();
                    }
                }
            });

            Omeka.closeSidebar(sidebar);
            sidebar.html(defaultSidebarHtml);
        });

        // Set/unset multivalue separator for all columns.
        $(document).on('click', '#multivalue_by_default', function(e) {
            $(this).toggleClass('active');
            setMultivalueSeparatorColumns();
        });

        function setMultivalueSeparatorColumns() {
            var targetRows = $('.element.mappable li.column-multivalue');
            var mappableElement = $('.element.mappable');
            targetRows.removeClass('delete');
            targetRows.find('.remove-option').show();
            targetRows.find('.restore-option').hide();
            if ($('#multivalue_by_default').hasClass('active')) {
                mappableElement.find('li.column-multivalue').show();
                mappableElement.find('input[type="hidden"]').prop('disabled', false);
            } else {
                mappableElement.find('li.column-multivalue').hide();
                mappableElement.find('input[type="hidden"]').prop('disabled', true);
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
