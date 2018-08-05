CKEDITOR.editorConfig = function( config ) {
   config.filebrowserBrowseUrl = '/ckfinder/ckfinder.html';
   config.filebrowserImageBrowseUrl = '/ckfinder/ckfinder.html?type=Images';
   config.filebrowserUploadUrl = '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files';
   config.filebrowserImageUploadUrl = '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
};