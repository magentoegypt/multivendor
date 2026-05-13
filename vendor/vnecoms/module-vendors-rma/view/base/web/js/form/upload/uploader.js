define(
    [
        'Magento_Ui/js/lib/validation/validator',
        'Magento_Ui/js/form/element/file-uploader',
        'ko'
    ],
    function (
        validator,
        UpLoader,
        ko
    ) {
        return UpLoader.extend({
            defaults: {
                isMultipleFiles : true,
                template: 'Vnecoms_RMA/form/upload/uploader',
                previewTmpl: 'Vnecoms_RMA/form/upload/preview'
            },

            /**
             * Invokes initialize method of parent class,
             * contains initialization logic
             */
            initialize: function () {
                _.bindAll(this, 'reset');

                this._super()
                    .setInitialValue()
                    ._setClasses()
                    .initSwitcher();

                return this;
            },

            getClassIcon:function (file){
                var filename = file.name;
                var ext = filename.substr(filename.lastIndexOf('.') + 1);
                switch(ext.toLowerCase()){
                    case 'jpg' :
                        var classIcon = "icon-jpg";
                        return false;
                        break;
                    case 'jpeg' :
                        var classIcon = "icon-jpeg";
                        return false;
                        break;
                    case 'png' :
                        var classIcon = "icon-png";
                        return false;
                        break;
                    case 'gif' :
                        var classIcon = "icon-gif";
                        return false;
                        break;
                    case 'pdf' :
                        var classIcon = "fa-file-pdf-o";
                        break;
                    case 'zip' :
                        var classIcon = "fa-file-archive-o";
                        break;
                    case 'rar' :
                        var classIcon = "fa-file-archive-o";
                        break;
                    case 'txt' :
                        var classIcon = "fa-file-text-o";
                        break;
                    case 'csv' :
                        var classIcon = "fa-file-excel-o";
                        break;
                    case 'xlsx' :
                        var classIcon = "fa-file-excel-o";
                        break;
                    case 'doc' :
                        var classIcon = "fa-file-word-o";
                        break;
                    case 'docx' :
                        var classIcon = "fa-file-word-o";
                        break;
                    default :
                        var classIcon = "fa-file-o";
                        break;
                }

                return "fa icon-attachment "+classIcon;
            }
        });
    }
);