(function ($) {
    var activeElement = null;

    var actionsHtml = '<ul class="actions"><li><a aria-label="Remove mapping" title="Remove mapping" class="o-icon-delete remove-mapping" href="#" style="display: inline;"></a></li></ul>';

    $(document).ready(function() {
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
                alert("Select an item type at the left before choosing a resource class.");
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
        $('#mapping-data').on('click', 'a.remove-mapping', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).parents('li.mapping').remove();
        });

        $('.sidebar-chooser').on('click', 'a', function(e) {
            e.preventDefault();

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
            Omeka.openSidebar(actionElement, target);
        });

        $('.sidebar.flags li').on('click', function(e){
            e.stopPropagation();
            e.preventDefault();
            //looks like a stopPropagation on the selector-parent forces
            //me to bind the event lower down the DOM, then work back
            //up to the li
            var targetLi = $(e.target).closest('li');
            if (activeElement == null) {
                alert("Select an element at the left before choosing a property.");
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
    });
})(jQuery);
