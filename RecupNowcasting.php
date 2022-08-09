<?php



	// affiche les messages d'erreur dans la page web

	ini_set("display_errors", 1);



	echo "V16.3"."<br>";



	// echo $_SERVER['DOCUMENT_ROOT']."<br>";

	//  répertoire racine = /home/visionmeteo/ftp/files



	

	$nomFichBrut = "SCRIBE.NWCSTG.".gmdate("m.d.H")."Z.n";

	//$nomFichBrut = "SCRIBE.NWCSTG."."01.19.12"."Z.n";

	

	

	if (file_exists("/home/visionmeteo/ftp/files/Nowcasting/".$nomFichBrut.".Z")) {

			

			echo $nomFichBrut.".Z"."<br>";

			

			$file = $nomFichBrut.".Z";

			shell_exec("gunzip  '/home/visionmeteo/ftp/files/Nowcasting/$file' 2>&1");

			

			//sleep(1);

			$fichBrut = file("/home/visionmeteo/ftp/files/Nowcasting/".$nomFichBrut);



			for ($indexLines=0; $indexLines <= sizeof($fichBrut)-1; $indexLines++)	{

			//for ($indexLines=0; $indexLines <= 27; $indexLines++)	{

				

				if (strpos($fichBrut[$indexLines],"STN:")===0){



					$codeStation = str_replace(' ', '', substr($fichBrut[$indexLines], 5, 4));		



					$fichStation = fopen("/home/visionmeteo/ftp/files/Nowcasting/Csv/".$codeStation.".csv", "w");

					$entetes = ["DateHeure","Neb","Pfd","PCPN1","POP1","PCPN2","POP2","PCPN3","POP3","PoP","AccMx","TypPrecip","TaMin","TdMin","DD","Vit","VitMax","Vis","TPS","U","Dir"];

					$lignFich = $indexLines;

					

					fputcsv($fichStation, $entetes);

						

					for ($ligData=$lignFich+3; $ligData <= $lignFich + 27; $ligData++){

					//for ($ligData=$lignFich+3; $ligData <= $lignFich+3; $ligData++){

						

						switch ($ligData) {

							

							case $lignFich + 6:

							case $lignFich + 10:

							case $lignFich + 12:

							case $lignFich + 16: 

							case $lignFich + 20: 

							case $lignFich + 24: 					

							break;	

										

							default:					

								//$lignStation[0] = str_replace(' ', '', substr($fichBrut[$ligData], 0, 13));

								$lignStation[0] = substr($fichBrut[$ligData], 0, 4)."-";

								$lignStation[0]=$lignStation[0].substr($fichBrut[$ligData], 4, 2)."-";

								$lignStation[0]=$lignStation[0].substr($fichBrut[$ligData], 6, 2)." ";

								$lignStation[0]=$lignStation[0].substr($fichBrut[$ligData], 9, 2).":00"; // DateHeure

								$lignStation[0] = date("Y-m-d H:i", strtotime($lignStation[0]."-4 hours"));

													

								$lignStation[1] = str_replace(' ', '', substr($fichBrut[$ligData], 15, 2)); // Neb

								$lignStation[2] = str_replace(' ', '', substr($fichBrut[$ligData], 18, 3)); // Pfd

								$lignStation[3] = str_replace(' ', '', substr($fichBrut[$ligData], 23, 3)); // PCPN1

								$lignStation[4] = str_replace(' ', '', substr($fichBrut[$ligData], 28, 3)); // POP1

								$lignStation[5] = str_replace(' ', '', substr($fichBrut[$ligData], 33, 3)); // PCPN2

								$lignStation[6] = str_replace(' ', '', substr($fichBrut[$ligData], 38, 3)); // POP2

								$lignStation[7] = str_replace(' ', '', substr($fichBrut[$ligData], 43, 3)); // PCPN3

								$lignStation[8] = str_replace(' ', '', substr($fichBrut[$ligData], 48, 3)); // POP3

								$lignStation[9] = str_replace(' ', '', substr($fichBrut[$ligData], 52, 3)); // PoP

								$lignStation[10] = str_replace(' ', '', substr($fichBrut[$ligData], 57, 3)); // AccMx

								$lignStation[11] = str_replace(' ', '', substr($fichBrut[$ligData], 62, 2)); // TypPrecip

								$lignStation[12] = str_replace(' ', '', substr($fichBrut[$ligData], 65, 5)); // TaMin

								$lignStation[13] = str_replace(' ', '', substr($fichBrut[$ligData], 71, 5)); // TdMin

								$lignStation[14] = str_replace(' ', '', substr($fichBrut[$ligData], 77, 3)); // DD			

								$lignStation[15] = str_replace(' ', '', substr($fichBrut[$ligData], 81, 3)); // Vit

								$lignStation[16] = str_replace(' ', '', substr($fichBrut[$ligData], 85, 3)); // VitMax

								$lignStation[17] = str_replace(' ', '', substr($fichBrut[$ligData], 89, 5)); // Vis

								$lignStation[18] = str_replace(' ', '', substr($fichBrut[$ligData], 95, 2)); // TPS

								

								// humidite relative de l'air (U)

								$ratioTa = 17.2694*($lignStation[12]/($lignStation[12]+238.3));

								$ratioTd = 17.2694*($lignStation[13]/($lignStation[13]+238.3));

								$expRatioTa = pow (2.718, $ratioTa); // 2.718^x equivalent a EXP(x)

								$expRatioTd = pow (2.718, $ratioTd);

								$lignStation[19]= round(100*($expRatioTd/$expRatioTa));

								

								// direction cardinale du vent (Dir)

								switch(true){

									case ($lignStation[14] < 22): $lignStation[20]="N"; break;

									case ($lignStation[14] < 67): $lignStation[20]="NE"; break;

									case ($lignStation[14] < 112): $lignStation[20]="E"; break;

									case ($lignStation[14] < 157): $lignStation[20]="SE"; break;

									case ($lignStation[14] < 202): $lignStation[20]="S"; break;

									case ($lignStation[14] < 247): $lignStation[20]="SO"; break;

									case ($lignStation[14] < 293): $lignStation[20]="O"; break;

									case ($lignStation[14] < 337): $lignStation[20]="NO"; break;

									default : $lignStation[14]="N"; break;						

								}	

								

								//print_r($lignStation);								

								fputcsv($fichStation, $lignStation);

								

							break;		

							

						}			

							

					}	

					

					$lignStation = array();

					

					fclose($fichStation);

						

				}	



			}

			

			// shell_exec("mv ".$nomFichBrut." Archive/");

			// n'arrive pas a deplacer le fichier d'un repertoire a un autre



}



?>