/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2013-2019 (original work) Open Assessment Technologies SA ;
 */

/**
 * Extension manager controller
 *
 * TODO REFACTOR this is a code dup of controller/settigns/extensionManager
 */
define([
    'jquery',
    'i18n',
    'util/url',
    'layout/section',
    'ui/feedback',
    'ui/modal'
], function($, __, urlUtil, section, feedback){

    var ext_installed = [];
    var toInstall = [];
    var indexCurrentToInstall = -1;
    var percentByExt = 0;
    var installError = 0;

    function getDependencies(extension) {
        var dependencies = [];
        $('#' + extension + ' .dependencies li:not(.installed)').each(function() {
            var ext = $(this).attr('rel');
            var deps = getDependencies(ext);
            deps.push(ext);
            dependencies = dependencies.concat(deps);
        });
        return dependencies;
    }

    //Give an array with unique values
    function getUnique(orig){
        var a = [];
        var i;
        for (i = 0; i < orig.length; i++) {
            if ($.inArray(orig[i], a) < 0){
                a.push(orig[i]);
            }
        }
        return a;
    }

    function progressConsole(msg) {
        $('#installProgress .console').append('<p>' + msg + '</p>');
        $('#installProgress .console').prop({scrollTop: $('#installProgress .console').prop("scrollHeight")});
    }

    function installNextExtension() {
        var ext = toInstall[indexCurrentToInstall];
        $('#installProgress p.status').text(__('Installing extension %s...').replace('%s', ext));
        progressConsole(__('Installing extension %s...').replace('%s', ext));
        $.ajax({
            type: "POST",
            url: urlUtil.route('install', 'ExtensionsManager', 'tao'),
            data: 'id='+ext,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    progressConsole(__('> Extension %s succesfully installed.').replace('%s', ext));

                    $('#installProgress .bar').animate({width:'+=' + percentByExt + '%'}, 1000, function() {
                        //Next
                        indexCurrentToInstall++;
                        hasNextExtensionToInstall();
                    });
                } else {
                    installError = 1;
                    progressConsole('Installation of ' + ext + ' failed');
                }
                feedback().info(data.message);
            }
        });

        if (installError) {
            progressConsole(__('A fatal error occured during the installation process.'));
        }
    }

    function hasNextExtensionToInstall() {
        if (indexCurrentToInstall >= toInstall.length) {
            toInstall = [];
            $('#installProgress .bar').animate({backgroundColor:'#bb6',width:'100%'}, 1000);
            progressConsole(__('Generating cache...'));
            $.ajax({
                type: "GET",
                url: $($('#main-menu a')[0]).prop('href'),
                success: function(data) {
                    $('#installProgress .bar').animate({backgroundColor:'#6b6'}, 1000);
                    $('#installProgress p.status').text(__('Installation done.'));
                    progressConsole(__('> Installation done.'));
                    window.location.reload();
                }
            });
        } else {
            installNextExtension();
        }
    }

    function styleTables(){
        // Clean all to make this function able to "restyle" after
        // data refresh.
        $('#Extensions_manager table tr').removeClass('extensionOdd')
                                                                            .removeClass('extensionEven');

        $('#Extensions_manager table tr:nth-child(even)').addClass('extensionEven');
        $('#Extensions_manager table tr:nth-child(odd)').addClass('extensionOdd');
    }

    function noAvailableExtensions(){
        var $noAvailableExtElement = $('<div/>');
        $noAvailableExtElement.attr('id', 'noExtensions')
                                                    .addClass('ui-state-highlight')
                                                    .text(__('No extensions available.'));

        $('#available-extensions-container').empty().append($noAvailableExtElement);
    }

    return {
        start : function start() {

            // Table styling.
            styleTables();

            $('#installProgress').hide();

            $('#addButton').click(function(e) {
                e.preventDefault();
                section.create({
                    id : 'devtools-newextension',
                    name : __('Create new extension'),
                    url : urlUtil.route('create', 'ExtensionsManager', 'taoDevTools'),
                    contentBlock : true
                })
                    .show();
            });

            //Detect wich extension is already installed
            $('#extensions-manager-container .ext-id').each(function() {
                    var ext = $(this).text();
                ext_installed.push(ext);
                $('.ext-id.ext-' + ext).addClass('installed');
            });

            $('#available-extensions-container tr input').click(function(event){
                event.stopPropagation();
            });

            $('#available-extensions-container tr input:checkbox').click(function() {
                var $installButton = $('#installButton');
                if ($(this).parent().parent().parent().find('input:checkbox:checked').length > 0){
                    $installButton.attr('disabled', false);
                }
                else{
                    $installButton.attr('disabled', true);
                }
            });

            $('#installButton').click(function(event) {
                var $modalContainer = $('#installProgress');

                event.preventDefault();

                //Prepare the list of extension to install in the order of dependency
                toInstall = [];
                $('#available-extensions-container input:checked').each(function() {
                    var ext = $(this).prop('name').split('_')[1];
                    var deps = getDependencies(ext);
                    if (deps.length) toInstall = toInstall.concat(deps);
                    toInstall.push(ext);
                });
                toInstall = getUnique(toInstall);
                if (toInstall.length == 0) {
                    window.alert(__('Nothing to install !'));
                    return false;
                }
                //Let's go
                percentByExt = 100 / toInstall.length;

                //Show the dialog with the result
                $('p.status', $modalContainer).text(__('%s extension(s) to install.').replace('%s', toInstall.length));
                $('.bar', $modalContainer).width(0);
                $('.console', $modalContainer).empty();
                progressConsole(__('Do you wish to install the following extension(s):\n%s?').replace('%s', toInstall.join(', ')));

                $('[data-control=cancel]', $modalContainer).on('click', function(e){
                    e.preventDefault();
                    $modalContainer.modal('close');
                });
                $('[data-control=confirm]', $modalContainer).on('click', function(e){
                    e.preventDefault();
                    progressConsole(__('Preparing installation...'));
                    $('.buttons', $modalContainer).remove();
                    installError = 0;
                    indexCurrentToInstall = 0;
                    installNextExtension();
                });

                $modalContainer.modal({
                    width : 400,
                    height : 300,
                    top : 150,
                    disableEscape : true,
                    disableClosing : true
                });
            });

            $('.disableButton').click(function(event) {
                var id = $(event.target).data('extid');
                $.ajax({
                    type: "POST",
                    url: urlUtil.route('disable', 'ExtensionsManager', 'taoDevTools'),
                    data: 'id='+id,
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            feedback().success(data.message);
                            setTimeout(function(){
                                window.location.reload();
                            }, 1000);
                        } else {
                            feedback().info(data.message);
                        }
                    }
                });
            });

            $('.enableButton').click(function(event) {
                var id = $(event.target).data('extid');
                $.ajax({
                    type: "POST",
                    url: urlUtil.route('enable', 'ExtensionsManager', 'taoDevTools'),
                    data: 'id='+id,
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            feedback().success(data.message);
                            setTimeout(function(){
                                window.location.reload();
                            }, 1000);
                        } else {
                            feedback().info(data.message);
                        }
                    }
                });
            });

            $('.uninstallButton').click(function(event) {
                var id = $(event.target).data('extid');
                $.ajax({
                    type: "POST",
                    url: urlUtil.route('uninstall', 'ExtensionsManager', 'taoDevTools'),
                    data: 'id='+id,
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            feedback().success(data.message);
                            setTimeout(function(){
                                window.location.reload();
                            }, 1000);
                        } else {
                            feedback().info(data.message);
                        }
                    }
                });
            });
        }
    };

});
