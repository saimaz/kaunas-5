$(document).ready(function() {
    var options=[];
    $('.dropdown-menu a').on('click', function (event) {
        var $target = $(event.currentTarget),
            val = $target.attr('data-value'),
            $inp = $target.find('input'),
            idx;
    
        if (( idx = options.indexOf(val) ) > -1) {
            options.splice(idx, 1);
            setTimeout(function () {
                $inp.prop('checked', false)
            }, 0);
        } else {
            options.push(val);
            setTimeout(function () {
                $inp.prop('checked', true)
            }, 0);
        }
        console.log( options );
        $( ".tags" ).empty();
        for(var i=0; i<options.length; i++)
        {
            $( "<li><span class='tag'>" + options[i] +"</span></li>" ).appendTo( ".tags" );
        }
        $(event.target).blur();
        return false;
    });
});