$(document).ready(function () {

    $.ajax({
        url: "ajax/inspect.php",
        type: "POST",
        data: {},
        beforeSend: function () {
            $("div#loader").css("display", "block");
        },
        success: function (data, textStatus, jqXHR ) {

            if(jqXHR.getResponseHeader('Content-Type') !== 'application/json'){
                data = JSON.parse(data);
            }

            let data1 = Object.assign([], data.information_schema);
            let data2 = Object.assign([], data.information_schema);

            drawTableMain(data1);          
            drawSelectOptionsColumns(data2, data.target);

            $("div#loader").css("display", "none");
        },
        error: function () {
            $("div#loader").css("display", "none");
        }
    });

    
    function drawTableMain(data, n = 1){

        if(data === undefined || data.length === 0){
            return null;
        }

        let entity = data.shift();

        if(n === 1){
            let HTMLthead = buildThead(entity);
            $('#table-main > thead').html(HTMLthead);
        }

        let HTMLtbody = buildTbody(entity);
        $('#table-main > tbody:first').append(HTMLtbody);

        n++;
        drawTableMain(data, n);
    }
    
    function drawSelectOptionsColumns(data, configTarget = null){

        if(data === undefined || data.length === 0){
            return null;
        }

        let entity = data.shift();

        let HTMLoption = '';
        HTMLoption = buildSelectOptionsTableName(entity, configTarget);
        if(HTMLoption.length > 0){
            $('#target_table').append(HTMLoption);
        }

        $('#target_column_id').append(buildSelectOptionsColumnName('target_column_id', entity, configTarget));
        $('#target_column_target').append(buildSelectOptionsColumnName('target_column_target', entity, configTarget));

        drawSelectOptionsColumns(data, configTarget);
    }

    function buildSelectOptionsColumnName(HTMLid, entity, configTarget = null){

        let selected = '';
        if(configTarget.hasOwnProperty(HTMLid) && 
           configTarget[HTMLid] === entity.COLUMN_NAME
           ){
            selected = 'selected="selected"';
        }

        let HTMLoption = '';
        if (entity.hasOwnProperty('COLUMN_NAME')  
            && $('#'+ HTMLid +' option[value="'+ entity.COLUMN_NAME +'"]').length === 0
            ){
            HTMLoption = '<option value="' + entity.COLUMN_NAME + '" '+ selected +'>' + entity.COLUMN_NAME + '</option>';
        }

        return HTMLoption;
    }

    function buildSelectOptionsTableName(entity, configTarget = null){

        let selected = '';
        if(configTarget.hasOwnProperty('target_table') && 
           configTarget.target_table === entity.TABLE_NAME
           ){
            selected = 'selected="selected"';
        }
        
        let HTMLoption = '';
        if (entity.hasOwnProperty('TABLE_NAME') && $('#target_table option[value="'+ entity.TABLE_NAME +'"]').length === 0) {
            HTMLoption = '<option value="' + entity.TABLE_NAME + '" '+ selected +'>' + entity.TABLE_NAME + '</option>';
        }

        return HTMLoption;
    }

    function buildThead(entity){
        let columns = Object.keys(entity);
        let HTMLthead = '';

        HTMLthead += '<tr>';
        for(let i = 0; i < columns.length; i++) {
            HTMLthead += '<th>'+ columns[i] +'</th>'; 
        }                       
        HTMLthead += '</tr>';

        return HTMLthead;
    }

    function buildTbody(entity){
        let entityValueList = Object.values(entity);
        let HTMLtbody = '';
        HTMLtbody += '<tr>';

        for(let i = 0; i < entityValueList.length; i++) {
            HTMLtbody += '<td>'+ entityValueList[i] +'</td>'; 
        }

        HTMLtbody += '</tr>';

        return HTMLtbody;
    }

    // -------------------------------------------------------------------------
    // EventListener that listens to the "Table" selector dropdown menu.
    // -------------------------------------------------------------------------
    $('table#config_table').on('change', 'select#target_table', function (e) {

        let target_table = this.options[e.target.selectedIndex].text;        

        $.ajax({
            url: "ajax/fetchColumnsByTable.php",
            type: "GET",
            data: {},
            beforeSend: function () {
                $("div#loader").css("display", "block");
            },
            success: function (data, textStatus, jqXHR) {

                if(jqXHR.getResponseHeader('Content-Type') !== 'application/json'){
                    data = JSON.parse(data);
                }
                
                $("div#loader").css("display", "none");
                
                drawSelectOptionsColumns(data);
            },
            error: function () {
                $("div#loader").css("display", "none");
            }
        });
        
        $.ajax({
            url: "ajax/setConfig.php",
            type: "POST",
            data: {
                section: 'target',
                target_table: target_table,
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
                    $("#sys-message-target-table").html('The table was selected successfully. ');
                }
            },
            error: function () {
                $("div#loader").css("display", "none");
            }
        });
    });

    // -------------------------------------------------------------------------
    // EventListener that listens to the "Identification column" dropdown menu.
    // -------------------------------------------------------------------------
    $('table#config_table').on('change', 'select#target_column_id', function (e) {

        let target_column_id = this.options[e.target.selectedIndex].text;        

        $.ajax({
            url: "ajax/setConfig.php",
            type: "POST",
            data: {
                section: 'target',
                target_column_id: target_column_id,
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
                    $("#sys-message-target-column-id").html('The identification column was selected successfully. ');
                }
            },
            error: function () {
                $("div#loader").css("display", "none");
            }
        });
    });

    // -------------------------------------------------------------------------
    // EventListener that listens to the "Target column" dropdown menu.
    // -------------------------------------------------------------------------
    $('table#config_table').on('change', 'select#target_column_target', function (e) {

        let target_column_target = this.options[e.target.selectedIndex].text;        

        $.ajax({
            url: "ajax/setConfig.php",
            type: "POST",
            data: {
                section: 'target',
                target_column_target: target_column_target,
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
                    $("#sys-message-target-column-target").html('The target column was selected successfully. ');
                }
            },
            error: function () {
                $("div#loader").css("display", "none");
            }
        });
    });


});