$(window).load(function () {

    $.ajax({
        url: "ajax/getConfig.php",
        type: "GET",
        data: {},
        beforeSend: function () {
            $("div#loader").css("display", "block");
        },
        success: function (data, textStatus, jqXHR ) {

            if(jqXHR.getResponseHeader('Content-Type') !== 'application/json'){
                data = JSON.parse(data);
            }

            if (data.hasOwnProperty('database')) {                
                for(let key in data.database) {
                    if ($("input[name='"+ key +"']").length > 0) {
                        $("input[name='"+ key +"']").val(data.database[key]);
                    }
                }                
            }

            $("div#loader").css("display", "none");
        },
        error: function () {
            $("div#loader").css("display", "none");
        }
    });



    $("form#form-db-connection").on('submit', function(e){
        e.preventDefault();
        
        let promise = new Promise(
            function (resolve, reject) {

                $.ajax({
                    url: "ajax/testConnection.php",
                    type: "POST",
                    data: {
                        host: $("input[name=host]").val().trim(),
                        username: $("input[name=username]").val().trim(),
                        password: $("input[name=password]").val().trim(),
                        db: $("input[name=db]").val().trim(),
                    },
                    beforeSend: function () {
                        $("div#loader").css("display", "block");
                    },
                    success: function (data, textStatus, jqXHR) {
                        resolve();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        
                        data = '';
                        if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('error')){
                            data = jqXHR.responseJSON.error;
                        }
                        reject(data);
                    }
                });
            }
        );

        promise.then(
            function () {
                $.ajax({
                    url: "ajax/setConfig.php",
                    type: "POST",
                    data: {
                        section: 'database',
                        host: $("input[name=host]").val().trim(),
                        username: $("input[name=username]").val().trim(),
                        password: $("input[name=password]").val().trim(),
                        db: $("input[name=db]").val().trim(),
                    },
                    beforeSend: function () {
                        $("div#loader").css("display", "block");
                    },
                    success: function (data, textStatus, jqXHR) {

                        if(jqXHR.getResponseHeader('Content-Type') !== 'application/json'){
                            data = JSON.parse(data);
                        }

                        $("div#loader").css("display", "none");

                        if (data.hasOwnProperty('success') && data.success === true) {
                            $("#sys-message").html('The data source name was configured successfully. ');
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {

                        $("div#loader").css("display", "none");

                        if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('error')){
                            $("#sys-message").html(jqXHR.responseJSON.error);
                        }
                    }
                });
            }
        );

        promise.catch(function (data) {
            $("div#loader").css("display", "none");
            $("#sys-message").html(data);
        });
        
        

        
    });

});