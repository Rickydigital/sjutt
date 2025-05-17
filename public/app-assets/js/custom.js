document.addEventListener('DOMContentLoaded',function(){
    const select2Elements = document.querySelectorAll('.select2');

    select2Elements.forEach(function(select2Element){
        $(select2Element).select2();
    });
});