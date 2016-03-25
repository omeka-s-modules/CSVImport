(function ($) {
    var activeElement = null;

    var actionsHtml = '<ul class="actions"><li><a aria-label="Remove mapping" title="Remove mapping" class="o-icon-delete remove-mapping" href="#" style="display: inline;"></a></li></ul>';

    $(document).ready(function() {
        $('#mapping-data').on('click', 'tr.mappable', function(e) {
            if (activeElement !== null) {
                activeElement.removeClass('active');
            }
            activeElement = $(e.target).closest('tr.mappable');
            activeElement.addClass('active');
            if (activeElement.hasClass('element')) {
                $('#resource-class-selector').removeClass('active');
                $('#property-selector').addClass('active');
            }

            if (activeElement.hasClass('item-type')) {
                $('#resource-class-selector').addClass('active');
                $('#property-selector').removeClass('active');
            }
        });

        $('#property-selector li.selector-child').on('click', function(e){
            e.stopPropagation();
            //looks like a stopPropagation on the selector-parent forces
            //me to bind the event lower down the DOM, then work back
            //up to the li
            var targetLi = $(e.target).closest('li.selector-child');
            if (activeElement == null) {
                alert("Select an element at the left before choosing a property.");
            } else {
                //first, check if the property is already added
                var hasMapping = activeElement.find('ul.mappings li[data-property-id="' + targetLi.data('property-id') + '"]');
                if (hasMapping.length === 0) {
                    var elementId = activeElement.data('element-id');
                    var newInput = $('<input type="hidden" name="column-property[' + elementId + '][]" ></input>');
                    newInput.val(targetLi.data('property-id'));
                    var newMappingLi = $('<li class="mapping" data-property-id="' + targetLi.data('property-id') + '">' + targetLi.data('child-search') + actionsHtml  + '</li>');
                    newMappingLi.append(newInput);
                    activeElement.find('ul.mappings').append(newMappingLi);
                } else {
                    alert('Column is already mapped');
                }
            }
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

        $('body').on('click', '.csv-import-fieldset-label, .csv-import-fieldset-label span', function(e) {
            e.stopPropagation();
            e.preventDefault();
            var target = $(e.target);
            if(! target.attr('id') ) {
                target = target.parent();
            }
            var fieldsetId = target.attr('id') + '-fieldset';
            $('#' + fieldsetId).toggle();
            var arrows = $('.expand, .collapse', target);
            arrows.toggleClass('expand collapse');
            if (arrows.hasClass('expand')) {
                arrows.attr('aria-label','Expand');
            } else {
                arrows.attr('aria-label','Collapse');
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
            //e.stopPropagation();
            var currentSidebar = $('.sidebar.active');
            var actionElement = $(this);
            actionElement.parent().addClass('active');
            var target = '#' + actionElement.data('sidebar');
            if (currentSidebar.attr('id') != target) {
                currentSidebar.removeClass('active');
            }
            Omeka.openSidebar(actionElement, target);
        });
        
        $('#flags-sidebar li, #media-sidebar li').on('click', function(e){
            e.stopPropagation();
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
                    var name = flagType + "[" + index + "]";
                    var newInput = $('<input type="hidden" name="' + name + '" value="1" ></input>');
                    var newMappingLi = $('<li class="mapping ' + flagType + '">' + flagName  + actionsHtml  + '</li>');
                    newMappingLi.append(newInput);
                    activeElement.find('ul.mappings').append(newMappingLi);
                } else {
                    alert('Column is already mapped');
                }
            }
        });
    });
})(jQuery);
