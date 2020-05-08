$(window).ready(function () {

    // -------------------------------------------------------------------------
    // EventListener that listens to the "Translation table" button. 
    // -------------------------------------------------------------------------
    $('button#translation_table_toggle_dialogbox_button').on('click', function (e) {
        
        $.ajax({
            url: "ajax/fetchTranslationTable.php",
            type: "GET",
            data: {},
            beforeSend: function () {
                $("div#loader").css("display", "block");
            },
            success: function (data, textStatus, jqXHR) {
                
                if(jqXHR.getResponseHeader('Content-Type') !== 'application/json'){
                    data = JSON.parse(data);
                }
                
                let translationTable = Object.values(data);
                
                let htmlContent = '';
                for(let i = 0; i < translationTable.length; i++) {
                    htmlContent += translationTable[i]; 
                }
                
                let sqrtWidth = Math.sqrt($(window).width());
                
                $( "#dialog" ).dialog({
                    width: sqrtWidth * sqrtWidth - (sqrtWidth * 10),
                    height: $(window).height(),
                });
                                
                $("#translation_table_textarea").html(htmlContent);
                
                $("div#loader").css("display", "none");
            },
            error: function (jqXHR, textStatus, errorThrown) {             
                
                $("#sys-message").html(errorThrown + '. <br>');
                
                console.log(jqXHR);
                if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('error')){
                    $("#sys-message").append(jqXHR.responseJSON.error);
                }
                
                $("div#loader").css("display", "none");
            }
        });

    });
    
    $('div#dialog').on('click', 'button#translation_table_save_button', function (e) {
        
        $.ajax({
            url: "ajax/setTranslationTable.php",
            type: "POST",
            data: {
                translation_table_content: $('#translation_table_textarea').val(),
            },
            beforeSend: function () {
                // $("div#loader").css("display", "block");
            },
            success: function (data, textStatus, jqXHR) {

                if(jqXHR.getResponseHeader('Content-Type') !== 'application/json'){
                    data = JSON.parse(data);
                }

                $("div#loader").css("display", "none");
                $("div#dialog").dialog("close");
                
                if (data.hasOwnProperty('success') && data.success === true) {
                    $("#sys-message").html('Translation table set. ');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {

                $("div#loader").css("display", "none");

                if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('error')){
                    $("#sys-message").html(jqXHR.responseJSON.error);
                }
            }
        });
        
        
    });
    
    // -------------------------------------------------------------------------
    // EventListener that listens to the "Reinterpret" button. 
    // -------------------------------------------------------------------------
    $('button#reinterpret').on('click', function (e) {
        
        $.ajax({
            url: "ajax/reinterpret_custom.php",
            type: "POST",
            data: {
                encoding_for_db_connection: $('#encoding_for_db_connection').val().trim(),
                encoding_from: $('#encoding_from').val().trim(),
                encoding_to: $('#encoding_to').val().trim(),
            },
            beforeSend: function () {
                $("div#loader").css("display", "block");
            },
            success: function (data, textStatus, jqXHR) {

                console.log(data);

                if(jqXHR.getResponseHeader('Content-Type') !== 'application/json'){
                    data = JSON.parse(data);
                }
                                
                $("#sys-message").html(data.message);
                
                $("div#loader").css("display", "none");
            },
            error: function (jqXHR, textStatus, errorThrown) {             
                
                $("#sys-message").html(errorThrown + '. <br>');
                
                console.log(jqXHR);
                if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('error')){
                    $("#sys-message").append(jqXHR.responseJSON.error);
                }
                
                $("div#loader").css("display", "none");
            }
        });
        

    });

});