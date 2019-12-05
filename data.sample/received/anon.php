<?php
$names = <<< EOL
Reid Reddish
Iona Izzard
Birdie Becenti
Euna Eifert
Jacinto Jain
Jeanetta Jeffress
Clay Colton
Teresia Tanksley
Phebe Prewitt
Genny Galles
Vincenzo Vandyne
Shizuko Stocks
Ina Inniss
Loraine Lasiter
Denese Drucker
Ruthie Rizzi
Isaac Irving
Theodore Teasley
Vonda Vartanian
Penny Pollitt
Marg Marston
Teodoro Thatcher
Katelynn Kube
Santana Stitt
Kelvin Kuehner
Merideth Mattson
Andre Althouse
Sarita Shahan
Luciano Lebrun
Maximina Moncada
Sarina Sulzer
Sheldon Strahan
Esta Eggen
Evan Esters
Madalyn Maize
Tim Tauber
Jeraldine Janik
Jere Joesph
Camelia Cypert
Almeta Abate
Norberto Nation
Holly Hintze
Dolores Disher
Shaquita Shabazz
Angie Anwar
Cordie Cully
Jenette Judson
Aleen Amrhein
Daryl Duhaime
Adalberto Arnone
EOL;

$names = explode( "\n", $names );

$files = glob("*.json");

$translate = [];

foreach( $files as $file ) {

  $parsed = json_decode(  file_get_contents( $file ), true );
  $studentID = $parsed['student']['id'];
  if( ! array_key_exists( $studentID, $translate ) )   $translate[ $studentID ] = explode( " ", array_shift( $names ) );

  $parsed['student']['first_name'] = $translate[ $studentID ][0];
  $parsed['student']['last_name'] = $translate[ $studentID ][1];
  $parsed['student']['email'] = $studentID . '@fake-student.edu';

   file_put_contents( $file, json_encode( $parsed, JSON_PRETTY_PRINT ) );

}

var_dump( $translate );

