$(document).ready(function () {

    // -------------------------------------------------------------------------
    // EventListener that listens to the "Reinterpret" button. 
    // -------------------------------------------------------------------------
    $('body').on('click', 'button#reinterpret', function (e) {

        $.ajax({
            url: "ajax/reinterpret_iconv.php",
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
    
        // -------------------------------------------------------------------------
    // EventListener that listens to the "Preview" button. 
    // -------------------------------------------------------------------------
    $('button#preview').on('click', function (e) {
        $.ajax({
            url: "ajax/preview_iconv.php",
            type: "POST",
            data: {
                encoding_for_db_connection: $('#encoding_for_db_connection').val().trim(),
                encoding_from: $('#encoding_from').val().trim(),
                encoding_to: $('#encoding_to').val().trim(),
                mode: 'preview',
            },
            beforeSend: function () {
                $("div#loader").css("display", "block");
            },
            success: function (data, textStatus, jqXHR) {

                if(jqXHR.getResponseHeader('Content-Type') !== 'application/json'){
                    data = JSON.parse(data);
                }
                                
                $("#sys-message").html(data.message);
                
                if(data.hasOwnProperty('data') && !$.isEmptyObject(data.data)){
                    
                    $("table#table-main").show();
                    
                    let htmlContent = '';
                    for (const property in data.data) {
                        htmlContent += '<tr><td>'+ property  +'</td><td>'+ data.data[property].old +'</td><td>'+ data.data[property].new +'</td></tr>';
                    }
                    $("#table-main tbody").html(htmlContent);                    
                }
                
                $("div#loader").css("display", "none");
            },
            error: function (jqXHR, textStatus, errorThrown) {             
                
                $("#sys-message").html(errorThrown + '. <br>');
                
                if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('error')){
                    $("#sys-message").append(jqXHR.responseJSON.error);
                }
                
                $("div#loader").css("display", "none");
            }
        });
    });

});