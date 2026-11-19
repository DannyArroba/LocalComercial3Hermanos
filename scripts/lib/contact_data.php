<?php
// Helpers for fictional fixture contacts. They never send or verify email/SMS.
function fixtureEmail(string $name, string $domain='gmail.com'): string {
    $parts=preg_split('/\s+/u',trim($name));
    if(count($parts)<2)throw new InvalidArgumentException('Se requiere nombre y apellido.');
    $surname=count($parts)>=4?$parts[count($parts)-2]:$parts[count($parts)-1];
    $clean=fn($word)=>preg_replace('/[^a-z]/','',strtolower(iconv('UTF-8','ASCII//TRANSLIT',$word)));
    $first=$clean($parts[0]);$last=$clean($surname);
    if($first===''||$last==='')throw new InvalidArgumentException('Nombre no válido para el correo.');
    if(!in_array($domain,['gmail.com','hotmail.com'],true))$domain='gmail.com';
    return "$first.$last@$domain";
}

function fixturePhone(array &$used, ?callable $random=null): string {
    $random??=fn($min,$max)=>random_int($min,$max);
    do{$phone='09'.$random(3,9).sprintf('%07d',$random(1000000,9999999));}
    while(isset($used[$phone])||preg_match('/0{3,}/',$phone));
    $used[$phone]=true;return $phone;
}
