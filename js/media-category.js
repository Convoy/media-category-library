jQuery(document).ready(function() {

        // change all text fields to selects

        jQuery('tr.' + media_category.taxonomy_name + ' input[type="text"]').each(function () {
                change_to_select(jQuery(this));
        });

        // change select to text

        jQuery('.add-new-category').live('click', function() {
                var field = jQuery(this).siblings('tr.' + media_category.taxonomy_name + ' select');
                var id = field.attr('id');
                var val = field.val();
                jQuery(this).remove();
                var text = jQuery('<input type="text">');
                text.val(val);
                field.replaceWith(text);
                text.attr('id',id);
                text.attr('name',id);
                text.after(' <a href="#" class="cancel-new-category">Cancel</a>');
                return false;
        });

        // change select to text

        jQuery('.del-category').live('click', function() {
                var id = jQuery('#attachment_id').val();
                var field = jQuery(this).siblings('tr.' + media_category.taxonomy_name + ' select');
                var val = field.val();
                var cat_id = media_category.cats[val];
                alert('Deleting cat: ' + cat_id + ' with attachment_id: ' + id);
                return false;
        });

        // change text to select

        jQuery('.cancel-new-category').live('click', function() {
                var field = jQuery(this).siblings('tr.' + media_category.taxonomy_name + ' input[type="text"]');
                jQuery(this).remove();
                change_to_select(field);
                return false;
        });

        // change all text fields to selects after upload

        if(typeof uploader != 'undefined') {
                uploader.bind('UploadProgress', function() {
                        //if (uploader.total.uploaded > 0) {
                                jQuery('img.mcl-loader').remove();
                                jQuery('tr.' + media_category.taxonomy_name + ' input[type="text"]').hide();
                                jQuery('tr.' + media_category.taxonomy_name + ' input[type="text"]').after('<img class="mcl-loader" src="' + media_category.plugin_url + 'images/ajax-loader.gif">');
                        //}
                });
                uploader.bind('UploadComplete', function(up, file, response) {
                        setTimeout(function() {
                                jQuery('tr.' + media_category.taxonomy_name + ' input[type="text"]').each(function () {
                                        jQuery('tr.' + media_category.taxonomy_name + ' input[type="text"]').show();
                                        jQuery('img.mcl-loader').remove();
                                        change_to_select(jQuery(this));
                                });
                        },3000);
                });
        }
});

// function that changes the taxonomy textfield to a select

function change_to_select(field) {
        if(media_category.options.length > 0) {
                var id = field.attr('id');
                var val = field.val();
                var select = jQuery('<select></select>');
                select.append('<option value="">--Select--</option>');
                jQuery.each(media_category.options, function(val, text) {
                        select.append('<option>'+text+'</option>');
                });
                select.val(val);
                field.replaceWith(select);
                select.attr('id',id);
                select.attr('name',id);
                var links = ' <a href="#" class="add-new-category">' + media_category.add_label + '</a>';
                links +=  ' &nbsp;&nbsp;<a href="#" class="del-category">' + media_category.del_label + '</a>';
                select.after(links);
        }
}