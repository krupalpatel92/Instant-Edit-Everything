jQuery(document).ready(function($){

    $(document).on("change", ".ine_select_box",function(){
        var target_url = $(this).val();
        if (target_url!=""){
            window.location.href = $(this).val();
        }        
    });

    $(".ine_select_box").select2();

});