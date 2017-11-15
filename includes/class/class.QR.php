<?php


Class Lots{
	/**
		@var
	*/
	public $list_lot  = array();
	/**
		@var
	*/
	public $list_fam = array();
	/**
		@var
	*/
	public $list_presta = array();
	/**
		@var
	*/
	public $list_prefiltre = array();
/**
		@var
	*/
	public $list_postfiltre = array();

	/**
		@var is object
	*/
	public $current;
	/**
		@var is object
	*/
	public $current_fam;
	/**
		@var is object
	*/
	public $current_tab;
	/**
		@var is object
	*/
	public $current_presta;
	/**
		@var is object
	*/
	public $current_prefiltre;
	/**
		@var is object
	*/
	public $current_postfiltre;

	/**
		@brief constructor
	*/
	function __construct(&$api){
        $this->api = &$api;

		$sql = "SELECT id, nom, commentaire_p, commentaire_s, coefficient_marge, coefficient_main_oeuvre, cout_mini, image FROM lot WHERE 1  ";

		if( !isset($_SESSION['arbo']) || !isset($_GET['arbo']))
			$sql .= " AND status > 0 ";

		$tab_lots = $this->api->db->query($sql);
// echo $sql;
		$i = 0;
		while ($l = $this->api->db->fetch($tab_lots)) {

				if(!isset($_GET["mylot"]) || ( isset($_GET["mylot"][$i]) && $_GET["mylot"][$i] =='true') ) {

					$sql2 = "SELECT id FROM famille WHERE id_lot='" . $l['id']."'";

						if( !isset($_SESSION['arbo']) || !isset($_GET['arbo']))
							$sql2 .= " AND status > 0 ";

					$f = $this->api->db->fetch($this->api->db->query($sql2));

					$lot = new LotListener(array(
															'id' => $l['id'],
															'nom' => $l['nom'],
															'commentaire_permanent' => $l['commentaire_p'],
															'commentaire_survol' => $l['commentaire_s'],
															'coefficient_marge' => $l['coefficient_marge'],
															'cout_minimum' => $l['cout_mini'],
															'coefficient_main_oeuvre' => $l['coefficient_main_oeuvre'],
															'image' => $l['image'],
															'premiere_famille' => $f['id']
															));

					$this->list_lot[$l['id']] = $lot;
				}
				$i++;
		}

// 		print_r($this->list_lot);
	}


	public function GetList(){
		return $this->list_lot;
	}




	public function GetListFam(){
		return $this->list_fam;
	}

	public function GetSelectFam($id = 1){
		foreach($this->list_fam as $row)
// 		print_r($row);

			if($row->id = $id)
			return $row;

		return false ;
	}

	/**
		@brief Selct current Lot
	*/
	public function SelectLot($id){

		$this->current_lot = $this->list_lot[$id];

		// call famille content in lot
		$this->CallFam($id);
	}

	/**
		@brief Selct current Lot
	*/
	public function SelectPresta($id){
		// call famille content in lot
		$this->CallPresta($id);

		$this->current_presta = $this->list_presta[$id];
	}


	/**
		@brief Selct current Lot
	*/
	public function SelectPrefiltre($id){
// 	var_dump(__file__);
		// call famille content in lot
		$this->CallPrefiltre($id);

		$this->current_prefiltre = $this->list_prefiltre[$id];

	}

	/**
		@brief Selct current Lot
	*/
	public function SelectPostfiltre($id){
// 	var_dump(__file__);
		// call famille content in lot
		$this->CallPostfiltre($id);

		$this->current_postfiltre = $this->list_postfiltre[$id];

	}

	/**
		@brief Selct current Fam
	*/
	public function SelectFam($id){
		$this->current_fam = $this->list_fam[$id];
//
// 		// call famille content in lot
		$this->CallTableau($id);
	}

	/**
		@brief Get CurrentLot  Selected by SelectFunction
	*/
	public function CurrentLot(){
		return $this->current_lot;
	}

	/**
		@brief Get CurrentLot  Selected by SelectFunction
	*/
	public function CurrentFam(){
		return $this->current_fam;
	}

	/**
		@brief Get CurrentLot  Selected by SelectFunction
	*/
	public function CurrentPresta(){
		return $this->current_presta;
	}

	/**
		@brief Get CurrentLot  Selected by SelectFunction
	*/
	public function CurrentPrefiltre(){
		return $this->current_prefiltre;
	}

	/**
		@brief Get CurrentLot  Selected by SelectFunction
	*/
	public function CurrentPostfiltre(){
		return $this->current_postfiltre;
	}

	/**
		@brief Get CurrentLot  Selected by SelectFunction
	*/
	public function CurrentTab(){

		$ligne = $col = array();
		//Clean Table
		foreach($this->current_tab as $k=>$val){
			foreach($val as $ki=>$ri){
				$ligne[$k] =( ($ri ===false) ? 0 : 1) + (int)$ligne[$k];
				$col[$ki] =( ($ri ===false) ? 0 : 1)  + (int)$col[$ki];
			}
		}

		$end = $this->current_tab;
		$this->current_tab = array();

		//Clean Table
		foreach($end as $k=>$val){

			if( (int)$ligne[$k] > 1)
				foreach($val as $ki=>$ri) {
					if( ( $ki == 0  ) || ($col[$ki] > 1 && !empty($ri->title))  ){
						$this->current_tab[$k][$ki]= $ri;
					}
					elseif(isset($this->current_tab[0][$ki]))
						$this->current_tab[$k][$ki]=false;
				}
		}

		return $this->current_tab;
	}


	/**
		@brief construct list all famille in After Selected lot
	*/
	protected function CallFam($id){

		$sql = " SELECT  id, nom, commentaire_s, id_lot, version, status FROM famille WHERE id_lot='" . (int)$id."' ";

		if( !isset($_SESSION['arbo']) || !isset($_GET['arbo']))
			$sql .= " AND status > 0 ";

    $tab_fams = $this->api->db->query($sql);
    $i = 1;
    while ($l = @$this->api->db->fetch($tab_fams)) {
				$this->list_fam[] = new FamListener(array(
				'num' => $i
				, 'lot_id' => $id
				, 'id' => $l['id']
				, 'nom' => $l['nom']
				, 'commentaire_survol' => $l['commentaire_s']
				));
//         $faam[$i] = array('nume' => $i, 'num' => $l[0], 'nom' => $l[1], 'commentaire_survol' => $l[2]);
        $i++;
    }
	}

	/**
		@brief construct list all famille in After Selected lot
	*/
	protected function CallPresta($id, $version = -1){

		$sql = "SELECT * FROM prestation WHERE id='" . (int)$id."'  ";

		if($version > 0 )
			$sql .= "  AND version = '".$version."' ";

		$sql .= " LIMIT 1 ";

// 		if( !isset($_SESSION['arbo']) || !isset($_GET['arbo']))
// 			$sql .= " AND status > 0 ";

    $tab_fams = $this->api->db->query($sql);

//     if( !mysql_num_rows($tab_fams)) {
// 			$sql = "SELECT * FROM prestation WHERE id='" . (int)$id."'  AND ";
//
// 			if( !isset($_SESSION['arbo']) || !isset($_GET['arbo']))
// 				$sql .= " AND status > 0  ";
// 				$tab_fams = mysql_query($sql);
// 		}
// 		echo $sql;

    $i = 1;
    while ($l = $this->api->db->fetch($tab_fams)) {
				$this->list_presta[ $l['id'] ] = new PrestaListener(array(
				'num' => $i
				, 'id' => $l['id']
				, 'nom' => $l['nom']
				, 'commentaire_survol' => $l['commentaire_p']
				, 'prix' => $l['prix']
				, 'unite' => $l['unite']
				, 'id_lot' => $l['id_lot']
				, 'id_famille' => $l['id_famille']
				, 'id_gdt' => $l['id_gdt']
				, 'ok' => $l['ok']
				, 'version' => $l['version']
				, 'status' => $l['status']
				, 'link_id' => $l['link_id']
				, 'link_title' => $l['link_title']
				));
        $i++;
    }
	}

	/**
		@brief construct list all famille in After Selected lot
	*/
	protected function CallPrefiltre($id, $version = 0 ){
    $tab_fams = $this->api->db->query($sql = "SELECT * FROM prefiltre WHERE id='" . (int)$id."' GROUP BY id");//  AND version = '".$version."' ");
//     echo $sql;
    $i = 1;
    while ($l = $this->api->db->fetch($tab_fams)) {
				$this->list_prefiltre[ $l['id'] ] = new PrestaListener(array(
				'num' => $i
				, 'id' => $l['id']
				, 'nom' => $l['nom']
				, 'commentaire_survol' => $l['commentaire_p']
				, 'id_lot' => $l['id_lot']
				, 'id_gdt' => $l['id_gdt']
				, 'version' => $l['version']
				, 'link_id' => $l['link_id']
				, 'link_title' => $l['link_title']
				));
        $i++;
    }
	}

	/**
		@brief construct list all famille in After Selected lot
	*/
	protected function CallPostfiltre($id, $version = 0){
    $tab_fams = $this->api->db->query($sql = "SELECT * FROM postfiltre WHERE id='" . (int)$id."' AND version = '".$version."' ");
//     echo $sql;
    $i = 1;
    while ($l = $this->api->db->fetch($tab_fams)) {
				$this->list_postfiltre[ $l['id'] ] = new PrestaListener(array(
				'num' => $i
				, 'id' => $l['id']
				, 'nom' => $l['nom']
				, 'commentaire_survol' => $l['commentaire_p']
				, 'id_lot' => $l['id_lot']
				, 'id_gdt' => $l['id_gdt']
				, 'version' => $l['version']
				, 'link_id' => $l['link_id']
				, 'link_title' => $l['link_title']
				));
        $i++;
    }
	}

	/**
		@brief Call Array of All presta in Current Fam After Selected Lot + Fam
	*/
	protected function CallTableau($id = 0){

		if($id <= 0 )
			$id = $this->current_fam->id;

    $cols = $this->api->db->num_rows($this->api->db->query($sql ="SELECT id FROM colonne WHERE id_famille='" . $id."' "));
    $ligs = $this->api->db->num_rows($this->api->db->query($sql ="SELECT id FROM ligne WHERE id_famille='" . $id."' "));


		$res_lignes = array();
		$res_cols = array();

		$res_cols[] = false;
    for ($c = 1; $c <= $cols; $c++) {
        $datc = $this->api->db->fetch($this->api->db->query("SELECT * FROM colonne WHERE ordre=" . $c . " AND id_famille=" . $id));

				$res_cols[] = new CellListener(array(
					 'nom'=> $datc['libelle']
					,'commentaire_s'=> $datc['information']
					));
    }
    $res_lignes[] = $res_cols;

    for ($l = 1; $l <= $ligs; $l++) {

				$res_cols = array();

				$li = $this->api->db->fetch($this->api->db->query("SELECT * FROM ligne WHERE ordre=" . $l . " AND id_famille=" . $id));
				$res_cols[] = new CellListener(array(
					 'nom'=> $li['libelle']
					,'commentaire_s'=> $li['information']
					));


        for ($c = 1; $c <= $cols; $c++) {

            $co = $this->api->db->fetch($this->api->db->query("SELECT id FROM colonne WHERE ordre=" . $c . " AND id_famille=" . $id));
            $tab = $this->api->db->query("SELECT * FROM tableau WHERE id_ligne=" . $li['id'] . " AND id_colonne=" . $co[0]);

            $Cell = new CellListener(false);

            if ($this->api->db->num_rows($tab) > 0) {
                $ff = $this->api->db->fetch($tab);
								$sql3 = "SELECT id, nom FROM prestation WHERE id='" . $ff[2]."' ";
								if( !isset($_SESSION['arbo']) || !isset($_GET['arbo']))
										$sql3 .= " AND status > 0 ";

                $datc = $this->api->db->fetch(mysql_query($sql3));
                if($datc !=false)
									$Cell->Fix('<img src="/img/outils_devis/suivantV2.png" style="border:none;"/>', '', "lot=" . $_GET['lot'] . "&fam=" . $_GET['fam'] . "&n_fam=" . $_GET['n_fam'] . "&presta=" . $datc[0] );
// 								else
// 									$Cell = false;
            } else {
							$Cell = false;
            }
            $res_cols[] = $Cell;
        }

        $res_lignes[] = $res_cols;

    }
		$this->current_tab = $res_lignes;
	}
}

/**
*/
Class CellListener{
	/**
		@var
	*/
	public $title ='';
	/**
		@var
	*/
	public $title_hover ='';
	/**
		@var
	*/
	public $href ='';


	/**
	*/
	public function __construct($array){
		if($array === false)
			return ;
// 		print_r($array);
		$this->title=  CheckEncoding( stripcslashes( $array['nom']) ) ;
		$this->title_hover= base64_encode( CheckEncoding( stripcslashes($array['commentaire_s']) ) );

		$this->href = rebuldUrls("/[*alias*]/?page=accueil&ran=1&lot=" . $this->id . "&fam=1&n_fam=" .$this->premiere_famille);
	}

	public function Fix($title='', $hover='', $href='' ){
		$this->title= $title;
		if(!empty($hover))
			$this->title_hover= (( CheckEncoding($hover)));
		if(!empty($href))
			$this->href = $href ;
// 			$this->href = rebuldUrls("/[*alias*]/?page=accueil&ran=1&lot=" . $this->id . "&fam=1&n_fam=" .$this->premiere_famille);
	}
}


/**
*/
Class LotListener{
	/**
		@var
	*/
	public $id;
	/**
		@var
	*/
	public $nom;
	/**
		@var
	*/
	public $commentaire_permanent;
	/**
		@var
	*/
	public $commentaire_survol;
	/**
		@var
	*/
	public $coefficient_marge;
	/**
		@var
	*/
	public $cout_minimum;
	/**
		@var
	*/
	public $coefficient_main_oeuvre;
	/**
		@var
	*/
	public $image;
	/**
		@var
	*/
	public $premiere_famille;
	/**
		@var
	*/
	public $title;
	/**
		@var
	*/
	public $title_hover;
	/**
		@var
	*/
	public $href;


	function __construct($array){
		$this->id= $array['id'];
		$this->nom= str_replace('#'," ", $array['nom'] ) ;
		$this->commentaire_permanent= $array['commentaire_permanent'];
		$this->commentaire_survol= $array['commentaire_survol'];
		$this->coefficient_marge= $array['coefficient_marge'];
		$this->cout_minimum= $array['cout_minimum'];
		$this->coefficient_main_oeuvre= $array['coefficient_main_oeuvre'];
		$this->image= $array['image'];


		$this->premiere_famille= ($array['premiere_famille']);


		$this->title= str_replace('#',"<br>", ucwords( ($this->nom) ) );
		$this->title_hover= (( ($this->commentaire_survol)));
		$this->href = array($this->id , $this->premiere_famille);
	}
}

/**
*/
Class FamListener{

	/**
		@var
	*/
	public $lot_id = 0;
	/**
		@var
	*/
	public $index = 0 ;
	/**
		@var
	*/
	public $id = 0 ;
	/**
		@var
	*/
	public $num = 0 ;
		/**
		@var
	*/
	public $nom = '';
	/**
		@var
	*/
	public $commentaire_survol = '';
	/**
		@var
	*/
	public $title= '';
	/**
		@var
	*/
	public $title_hover= '';
	/**
		@var
	*/
	public $href= '';



	function __construct($array){
		$this->index= @$array['index'];
		$this->lot_id= $array['lot_id'];
		$this->id= $array['id'];
		$this->num= $array['num'];
		$this->nom= $array['nom'];
		$this->commentaire_survol= $array['commentaire_survol'];

		$this->title= ( ( ( ($this->nom) ) ) ) ;
		$this->title_hover= (((  ( $this->commentaire_survol) )) );
		$this->href = array( $this->lot_id , $this->num , $this->id );
	}
}


/**
*/
Class PrestaListener{

	/**
		@var
	*/
	public $id = 0 ;
	/**
		@var
	*/
	public $num = 0 ;
		/**
		@var
	*/
	public $nom = '';
	/**
		@var
	*/
	public $commentaire_survol = '';
	/**
		@var
	*/
	public $title= '';
	/**
		@var
	*/
	public $title_hover= '';
	/**
		@var
	*/
	public $href= '';
	/**
		@var
	*/
	public $link_id= '';
	/**
		@var
	*/
	public $link_title= '';



	function __construct($array){
		$this->index= @$array['index'];
// 		$this->lot_id= $array['lot_id'];
		$this->id= $array['id'];
		$this->num= $array['num'];
		$this->nom= $array['nom'];
		$this->commentaire_survol= $array['commentaire_survol'];
		$this->link_id = $array['link_id'];
		$this->link_title =  $array['link_title'];


		$this->title= $this->nom ;
		$this->title_hover=  $this->commentaire_survol;
		$this->href = array( @$this->lot_id , $this->num , $this->id );


	}
}



class QR {

	public $type ='';

	/**
		@var suffixe in table [ '' | '_pf' | '_pf_a' ]
	*/
	public $_next = '';
	/**
		@var id int $arbo
	*/
	public $arbo = 0;

	/**
		@var id int ressource id
	*/
	public $quest_id = 0;

	public $endi = 0;


	function __construct(&$api,$type = 'prestation', $arbo=0){
        $this->api = &$api;
		$this->type = $type;
		$this->arbo = $arbo;

		switch($this->type){
			case 'prefiltre';
				$this->_next = '_pf';
			break;
			case 'postfiltre':
				$this->_next = '_ptf';
			break;
		}
	}

 /**
	@brief return current Question / reponse
	@param $id int > 0
	@param $rang 0
	@param $sptrintf "%s</////>%s"
 */
 function LoadRootQuestion($id, $rang = 0, $sptrintf = "%s</////>%s"){
				if($id<=0)
					return false;

				if($rang > 0 )
					$q=$this->api->db->fetch($this->api->db->query($sql="SELECT * FROM question".$this->_next."_a WHERE id=".$id/*. " AND ok=" . $this->arbo*/));
				else
					$q = $this->api->db->fetch($this->api->db->query($sql="SELECT * FROM question".$this->_next."_a WHERE id_prestation=" . $id . " AND ok=" . $this->arbo . " AND id NOT IN (SELECT id_question_suivante FROM reponse".$this->_next."_a) ORDER BY id LIMIT 1"));

 				//echo $sql ;
				$reponse = new Reponse($this->api, $this->type );
// 				echo $q[2];
				switch($q['type_r'])
				{
					case 'na':
						$rep = $reponse->GetDisplayNa($q['id'],$rang);
					break;
					case 'cac':
						$rep = $reponse->GetDisplayCac($q['id'],$rang);
					break;
					case 'tb':
						$rep = $reponse->GetDisplayTbl($q['id'],$rang);
					break;
					case 'zdtn':
							$rep = $reponse->GetDisplayZdtn($q['id'],$rang);
					break;
					case 'ld':
							$rep = $reponse->GetDisplaySelect($q['id'],$rang);
					break;
				}


				$question = new Question($this->api, $this->type );
				$question->GetDisplayInfo($q['libelle'], $q['id_ancienne_question'], $rang);
				if( $q['type_r'] !='na')
				$string = sprintf($sptrintf, $question->GetDisplayInfo($q['libelle'], $q['id_ancienne_question'], $rang) ,$rep ) ;

				// fix endi
				$this->endi = $reponse->endi;



				return $string;
 }

}

class Question
	Extends QR {

	public $type ='';

	protected $tables = array();

    public function __construct(&$api,$type = 'prestation', $arbo = 0)
    {
        $this->api = &$api;
        parent::__construct($api, $type, $arbo);
    }

	/**
		@brief This information is extract in Racine Table, not stocked in arbo tables
	*/
	function GetDisplayInfo( $title, $idstring , $indice = 0){

		$id = preg_replace('#-.*#', '',$idstring);
		if( $idstring === $id)
			$ver = 0;
		else
			$ver = substr($idstring, (strlen($id)+1) );

		$rep='';

		$sql = $this->api->db->query($sql="SELECT information FROM question".$this->_next." WHERE id=" . $id ." AND version = '".$ver."' " );
		if(!$sql)
			return $rep;
		$descdp = $this->api->db->fetch($sql);



		if (addslashes(stripslashes($descdp['information'])) != "") {
				$rep .= "<table ><tr><td style='font-size:11px'>&nbsp;".($indice+1).")  " . html_entity_decode(stripslashes($title)) . "</td><td><img src='/img/outils_devis/info1.png'  onClick=\"javascript:ChangeImg(this, '" . addslashes(stripslashes($descdp['information'])) . "');\" style='border:none;vertical-align:middle;' /></td></tr></table>";
		} else {
				$rep .= "<span style='font-size:11px; display:inline'>&nbsp;".($indice+1).") "./* ?????? html_entity_decode(stripslashes($q[1])).*/"</span>";
				$rep .= "<span style='font-size:11px'>" . html_entity_decode(stripslashes($title))."</span>";
		}

		return $rep;
	}


}


class Reponse
	Extends QR {



	protected $tables = array();
    
    public function __construct(&$api,$type = 'prestation', $arbo = 0)
    {
        $this->api = &$api;
        parent::__construct($api, $type, $arbo);
    }

	/**
		@brief construct Display Select  Response
	*/
	function GetDisplaySelect( $question_id , $indice = 0){

		$list1 = $list = $this->api->db->query("SELECT * FROM reponse".$this->_next."_a WHERE id_question=" . $question_id . ' ORDER BY id');
// der='" . $der . "'
		$rep = "<select  style='font-size:10px' id='r".($indice+1)."' ";
// 		var_dump($der);
// 		$l=mysql_fetch_object($list1);
// 			if($der !=null)
// 				$rep.=" onchange=\" ProcessPrice('".((isset($_SESSION['prr'][$_SESSION['monnum'] + 1])) ? 'notend' : 'end' )."');\" ";
// 			else
				$rep.=" onchange=\"gor(this[this.selectedIndex].id,".($indice+1).",this.value, '".$this->type."')\" ";

// 				onchange=\"gor(this[this.selectedIndex].id,".($indice+1).",this.value, '".$this->type."');\"
		$rep.= "><option value=''></option>";
		while ($l = $this->api->db->fetch($list)) {
// 				$der = 0;
// 				$mader = mysql_query($sql="SELECT * FROM prixnul WHERE type='".$this->type."' AND id_reponse=" . $l->id_reponse_origine);
// 				if (@ mysql_num_rows($mader) != 0) {
// 						$der = 1;
// 				}
// 				echo $sql ;  der='" . $der . "'
				$rep.="<option value='" . $l['id'] . "' id='" . $l['id_question_suivante'] . "' ";
				$ccoul = $this->api->db->query("SELECT * FROM couleur WHERE id_reponse='" . $l['id_reponse_origine'] . "' AND type='".$this->type."'");
				if ($ccoul!== false && $ccoul->num_rows != 0) {
						$rep.= "style='background-color:#d6dfff'";
				}
				$rep.= ">" . html_entity_decode(stripslashes($l['libelle'])) . "</option>";
		}
		$rep.='</select>';

		return $rep;
	}

	/**
		@brief construct Display Select  Response
	*/
	function GetDisplayCac( $question_id , $indice = 0){
		$der=0;
		$l=mysql_fetch_object(mysql_query("SELECT * FROM reponse".$this->_next."_a WHERE id_question=" . $question_id . ' ORDER BY id'));
		$mader=mysql_query("SELECT * FROM prixnul WHERE type='".$this->type."' AND id_reponse=".$l->id_reponse_origine);
		if(@ mysql_num_rows($mader)!=0)
		{
			$der=1;
		}
		$rep="<input der='".$der."' type='hidden' name='".$l->id."' id='r".($indice+1)."' value='0' />";
		if($l->id_question_suivante!=0){
			$rep.="<a id='ucac".($indice+1)."' href=javascript:WindowChangeRoomm('/module_devis/casesacocher.php?tp=".$this->type."&id=r".($indice+1)."&rep=".$l->id."&pr=".$l->id_question_suivante."','_blank')>Choisir</a>";
		}
		else{
			$rep.="<a id='ucac".($indice+1)."' href=javascript:WindowChangeRoomm('/module_devis/casesacocher.php?tp=".$this->type."&id=r".($indice+1)."&rep=".$l->id."','_blank')>Choisir</a>";
			if(isset($_SESSION['prr'][$_SESSION['monnum']+1]))
			{
				$this->endi = 1;
				if ($der != 0) {
						$this->endi = 3;
				}

				$rep.="</!!!/>";
			}else
			{
				$this->endi = 2;
				if ($der != 0) {
						$this->endi = 4;
				}

				$rep.="</!end!/>";
			}
		}

		return $rep;
	}


	/**
		@brief construct Display Select  Response
	*/
	function GetDisplayTbl( $question_id , $indice = 0){
		$der=0;
		$l=mysql_fetch_object(mysql_query("SELECT * FROM reponse".$this->_next."_a WHERE id_question=" . $question_id . ' ORDER BY id'));
		$mader=mysql_query("SELECT * FROM prixnul WHERE type='".$this->type."' AND id_reponse=".$l->id_reponse_origine);
		if(@ mysql_num_rows($mader)!=0)
		{
			$der=1;
		}
		$rep="<input der='".$der."' type='hidden' name='".$l->id."' id='r".($indice+1)."' value='0' />
		";
		if($l->id_question_suivante!=0){
			$rep.="<a id='utab".($indice+1)."' href=javascript:WindowChangeRoomm('/module_devis/tableau.php?tp=prestation&id=r".($indice+1)."&rep=".$l->id."&pr=".$l->id_question_suivante."','_blank')>Choisir</a>";
		}else{
		$rep.="<a  id='utab".($indice+1)."' href=javascript:WindowChangeRoomm('/module_devis/tableau.php?tp=prestation&id=r".($indice+1)."&rep=".$l->id."','_blank')>Choisir</a>";
			if(isset($_SESSION['prr'][$_SESSION['monnum']+1]))
			{
				$this->endi = 1;
				if ($der != 0) {
						$this->endi = 3;
				}
				$rep.="</!!!/>";
			}else
			{
				$this->endi = 2;
				if ($der != 0) {
						$this->endi = 4;
				}

				$rep.="</!end!/>";
			}
		}

		return $rep;
	}


	/**
		@brief construct Display Select  Response
	*/
	function GetDisplayZdtn( $question_id , $indice = 0){
		$der=0;
		$l=mysql_fetch_object(mysql_query("SELECT * FROM reponse".$this->_next."_a WHERE id_question=" . $question_id . ' ORDER BY id'));
		$mader=mysql_query("SELECT * FROM prixnul WHERE type='".$this->type."' AND id_reponse=".$l->id_reponse_origine);
		if(@ mysql_num_rows($mader)!=0)
		{
			$der=1;
		}
		$rep="";

		$minii = 0;
		$maxii = 0;
		$limi = mysql_query("SELECT * FROM limite WHERE id_reponse='" .  $l->id_reponse_origine . "' AND type='".$this->type."'");
		if (@ mysql_num_rows($limi) != 0) {
				$gh = mysql_fetch_row($limi);
				(empty($gh[2])) ? $minii = 0 : $minii = $gh[2];
				(empty($gh[3])) ? $maxii = 0 : $maxii = $gh[3];
		}


			$rep = "<input class='inputcheck' der='" . $der . "' style='font-size:10px' mini='" . $minii . "' maxi='" . $maxii . "' maxlength='3' size='10' type='text' name='" . $l->id . "' id='r".($indice+1)."' ";
			//onkeypress=\"if((event.keyCode < 48 || event.keyCode > 57) && event.keyCode > 31 && event.keyCode != 43){ if((event.keyCode==44) || (event.keyCode==46)) { alert('Les decimales ne sont pas autorisees.'); }; event.returnValue = false; }; if((event.which < 48 || event.which > 57) && event.which > 31){ if((event.which==44) || (event.which==46)) { alert('Les decimales ne sont pas autorisees.'); }; return false; }; \" ";
			$rep .=" onkeypress=\"CheckPress(event, '".((isset($_SESSION['prr'][$_SESSION['monnum'] + 1])) ? 'notend' : 'end' )."') \" ";

			if($l->id_question_suivante == 0)
				$rep.=" onchange=\" ProcessPrice('".((isset($_SESSION['prr'][$_SESSION['monnum'] + 1])) ? 'notend' : 'end' )."');\" ";
			else
				$rep.=" onchange=\"goz(" . $l->id_question_suivante . ", ".($indice+1).", " . $l->id . ", '".$this->type."')\" ";

			$rep.=" /> ";
			if ($l->unite != 'u') {
					$rep.=$l->unite;
			}
// 							echo $l->id_question_suivante;
			if ($l->id_question_suivante != 0) {
					$rep.=" <a href=\"javascript:goz(" . $l->id_question_suivante . ", ".($indice+1).", " . $l->id . ", '".$this->type."')\" style='font-size:9px; color:#925e02; font-weight:bold; text-decoration:none'>Suite</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			} else {
					if (isset($_SESSION['prr'][$_SESSION['monnum'] + 1])) {
							$this->endi = 1;
							if ($der != 0) {
									$this->endi = 3;
							}
							$rep.="</!!!/>";
					} else {
							$this->endi = 2;
							if ($der != 0) {
									$this->endi = 4;
							}
							$rep.="</!end!/>";
					}
			}


		return $rep;
	}

		/**
		@brief construct Display Select  Response
	*/
	function GetDisplayNa( $question_id , $indice = 0){
			$rrr = mysql_fetch_row(mysql_query("SELECT * FROM reponse".$this->_next."_a WHERE id_question=" . $question_id ));
			$mp = $rrr[6];
			$c = mysql_query("SELECT code, prix FROM prix_impact");
			while ($l = mysql_fetch_row($c)) {
			$mp = html_entity_decode(str_replace($l[0], $l[1], $mp));
			}
			eval('$prix=' . $mp . ';');
			$prix = round(($prix / 0.9), 2);
			echo "<tr id='come' height='14px'>
			<td id='op' align='right' style='font-size:11px'>
			</td>
			<td id='opp' style='font-size:11px' align='right'>
			<input style='font-size:12px; color:#000000' size='12' type='text' id='prix' disabled='disabled' value='" . $prix . "' />&nbsp;&euro;&nbsp;HT&nbsp;
			</td>
			</tr>
			<tr>
			<td colspan='2' id='commme' valign='top' style='background:url(img/outils_devis/cadrepresta.png); background-repeat:no-repeat; height:73px;text-align:justify;padding-left:13px;padding-right:17px;padding-top:13px;font-size:11px'>" . html_entity_decode(stripslashes($rrr[7])) . "</td>
			</tr>";

			$pr = mysql_fetch_row(mysql_query("SELECT nom,id FROM ".$this->type." WHERE id=" . $_SESSION['prr'][$_SESSION['monnum']]['id']));

			$common = " SELECT famille.id_lot FROM famille, prestation " ;
			switch($this->type){
				case 'postfiltre':
					$lo = mysql_fetch_row(mysql_query($common . ", pre_pre WHERE famille.id=prestation.id_famille AND prestation.id=pre_pre.id_prestation AND pre_pre.id_prefiltre=" . $_SESSION['prr'][$_SESSION['monnum']]['id']));
				break ;
				case 'prefiltre':
					$lo = mysql_fetch_row(mysql_query($common . ", pre_post WHERE famille.id=prestation.id_famille AND prestation.id=pre_post.id_prestation AND pre_post.id_postfiltre=" . $_SESSION['prr'][$_SESSION['monnum']]['id']));
				break ;
				default:
					$sql = $common . " WHERE famille.id=prestation.id_famille AND prestation.id=" . $_SESSION['prr'][$_SESSION['monnum']]['id'] ;
					$r = mysql_query($sql);
					$lo = mysql_fetch_row($r);
			}

			$_SESSION['pre_panier'] = array(
																	'lot' => $lo[0],
																	'presta' => $pr[0],
																	'pht' => $prix,
																	'pu' => $prix,
																	'com' => stripslashes($rrr[7]),
																	'piece' => 'nommer',
																	'qtte' => '1',
																	'unite' => (empty($rrr[5]) || ($rrr[5] == 'u')) ? '' : $rrr[5],
																	'ok' => '1',
																	'id_prestation' => $pr[1],
																	'type_presation' => $this->type
																);


			if (isset($_SESSION['prr'][$_SESSION['monnum'] + 1])) {
			echo "<script language='javascript' type='text/javascript'>
			var tru=0;
			</script>";
			} else {
			echo "<script language='javascript' type='text/javascript'>
			var tru=1;
			</script>";
			}
	}
}

?>