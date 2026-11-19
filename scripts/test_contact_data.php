<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/lib/contact_data.php';
if(fixtureEmail('María Fernanda Grefa Vera')!=='maria.grefa@gmail.com')throw new RuntimeException('No se eligió el primer apellido.');
if(fixtureEmail('Luis Cárdenas','hotmail.com')!=='luis.cardenas@hotmail.com')throw new RuntimeException('Nombre y apellido incorrectos.');
mt_srand(20261118);$used=[];$prefixes=[];
for($i=0;$i<250;$i++){
    $phone=fixturePhone($used,fn($a,$b)=>mt_rand($a,$b));
    if(!preg_match('/^09[3-9]\d{7}$/',$phone)||preg_match('/0{3,}/',$phone))throw new RuntimeException('Teléfono incorrecto.');
    $prefixes[substr($phone,0,3)]=true;
}
if(count($used)!==250||count($prefixes)<6)throw new RuntimeException('Teléfonos repetidos o poco variados.');
echo "Contactos: correos nombre.apellido y 250 teléfonos distintos y variados correctos.\n";
