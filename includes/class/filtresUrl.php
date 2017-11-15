<?php

	function CheckEncoding($val){

		if( empty($val) || strlen($val) <1)
			return '';

		$v = mb_detect_encoding($val);

		if($v !=false &&  $v != 'ISO-8859-1')
			$val = mb_convert_encoding($val, 'ISO-8859-1', $v);

		$val = html_entity_decode($val);
		return $val;
	}


	//fct de service de getArrayNomNumFromBDD, qui récupére les nom dans la bdd, mais il ne sonts pas compatible avec des urls.
	function modifStrangeChars($String) { //elle sert donc a remplacer les caractéres nom compatible avec les urls.

		//Liste des caractéres a remplacer: accentué par des lettres non accentué, plus quelques autres trucs dangeureux dans les urls.
		// Le \ est remplacé en dernier, car dois l'etre aprés les caractéres ' et " qu'il protége, sinon on pert la protection et on  risque des bug.
		// Ici il y a 2\, car le premier 	protége le second dans le code local...(compliqué tout ca...)
		$array_supersededChars = array('á','à','â','ä','ã','å','ç','é','è','ê','ë','í','ì','î','ï','ñ','ó','ò','ô','ö','õ','ú','ù','û','ü','ý','ÿ',"&Agrave;","&agrave;", "&Acirc;", "&acirc;", "&Ccedil;", "&ccedil;", "&Egrave;", "&egrave;","&Eacute;", "&eacute;", "&Ecirc;", "&ecirc;", "&Euml;", "&euml;", "&Icirc;" ,"&icirc;" ,"&Iuml;", "&iuml;", "&Ocirc;", "&ocirc;", "&OElig;", "&oelig;", "&Ugrave;", "&ugrave;", "&Ucirc;", "&ucirc;", "&Uuml;","&uuml;", " ", "/", "-", "'", ".", "&", "?", "=", "\\", "_", "(", ")","+", "&");

		$array_supplantChars = array('a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y',"A", "a", "A", "a", "C", "c", "E", "e", "E", "e", "E", "e", "E", "e", "I", "i", "I", "i", "O", "o", "OE", "oe", "U", "u", "U", "u", "U", "u", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "&amp;");



			$String = str_replace($array_supersededChars, $array_supplantChars, $String );

		$String = preg_replace('`-+`', '-', $String);// vire les - en double.

		return $String;

	}


	//fct de service de rebuldUrls. elle se conecte a la base de donée, et extrais de la table passé en paramétre un array associant numéro et nom. voir exemple en dessous.
	function getArrayNomNum($table) {

		require_once("bdd.php");//conection a la bdd

		$rawSQLreply = mysql_query("SELECT id, nom FROM ".$table." GROUP BY id ORDER BY id"); // Requête SQL, récupére les ID et les nom

		while ($data = mysql_fetch_array($rawSQLreply) ) {//on passe en revue les ligne de la répose

			$arrayReturn[ $data['id'] ] = modifStrangeChars(CheckEncoding($data['nom'])); // et on construit arrayreturn pile comme on veux.
		}

		return ($arrayReturn);// au final, ce truc associe les ID de la bdd et la version sans caractéres spéciaux du nom

	}

	/*getArrayNomNumFromBDD("lot") renvois ca:

	 Array
	(
	    [1] => Revetement_mur_plafond
	    [2] => Menuiserie
	    [3] => Platrerie_Isolation
	    [4] => Revetement_sol
	    [5] => Plomberie
	    [6] => Maconnerie
	    [7] => Electricite
	)*/


	//Le filtre "sortie". Cette fct encadrera les urls(dans le php, dans le html, dans le javascript si faut!) et transforme les numéros en noms.
	function rebuldUrls($url) {

		$array_Path_Gets = explode("?", $url); //on sépart le chemin des  gets.
		$array_GetsIso = explode("&", $array_Path_Gets[1]); // on sépart les gets entre eux.

		$i=0;//compteur
		foreach($array_GetsIso as $OneGet) {//parcour les diférents gets

			$arrayGetNameAndGetValue = explode("=",$OneGet ); //On explose pour récupérer nom et num

			switch($arrayGetNameAndGetValue[0]) {// en fonction du GetName

				case 'lot': {//si on tombe sur le get lot

					$arrayLot = getArrayNomNum("lot"); //on vas chercher l'aray qui gére cet affaire.
					$arrayGetNameAndGetValue[1] = $arrayLot[$arrayGetNameAndGetValue[1]]; //on transforme le num en nom.
					$array_GetsIso[$i] = implode("=", $arrayGetNameAndGetValue); //et on réasemble dans Array_GetIso
					break;
				}

				case 'n_fam': {//si on tombe sur le get fam

					$arrayFamilles = getArrayNomNum("famille"); //on vas chercher l'aray qui gére cet affaire.
					$arrayGetNameAndGetValue[1] = $arrayFamilles[$arrayGetNameAndGetValue[1]]; //on transforme le num en nom.
					$array_GetsIso[$i] = implode("=", $arrayGetNameAndGetValue); //et on réasemble dans Array_GetIso

					break;
				}

				case 'presta': {//si on tombe sur le get fam

					$arrayPresta = getArrayNomNum("prestation"); //on vas chercher l'aray qui gére cet affaire.
					$arrayGetNameAndGetValue[1] = $arrayPresta[$arrayGetNameAndGetValue[1]]; //on transforme le num en nom.
					$array_GetsIso[$i] = implode("=", $arrayGetNameAndGetValue); //et on réasemble dans Array_GetIso

					break;
				}

			}

			$i++;
		}


		/**	DEB @remarks  OSCIM Modif Fix encdage and html entitites in url */
			$urlRebuld= $array_Path_Gets[0]."?".implode("&", $array_GetsIso);

		/**	END  @remarks  OSCIM Modif */


		Return $urlRebuld; // et paf^^

	}

// filtre "entré". Récupére les get avec les nom, chope un aray nom/num dans la bdd avec getArrayNomNum() et remplace les nom par les num dans les $_GET.

	if (isset($_GET['lot'])) {//on verifie l'existence du get

		$arrayNomNumLot= GetArrayNomNum('lot');// on chope l'array utile.

		if (in_array($_GET['lot'],$arrayNomNumLot )) {
		//on verifie que le get actuel est une valeur de l'array. rend posible une demi-implémentation sans bug.

			$_GET['lot']= array_search($_GET['lot'],$arrayNomNumLot );//on redéfini le get selon l'array.

		}
	}

	if (isset($_GET['n_fam'])) {

		$arrayNomNumFam= GetArrayNomNum('famille');

		if (in_array($_GET['n_fam'],$arrayNomNumFam)) {

			$_GET['n_fam']= array_search($_GET['n_fam'],$arrayNomNumFam);

		}

	}

	if (isset($_GET['presta'])) {

		$arrayNomNumPresta= GetArrayNomNum('prestation');
// 		print_r($arrayNomNumPresta);
		if (in_array($_GET['presta'],$arrayNomNumPresta )) {

			$_GET['presta']= array_search($_GET['presta'],$arrayNomNumPresta);

		}

	}
	