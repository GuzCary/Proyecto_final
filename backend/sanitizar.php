<?php

function sanitizar($dato){

    $dato = htmlspecialchars($dato);
    $dato = strip_tags($dato);




    return $dato;
}



function validarNum($dato){



    if(filter_var($dato,FILTER_VALIDATE_INT)){

    return $dato;


}

return null;
}


function validarImagen($archivo) {
    if (!isset($archivo['tmp_name']) || empty($archivo['tmp_name'])) {
        return null;
    }

    // Lee los bytes internos del archivo
    $tipo = exif_imagetype($archivo['tmp_name']);

    // Lista de tipos permitidos
    $permitidos = [
        IMAGETYPE_JPEG, // JPG / JPEG
        IMAGETYPE_PNG,  // PNG
        IMAGETYPE_GIF,  // GIF
        IMAGETYPE_WEBP  // WebP
    ];

    if (in_array($tipo, $permitidos, true)) {
        return $archivo;
    }

    return null;
}









?>