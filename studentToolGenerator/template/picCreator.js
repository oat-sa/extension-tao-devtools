define([
    'lodash',
    'taoQtiItem/qtiCreator/editor/infoControlRegistry',
    '{tool-id}/creator/widget/Widget',
    'tpl!{tool-id}/creator/tpl/{tool-base}'
], 
function(_, registry, Widget, commonTpl, markupTpl){


    /**
     * Retrieve data from manifest
     */
    var manifest = registry.get('{tool-id}').manifest;

    /**
     * Configuration of the container
     */
    var is = {
        transparent: {is-transparent}, 
        movable: {is-movable}, 
        rotatable: {
            tl: {is-rotatable-tl},
            tr: {is-rotatable-tr},
            br: {is-rotatable-br},
            bl: {is-rotatable-bl}
        },
        adjustable: {
            x:  {is-adjustable-x}, 
            y:  {is-adjustable-y}, 
            xy: {is-adjustable-xy}
        } 
    };
    is.transmutable = _.some(is.rotatable, Boolean) || _.some(is.adjustable, Boolean);

    return {
        /**
         * (required) Get the typeIdentifier of the custom interaction
         * 
         * @returns {String}
         */
        getTypeIdentifier : function(){
            return manifest.typeIdentifier;
        },
        /**
         * (required) Get the widget prototype
         * Used in the renderer
         * 
         * @returns {Object} Widget
         */
        getWidget : function(){
            return Widget;
        },
        /**
         * (optional) Get the default properties values of the PIC.
         * Used on new PIC instance creation
         * 
         * @returns {Object}
         */
        getDefaultProperties : function(pic){
            return {};
        },

        /**
         * (optional) Callback to execute on the 
         * Used on new pic instance creation
         * 
         * @returns {Object}
         */
        afterCreate : function(pic){
            //do some stuff
        },

        /**
         * (required) Returns the QTI PIC XML template 
         * 
         * @returns {function} handlebar template
         */
        getMarkupTemplate : function(){
            return markupTpl;
        },

        /**
         * (optional) Allows passing additional data to xml template
         * 
         * @returns {function} handlebar template
         */
        getMarkupData : function(pic, defaultData){
            
            defaultData = _.defaults(defaultData, {
                typeIdentifier : manifest.typeIdentifier,
                title : manifest.label,
                is: is,
                //referenced as a required file in manifest.media[]
                icon : manifest.typeIdentifier + '/runtime/media/{tool-base}.svg',
                alt : manifest.short || manifest.label
            });
            
            return defaultData;
        }
    };
});