function class_funciones(){
	this.inicio_ajax = function(sec,title){
	    if(sec == undefined) sec = 10;
	    if(title == undefined) title = "Espere Por Favor";
	    $.blockUI
	        ({
	            css: {
	            border: 'none',
	            padding: '20px',
	            backgroundColor: '#000',
	            '-webkit-border-radius': '10px',
	            '-moz-border-radius': '10px',
	            opacity: .5,
	            color: '#fff',
	            '-webkit-border-radius': '10px',
	            '-moz-border-radius': '10px',
	        },

	            message: "<h4>"+title+"</h4> <img src='public/img/ajax-loader.gif' /> "
	        });
	       setTimeout($.unblockUI, sec * 100);
	}

	this.mostrar_div = function(id,sec){
	    if(id == undefined) id = "";
	    if(sec == undefined){ sec = 3; }
	    $(id).show();
	    $('html,body').animate({scrollTop: $(id).position().top}, 800, 'swing');

	    return false;
	}

	this.enviarMensaje = function(){
    	obj_funciones.inicio_ajax();
    	setTimeout(function(){
	    	 $.ajax({
		        type: $("#formcontacto").attr('method'),
	   			url: $("#formcontacto").attr('action'),
	   			data: $("#formcontacto").serialize(),
		        cache : false,
	            async : false,
		        dataType: 'json',
		    success: function(data){
		    	if(data.success == true){   
	                $("#msj_alert").html(data.mensages);
	                obj_funciones.mostrar_div("#msj_alert");
	                $('#formcontacto')[0].reset();
	            }
	            else{
	                $("#msj_alert").html(data.mensages);
	                 obj_funciones.mostrar_div("#msj_alert");
	            }
	         }  
           }); 
	     },200);	
	}

	this.modal = function(id,url,div,modal){
	 if(modal == undefined) modal = "";
	  $.ajax({
	      type: 'POST',
	      url: url,
	      data: id,
	      beforeSend: function (){
	           $(modal).modal({ keyboard:true}, 'show');
	           $(div).html('Cargando <i class="fa fa-refresh fa-spin"></i>');
	      },
	      success: function(data){
	            if(data != ""){
	                if(modal != "")
	                    $(modal).modal('show');
	                    $(div).html(data);
	           }
	      },
	  });
	}
}
var obj_funciones = new class_funciones();