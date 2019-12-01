/**
 * Initially based on Omeka S omeka2importer.js and resource-core.js.
 */
(function ($) {

    $(document).ready(function() {
        /*
         * Init.
         */

        var activeElement = null;
        var activeElements = null;

        var defaultSidebarHtml = null;

        var actionsHtml = '<ul class="actions">'
            + '<li><a aria-label="' + Omeka.jsTranslate('Remove mapping') + '" title="' + Omeka.jsTranslate('Remove mapping') + '" class="o-icon-delete remove-mapping" href="#" style="display: inline;"></a></li>'
            + '</ul>';

        var batchEditCheckboxes = $('.column-select, .select-all');
        var batchEditButton = $('#batch-edit-options');

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
            if ($('.column-select:checked').length > 0) {
                resetActiveColumns();
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
            populateSidebar();
        });

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
                    sidebarOptionInput.val(optionInput.val());
                }
            });
        }

        /*
         * Batch edit options.
         */

        $('.batch-edit input[type="checkbox"], .batch-edit .select-all').change(function() {
            if ($('.column-select:checked').length > 0) {
                batchEditButton.removeClass('inactive').addClass('active sidebar-content');
            } else {
                batchEditButton.addClass('inactive').removeClass('active sidebar-content');
            }
        });

        $(document).on('click', '#batch-edit-options.active', function() {
            defaultSidebarHtml = $('#column-options').html();
            activeElements = $('.column-select:checked').parents('.mappable.element');
            activeElements.addClass('active');
            $(this).removeClass('active sidebar-content').addClass('inactive');
            batchEditCheckboxes.prop('disabled', true);
            $('#column-options').addClass('batch-edit');
            $('.reset-link').each(function() {
                var reset = $(this);
                var optionInputsHtml = reset.siblings('.option-inputs').html();
                reset.attr('data-option-inputs', optionInputsHtml);
            });
        });

        /*
         * Sidebar actions (data mapping and options on the active element).
         */

        $('#resource-type-column').change(function() {
            $('.mapping.resource-type').remove();
            var resourceTypeSelect = $(this);
            var flagName = resourceTypeSelect.data('flag-name');
            var flagValue = 1;
            var flagLabel = resourceTypeSelect.data('flag-label');
            var flagLiClass = resourceTypeSelect.data('flag-class');
            var selectedColumnName = resourceTypeSelect.val();

            if (selectedColumnName == "") {
                return;
            }
            activeElement = $('[name="' + selectedColumnName + '"]').parents('.mappable.element');
            applyMappings(flagName, flagValue, flagLiClass, flagLabel);
            activeElement.find('.resource-type .actions').remove();
            activeElement = null;
        });

        $(document).on('click', '.sidebar-close', function() {
            resetActiveColumns();
            $('#column-options').removeClass('batch-edit');
        });

        // Generic sidebar actions.
        $(document).on('o:expanded', '#add-mapping a', function() {
            var mappingGroup = $(this).parents('.mapping-group');
            var mappingGroupID = mappingGroup.attr('id');
            $('#add-mapping .mapping-group:not(#' + mappingGroupID + ') a.collapse').each(function() {
                var openMappingGroup = $(this);
                openMappingGroup.removeClass('collapse').addClass('expand');
                openMappingGroup.attr('aria-label', Omeka.jsTranslate('Expand')).attr('title', Omeka.jsTranslate('Expand'));
                openMappingGroup.trigger('o:collapsed');
            });
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
                        if (!flagInput.hasClass('touched')) {
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
                    var flagLabel = $('label[for="' + checkboxId + '"]').text();
                    var optionClass = '.' + flagInput.data('flag');
                    if (flagInput.is(':checked')) {
                        var flagValue = flagInput.val();
                        applyMappings(flagName, flagValue, flagLiClass, flagLabel);
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

            Omeka.closeSidebar(sidebar);
            sidebar.html(defaultSidebarHtml);
        });

        $(document).on('change', '.sidebar input, .sidebar select, .sidebar textarea', function() {
            var sidebarInput = $(this);
            sidebarInput.addClass('touched');
            if ($('#column-options').hasClass('batch-edit')) {
                sidebarInput.parents('.option').addClass('batch-edit-touched');
            }
        });

        $(document).on('click', '.reset-link', function(e) {
            e.preventDefault();
            var reset = $(this);
            var columnOption = reset.parents('.option');
            var columnOptionInputsHtml = reset.data('option-inputs');
            columnOption.removeClass('batch-edit-touched');
            columnOption.find('.option-inputs').html(columnOptionInputsHtml);
        });

        $(document).on('click', '#column-options .confirm-panel button', function() {
            var sidebar = $(this).parents('.sidebar');
            var languageTextInput = $('#value-language');
            var languageValue = languageTextInput.val();
            if (activeElements == null) {
                activeElements = activeElement;
            }

            activeElements.each(function() {
                activeElement = $(this);
                if (languageTextInput.hasClass('touched')) {
                    var languageHiddenInput = activeElement.find('.column-language');
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
                        optionLi.find('input[type="hidden"]').val(selectedOptionValue);
                        if (selectedOptionValue !== 'literal') {
                            optionLi.show();
                            optionLi.find('.option-label').text(selectedOption.text());
                        } else {
                            optionLi.hide();
                        }
                    }
                });
                resetActiveColumns();
            });
            Omeka.closeSidebar(sidebar);
            $('#column-options').removeClass('batch-edit');
            sidebar.html(defaultSidebarHtml);
        });

        function resetActiveColumns() {
            activeElements = null;
            $('tr.mappable.active').removeClass('active');
            batchEditCheckboxes.prop('checked', false).prop('disabled', false);
            batchEditButton.removeClass('active sidebar-content').addClass('inactive');
        }

        /*
         * Actions on mapped columns.
         */

        // Remove mapping.
        $('.section').on('click', 'a.remove-mapping', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).parents('li.mapping').remove();
        });

        /*
         * Modified from resource-form.js in core
         */

        $(document).on('keyup', 'input.value-language', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if ('' === this.value || Omeka.langIsValid(this.value)) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity(Omeka.jsTranslate('Please enter a valid language tag'));
            }
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
                var flagUnique = (flagLiClass !== 'property');
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


        function setLanguage(lang) {
            var valueLanguageElement = document.getElementById('value-language');
            if (lang == '') {
                valueLanguageElement.setCustomValidity(Omeka.jsTranslate('Please enter a valid language tag'));
            }
            if (typeof valueLanguageElement.reportValidity === 'function') {
                var valid = valueLanguageElement.reportValidity();
            } else {
                var valid = valueLanguageElement.checkValidity();
                if (! valid) {
                    alert(Omeka.jsTranslate('Please enter a valid language tag'));
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

        function toggleActionOptions() {
            var action = $('#action').val();
            if (action === 'create') {
                $('.action-option')
                    .closest('.field').hide();
            } else {
                $('.action-option')
                    .closest('.field').show();
            }
        }
        toggleActionOptions();
        $('#action').change(toggleActionOptions);
    });

})(jQuery);
