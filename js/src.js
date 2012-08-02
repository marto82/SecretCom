$(document).ready(function() {
    serviceBaseUrl = "/SecretCom";
    createChannel();
    openChannel();
    aboutThis();
    contact();
    createChannelList();
    deleteChannel()
});

function createChannel() {
    $('#buttonlist').buttonset();
    $('#create_channel').dialog({
        width: 'auto',
        height: 'auto',
        autoOpen : false
    });
		
    $("#create_button").button().click(function() {
        var create_dialog = $("#create_channel");
        var create_button = $(this);
        create_dialog.dialog("open");
    });
    $("#create_button").css({
        'width':'230px'
    });
    $('#closeButton').click(function() {
        $("#create_channel").dialog("close");
        $('#createError').html('');
    });
	
    $('#createButton').click(buttonCreateChannel);
}

function buttonCreateChannel() {
    var channelName = $('#channelName').val();
    var password = $('#password').val();
    var data = {
        "channelName": channelName, 
        "password": password
    };
    $('#channelName').attr('value', '');
    $('#password').attr('value', '');
    $.ajax({
        url: serviceBaseUrl + '/create-channel',
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(data),
        success: function(data) {
            oChannel(channelName, password, data);
            $('#channelName').attr('value', '');
            $('#password').attr('value', '');
            $("#create_channel").dialog("close");
        },
        error: function (request, status, error) {
            my_err = jQuery.parseJSON(request.responseText);
            $('#createError').html(my_err.error);
        }
    });
}

function openChannel() {
    $('#openbuttonlist').buttonset();
    $('#openChannel').dialog({
        width: 'auto',
        height: 'auto',
        autoOpen : false
    });

    $("#open_button").button().click(function() {
        var open_dialog = $("#openChannel");
        var create_button = $(this);
        open_dialog.dialog("open");
    });
    $("#open_button").css({
        'width':'230px'
    });
    
    $('#openChannelButton').click(buttonOpenChannel);
    
    $('#closeChannelButton').click(function() {
        $('#openChannelName').attr('value', '');
        $('#openChannelPassword').attr('value', '');
        $("#openChannel").dialog("close");
        $('#openError').html('');
    });
}


function oChannel(channelName, password, data) {
    var div_count = $('.dialog_window').length + 1;
    if(div_count < 3) {
        var div_id = 'channel_' + channelName;
	
        var div_title = channelName;
        var sendFile = '<div id="uploadForm">' +
        '<form id="file_upload_form" method="post" enctype="multipart/form-data" action="' + serviceBaseUrl + '/send-file">' +
        '<input name="file" id="file" size="27" type="file" /><br />' +
        '<iframe id="upload_target" name="upload_target" src="" style="width:0;height:0;border:0px solid #fff;"></iframe>' +
        '</form><button id="submit" name="action" >Upload</buttom><br /></div>';

        $('body').append('<div class="dialog_window" id="' + 
            div_id + '"><div id="chatbox_'+ div_id +'"></div><div id="error_'+ div_id +'"></div><div id="tabs_'+ div_id +'"><ul><li><a href="#tabs-1">Send Msg</a></li><li><a href="#tabs-2">Send Url</a></li><li><a href="#tabs-3">Send file</a></li></ul><div id="tabs-1"><textarea rows="3" cols="30" id="msg_'+ div_id +'"></textarea><button id="btnsend_'+ div_id +'">SEND</button></div><div id="tabs-2"><div id="urlSend_'+ div_id +'">URL:<input type="text" id="url_'+ div_id +'"/></div><button id="btnsendurl_'+ div_id +'">SEND</button><div id="desc_'+ div_id +'">Description:<textarea rows="2" cols="30" id="description_'+ div_id +'"></textarea></div></div><div id="tabs-3">'+ sendFile +'</div></div>');
        $('#tabs_'+ div_id).tabs();
        if ( $.browser.msie ) {
            $('#tabs-3').html('<p style="color:red;">Sorry!!!This browser not support file share!</p>');
        }
        
        $('#btnsend_' + div_id).button().click(function() {
            sendMessage(channelName, password, $('#msg_' + div_id).val(), div_id);
            reloadContent(channelName, password, div_id);
            $('#error_' + div_id).css({
                'color' : 'red', 
                'text-align' : 'center'
            });
        });

        $('#submit').button().click(function() {
            document.getElementById('file_upload_form').target = 'upload_target';
            
            var data = new FormData();
            data.append('channelName', channelName);
            data.append('password', password);
            data.append("file", document.getElementById('file').files[0]);
        
            $.ajax({
                url: serviceBaseUrl + '/send-file',
                type: 'POST',
                data: data,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                success: function (datas) {            
                    reloadContent(channelName, password, div_id);
                    $('#f1_upload_process').hide();
                    $('#error_'+ div_id).html('');
                    $('#file').val('');
                },
                error: function(request, status, error) {
                    $('#f1_upload_process').hide();
                    my_err = jQuery.parseJSON(request.responseText);
                    $('#error_'+ div_id).html(my_err.error);
                    $('#f1_upload_process').hide();
                }
            });
            $('#error_' + div_id).css({
                'color' : 'red', 
                'text-align' : 'center'
            });
        
            return false;
        });
    
        $('#btnsendurl_' + div_id).button().click(function() {
            sendUrl(channelName, password, $('#url_' + div_id).val(), $('#description_' + div_id).val(), div_id);
            reloadContent(channelName, password, div_id);
            $('#url_' + div_id).attr('value', '');
            $('#description_' + div_id).attr('value', '');
            $('#error_' + div_id).css({
                'color' : 'red', 
                'text-align' : 'center'
            });
        });
    
        $('#chatbox_' + div_id).css({
            'margin' : '0 auto', 
            'height' : '250px' ,
            'border':'1px solid black', 
            'background' : '#ebf4fb', 
            'padding-bottom' : '0px', 
            'text-align':'left', 
            'overflow' : 'auto'
        });
        $('#btnsend_' + div_id).css({
            'float' : 'left'
        });
        $('#textmsg_' + div_id).css({
            'float' : 'left'
        });
        $('#url_' + div_id).css({
            'display' : 'block',
            'float' : 'left'
        });
        $('#desc_' + div_id).css({
            'display':'block'
        });
        $('#btnsendurl_' + div_id).css({
            'margin-left':'auto',
            'margin-right':'auto',
            'float':'left'
        });
        var button = new Array();
        button.push({
            text: 'close',
            click: function() {
                $('#openChannelName').attr('value', '');
                $('#openChannelPassword').attr('value', '');
                $('#' + div_id).dialog("close");
                $('#' + div_id).remove();
            }
        });
        $('#chatbox_' + div_id).html(displayData(data));
        setInterval(function() {
            reloadContent(channelName, password, div_id);
        }, 150000);
        var dialog = $('#' + div_id).dialog({
            width: '700px',
            height: 'auto',
            title : div_title,
            autoOpen : true,
            buttons: button
        });
    }
    else {
        alert('You can open only 2 channels at once!'); 
    }
}

function buttonOpenChannel() {
    var openChannelName = $('#openChannelName').val();
    var openChannelPassword = $('#openChannelPassword').val();
    var data = {
        "channelName": openChannelName, 
        "password": openChannelPassword
    };
    
    $.ajax({
        url: serviceBaseUrl + '/open-channel',
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(data),
        success: openChannelData,
        error: function (request, status, error) {
            my_err = jQuery.parseJSON(request.responseText);
            $('#openError').html(my_err.error);
            $('#openChannelName').attr('value', '');
            $('#openChannelPassword').attr('value', '');
        }
    });
}

function openChannelData(data) {
    oChannel($('#openChannelName').val(), $('#openChannelPassword').val(), data);
    $('#openChannelName').attr('value', '');
    $('#openChannelPassword').attr('value', '');
    $("#openChannel").dialog("close");
}

function deleteChannel() {
    $('#deleteButtonlist').buttonset();
    $('#delete_channel').dialog({
        width: 'auto',
        height: 'auto',
        autoOpen : false
    });
		
    $("#delete_button").button().click(function() {
        var delete_dialog = $("#delete_channel");
        var create_button = $(this);
        delete_dialog.dialog("open");
    });
    $("#delete_button").css({
        'width':'230px'
    });
    $('#deleteCloseButton').click(function() {
        $("#delete_channel").dialog("close");
        $('#deleteChannelName').attr('value', '');
        $('#deletePassword').attr('value', '');
        $('#deleteError').html('');
        $('#deleteChannelName').show();
        $('#deletePassword').show();
        $('#nameOnChannel').show();
        $('#nameOnPassword').show();
        $('#deleteButtonChannel').show();
    });
	
    $('#deleteButtonChannel').click(buttonDeleteChannel);
}

function buttonDeleteChannel() {
    var channelName = $('#deleteChannelName').val();
    var password = $('#deletePassword').val();
    
    var data = {
        "channelName": channelName, 
        "password": password
    };
    
    $.ajax({
        url: serviceBaseUrl + '/delete-channel',
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(data),
        success: function(data) {
            var message = 'Channel <b>' + channelName + '</b> was deleted!<br />Close this dialog!';
            $('#deleteChannelName').hide();
            $('#deletePassword').hide();
            $('#nameOnChannel').hide();
            $('#nameOnPassword').hide();
            $('#deleteButtonChannel').hide();
            $('#deleteError').html(message).css({
                'color':'red'
            });
            
        },
        error: function (request, status, error) {
            var my_err = jQuery.parseJSON(request.responseText);
            $('#deleteError').html(my_err.error);
            $('#deleteChannelName').attr('value', '');
            $('#deletePassword').attr('value', '');
        }
    });
}

function htmlEncode(value) {
    return $('<div/>').text(value).html();
}

function displayData(datas) {
    var html = "";
    
    for (i in datas) {
        var data = datas[i];
        if(data.empty != null) {
            html += '<div class="empty">' + htmlEncode(data.empty)+ '</div>';
        }
        if (data.file_name != null) {
            html += ' <div class="file_name">File name: ' + htmlEncode(data.file_name) + '</div>';
        }
        if (data.url_to_file != null) {
            var imageUrl = htmlEncode(data.url_to_file);
            html += '<div class="imageUrl">Url to download: <a href="'+ imageUrl +'">'+ imageUrl +'</a></div>';
        }
        if (data.mime_type != null) {
            var mime_type = htmlEncode(data.mime_type);
            html += '<div class="mime_type">Mime type: '+ mime_type +'</div><br />';
        }
        if (data.message != null) {
            var message = htmlEncode(data.message);
            html += '<div class="message">This is message: '+ message +'</div><br />';
        }
        if (data.url != null) {
            var url = htmlEncode(data.url);
            html += '<div class="url">Url: <a href="' + url + '" target="_blank">'+ url +'</a></div>';
        }
        if (data.description != null) {
            var description = htmlEncode(data.description);
            html += '<div class="description">Description: '+ description +'</div><br />';
        }
        html += '</div>';
    }
    return html;
}

function sendMessage(channelName, password, message, div_id) {
    var data = {
        "channelName": channelName, 
        "password": password,
        "message": message
    }
    $.ajax({
        url: serviceBaseUrl + '/send-message',
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(data),
        success: function() {
            $('#error_' + div_id).html('');
        },
        error: function(request, status, error) {
            var my_err = jQuery.parseJSON(request.responseText);
            $('#error_' + div_id).html(my_err.error);
        }
    });
}

function sendUrl(channelName, password, url, description, div_id) {
    var data = {
        "channelName": channelName, 
        "password": password,
        "url": url,
        "description": description
    }
    $.ajax({
        url: serviceBaseUrl + '/send-url',
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(data),
        success: function() {
            $('#error_' + div_id).html('');
        },
        error: function(request, status, error) {
            var my_err = jQuery.parseJSON(request.responseText);
            $('#error_' + div_id).html(my_err.error);
        }
    });
}

function reloadContent(channelName, password, div_id) {
    var data = {
        "channelName": channelName, 
        "password": password
    };
    $.ajax({
        url: serviceBaseUrl + '/open-channel',
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(data),
        success: function(data) {
            $('#chatbox_' + div_id).html(displayData(data));
            $('#msg_' + div_id).attr('value', '');
        },
        error: function (request, status, error) {
            var my_err = jQuery.parseJSON(request.responseText);
            $('#openError').html(my_err.error);
        }
    });
}

function createChannelList() {
    $('#list_channels').button().click(function(){
        $("#listChannels").dialog("open");
        generateList();
    });
    $('#listChannels').dialog({
        width: 300,
        height: 500,
        autoOpen : false
    });
    
    $('#closeListChannels').button().click(function() {
        $("#listChannels").dialog("close");
        $('#listContent').html('');
        $('.formChannel').remove();
        $('#listError').html('');
    });
}

function generateList() {
    $.ajax({
        url: serviceBaseUrl + '/list-channels',
        cache: "false",
        type: "GET",
        dataType: "json",
        success: function(datas) {
            var html = '<table class="formChannel">';
            for(i in datas) {
                html += '<tr><td>';
                var data = datas[i];
                html += data;
                html += '</td></tr>';
                    
            }
            html += '</table>';
            $('#list').html(html);
        },
        error: function (request, status, error) {
            var my_err = jQuery.parseJSON(request.responseText);
            $('#listError').html(my_err.error);
        }
    });
}

function aboutThis() {
    $('#about_me').button().click(function(){
        $("#aboutMe").dialog("open");
    });
    $('#aboutMe').dialog({
        width: 600,
        height: 300,
        autoOpen : false
    });
    $('#closeAboutThis').button().click(function() {
        $("#aboutMe").dialog("close");
    });
}

function contact() {
    $('#contact_me').button().click(function(){
        $("#contact").dialog("open");
    });
    $('#contact').dialog({
        width: 'auto',
        height: 'auto',
        autoOpen : false
    });
}
