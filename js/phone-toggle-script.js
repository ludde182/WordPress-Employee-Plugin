jQuery(document).ready(function($){
  
    $('.employee_phone > a').click(function (event) {
      if($(window).width() > 600) {
        event.preventDefault();
        var clicks = $(this).data('clicks');
        var phone = this.href.substring(4);
      
        if (clicks) {
          this.text = 'Phone';
        } else {
          this.text = phone;
        }
      
        $(this).data("clicks", !clicks);
      }
    });
});