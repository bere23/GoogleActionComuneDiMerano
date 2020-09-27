<?php
include('Webhook.php');
include('dbconfig.php');
$args = ['projectId' => 'test-project-id'];

/*
	spezza una stringa in singole parole e crea le parti di query necessarie

*/
function multiWords($fieldname, $words) {
	$words_array = explode(" ", $words);
	$words_search_where = "";
	$words_search_count = "( 0 ";
	for ($i = 0; $i < count($words_array); $i++) {
		$word_tmp = substr($words_array[$i],0, strlen($$words_array[$i]) -2);
		if (strlen($word_tmp) > 0) {
			$words_search_where = $words_search_where . " or $fieldname like '%".$word_tmp."%'";
			$words_search_count = $words_search_count . " + (position('$word_tmp' in $fieldname) > 0)";
		}
	}
	$words_search_count = $words_search_count . ") as tot";
	$qryelem["where"] = $words_search_where;
	$qryelem["count"] = $words_search_count;
	return $qryelem;

}

/*
trova tutte le combinazioni degli elementi di un array

*/

function array_comb($arr, $temp_string, &$collect) {
    if ($temp_string != "")
        $collect []= $temp_string;

    for ($i=0, $iMax = sizeof($arr); $i < $iMax; $i++) {
        $arrcopy = $arr;
        $elem = array_splice($arrcopy, $i, 1); // removes and returns the i'th element
        if (sizeof($arrcopy) > 0) {
            array_comb($arrcopy, $temp_string ." " . $elem[0], $collect);
        } else {
            $collect []= $temp_string. " " . $elem[0];
        }
    }
}

/*
	nel caso del nome di un ufficio in tedesco, toglie la parte del nome non necessario per la ricerca
*/
function cleanOfficeNameDe($str) {
	$off = str_ireplace("amt", "",str_ireplace("samt", "",str_ireplace("büro", "",str_ireplace("sbüro", "", str_ireplace("ammtes", "", $str)))));
	$off = str_ireplace("wesen", "",str_ireplace("dienste", "", $off));
	return $off;
}

function getLocalizedString($lang, $textIT, $textDE) {
	if ($lang=="it") return $textIT;
	return $textDE;

}

function OfficesIntent($w) {
	global $answer, $con;

	$lang = $w->get_language();

	$office_tag = '***X**';
	$list_general = '***X**';
	$office_room = '***X**';
	$office_phone = '***X**';
	$person_name = '***X**';
	$person_lastname = '***X**';
	$person = '***X**';
	$office_time = '***X**';
	$event_type = '***X**';
	$event_date = '***X**';
	$event_singledate = '***X**';

	// ricerca in base alle competenze di un ufficio
	if ( ! ($w->get_parameter('office_tag') == '')) {
		$office_tag = $w->get_parameter('office_tag');
		$qry = "select uf_title_$lang as title, uf_stanza_$lang as stanza, uf_tel as tel from uffici where uf_competenze_$lang like '%".$office_tag."%'";
		$result = $con->query($qry);
		if ( $row = $result->fetch_array())  {
			$answer = getLocalizedString($lang, "l'ufficio competente in ".$office_tag." è: ". $row['title'] ." e si trova in ". $row['stanza'] .". Il numero di telefono è ". $row['tel'], "Das Büro der für ".$office_tag." zuständig ist: ". $row['title'] ." und befindet sich in der  ". $row['stanza'] .". Die Telefonnummer ist ". $row['tel']);
			return $answer;
		}
	}

	// orari di apertura di un ufficio
	if ( ! ($w->get_parameter('office_time') == '')) {
		$office_time = trim($w->get_parameter('office_time'));
		if ($lang=="de"){
			$office_time = cleanOfficeNameDe($office_time);
		}
		$qryelem = multiWords("uf_title_$lang", $office_time);
		$qry = "select uf_title_$lang as title, uf_stanza_$lang as stanza, uf_tel as tel, uf_apertura_$lang as apertura, ".$qryelem["count"]." from uffici where (".substr($qryelem["where"], 4).") order by tot desc";
		$result = $con->query($qry);
		if ( $row = $result->fetch_array())  {
			$answer = $row['title'];
			/*
			if (strlen($row['stanza'])>0) {
				$answer =  $answer . getLocalizedString($lang, " si trova in " . $row['stanza']. ".", " befindet sich im " . $row['stanza']. ".");
			}
			if (strlen($row['tel'])>0) {
					$answer =  $answer . getLocalizedString($lang, " Il numero di telefono è " . $row['tel']. ".", " Die Telefonnummer ist " . $row['tel']. ".");
			};
			*/
			$answer =  $answer . getLocalizedString($lang, " Gli orari di apertura sono: " . $row['apertura'], " Die Öffnungszeiten sind: " . $row['apertura']);
			return $answer;
		}
	}

	// sindaco, vicesindaco, assessori
	if ( ! ($w->get_parameter('person') == '')) {
		$person = $w->get_parameter('person');
		$person = substr($person,0, strlen($person)-3);

		$qry = "select pe_nome as pname, pe_stanza_$lang as stanza, pe_tel as tel, pe_email as email, in_title_$lang as incarico, uf_title_$lang as ufficio, pe_competenze_$lang as competenze  from  incarichi, personale left join uffici on uf_id=pe_uf_id where  in_id=pe_in_id and (in_title_$lang like '".$person."%' or pe_nome like  '".$person."%')";
		$result = $con->query($qry);
		if ($result->num_rows > 1) {
			$answer = getLocalizedString($lang, 'Gli assessori sono: ', 'Die Assessoren sind: ');
			while ($row = $result->fetch_array()) {
				$answer = $answer . ', '. $row['pname'];
			}
			return $answer;
		} else {
			if ( $row = $result->fetch_array())  {
				$answer = getLocalizedString($lang, $row['pname']." ha un incarico come ".$row['incarico'].". ".$row['stanza'].". Il suo numero di telefono è ".$row['tel']." e l'email è ".$row['email'].". ".$row['competenze'], $row['pname']." hat ein Auftrag als ".$row['incarico'].". ".$row['stanza'].". Seine Telefonnummer ist ".$row['tel']." und seine Email ist ".$row['email'].". ".$row['competenze']);
				return $answer;
			}
		}

	}

	// elenco ripartizioni o uffici
	if ( ! ($w->get_parameter('list_general') == '')) {
		$list_general = $w->get_parameter('list_general');
		$qry = "select ri_name_$lang as title from ripartizioni";
		$answer = getLocalizedString($lang, 'Le ripartizioni presenti sono: ', 'Die Abteilungen sind: ');

		if (($list_general == 'uffici')||($list_general == "Büros")||($list_general=="Büro")) {
			$qry = "select uf_title_$lang as title from uffici  ";
			$answer = getLocalizedString($lang, 'Gli uffici presenti sono: ', 'Die Büros sind: ');
		}
		$result = $con->query($qry);
		while ($row = $result->fetch_array()) {
			$answer = $answer . ', '. $row['title'];
		}
		return $answer;
	};

	// dove si trova un ufficio
	if ( ! ($w->get_parameter('office_room') == '')) {
		$office_room = $w->get_parameter('office_room');
		if ($lang=="de") {
			$office_room = cleanOfficeNameDe($office_room);
		}
		$office_room = substr($office_room,0, -2);
		$qry = "select uf_title_$lang as title,uf_stanza_$lang as stanza, uf_tel as tel from uffici where uf_title_$lang  like '%".$office_room."%' ";

		$result = $con->query($qry);
		$answer_tmp = "";
		while ($row = $result->fetch_array()) {
			$answer_tmp = $answer_tmp . getLocalizedString($lang, '. '."L'ufficio ".$row['title']." si trova in ". $row['stanza']." e il numero di telefono è ".$row['tel'], '. '. "Das Büro ".$row['title']." befindet sich im ".$row['stanza']." und seine Telefonnummer ist ".$row['tel']);
		}
		if (strlen($answer_tmp)>0) $answer = substr($answer_tmp, 1);
		return $answer;
	};



	if (( ! ($w->get_parameter('person_name') == '')) || ( ! ($w->get_parameter('person_lastname') == ''))) {
		$person_name = $w->get_parameter('person_name');
		$person_lastname = $w->get_parameter('person_lastname');
		$person = explode(" ",trim($person_name." ".$person_lastname));

		$combinations = array();
		array_comb($person, "", $combinations);
		error_log(print_r($person, true));
		error_log(print_r($combinations, true));

		$where = "";
		$where1 = "";
		for ($i=0; $i<count($combinations); $i++) {
			if ($i>0) {
			  $where = $where . " OR ";
				$where1 = $where1 . " OR ";
			}
			$where = $where . " (SUBSTR(SOUNDEX(pe_nome),2) = SUBSTR(SOUNDEX('" . trim($combinations[$i])."'),2))";
			$where1 = $where1 . " (SOUNDEX(pe_nome) = SOUNDEX('" . trim($combinations[$i])."'))";
		}

		// TO USE: where  in_id=pe_in_id and (SUBSTR(SOUNDEX(pe_nome),2) = SUBSTR(SOUNDEX("Unterturner Aidelinde"),2))
	//	$qry = "select pe_nome as pname, pe_stanza_$lang as stanza, pe_tel as tel, pe_email as email, in_title_$lang as incarico, IFNULL(uf_title_$lang,'') as ufficio, pe_competenze_$lang as competenze
	//	from  incarichi, personale left join uffici on uf_id=pe_uf_id where  in_id=pe_in_id and
	//	(pe_nome like '%".$person_name."%' and pe_nome like '%".$person_lastname."%') ";
	$qry = "select pe_nome as pname, pe_stanza_$lang as stanza, pe_tel as tel, pe_email as email, in_title_$lang as incarico, IFNULL(uf_title_$lang,'') as ufficio, pe_competenze_$lang as competenze
	from  incarichi, personale left join uffici on uf_id=pe_uf_id where  in_id=pe_in_id and
	(".$where1.") ";
	error_log($qry);
		$result = $con->query($qry);
		if ( $row = $result->fetch_array())  {
			if (strlen($row['ufficio']) > 0) {
				$answer = getLocalizedString($lang, $row['pname']." lavora come ".$row['incarico']." dell'ufficio ".$row['ufficio'].", " . $row['stanza'] .". Il suo numero di telefono è ". $row['tel']." e l'email è ". $row['email'], $row['pname']." arbeitet als ".$row['incarico']." des ".$row['ufficio'].", " . $row['stanza'] .". Seine Telefonnummer ist ". $row['tel']." und seine E-Mail ist  ". $row['email']);

			} else {
				$answer = getLocalizedString($lang, $row['pname']." ha un incarico come ".$row['incarico'].". " . $row['stanza'] .". Il suo numero di telefono è ". $row['tel']." e l'email è ". $row['email'],  $row['pname']." arbeitet als ".$row['incarico']." des ".$row['ufficio'].", " . $row['stanza'] .". Seine Telefonnummer ist ". $row['tel']." und seine E-Mail ist  ". $row['email']);
			}
			return $answer;
		}

	$qry = "select pe_nome as pname, pe_stanza_$lang as stanza, pe_tel as tel, pe_email as email, in_title_$lang as incarico, IFNULL(uf_title_$lang,'') as ufficio, pe_competenze_$lang as competenze
	from  incarichi, personale left join uffici on uf_id=pe_uf_id where  in_id=pe_in_id and
	(".$where.") ";
error_log($qry);
		$result = $con->query($qry);
		if ( $row = $result->fetch_array())  {
			if (strlen($row['ufficio']) > 0) {
				$answer = getLocalizedString($lang, $row['pname']." lavora come ".$row['incarico']." dell'ufficio ".$row['ufficio'].", " . $row['stanza'] .". Il suo numero di telefono è ". $row['tel']." e l'email è ". $row['email'], $row['pname']." arbeitet als ".$row['incarico']." des ".$row['ufficio'].", " . $row['stanza'] .". Seine Telefonnummer ist ". $row['tel']." und seine E-Mail ist  ". $row['email']);

			} else {
				$answer = getLocalizedString($lang, $row['pname']." ha un incarico come ".$row['incarico'].". ".$row['stanza'].". Il suo numero di telefono è ". $row['tel']." e l'email è ". $row['email'],  $row['pname']." arbeitet als ".$row['incarico']." des ".$row['ufficio'].", " . $row['stanza'] .". Seine Telefonnummer ist ". $row['tel']." und seine E-Mail ist  ". $row['email']);
			}
			return $answer;
		} else {
			$answer = getLocalizedString($lang, "mi dispiace, non conosco ".$person_name." ".$person_lastname, "es tut mir leid, ich weiss es nich wer ".$person_name." ".$person_lastname." ist");
			return $answer;
		}

	};

// servizi

if ( ! ($w->get_parameter('services') == '')) {
	$service = addslashes($w->get_parameter('services'));
	$qry = "select se_titolo, se_text  from servizi$lang where (se_titolo like '%".$service."%' OR (SOUNDEX(se_titolo) = SOUNDEX('".$service."')))";
	error_log($qry);
//	$answer = getLocalizedString($lang, 'Le ripartizioni presenti sono: ', 'Die Abteilungen sind: ');
	$answer = getLocalizedString($lang, 'Spiacente, non conosco la risposta', 'Es tut mir leid, ich weiss es nicht');
	$result = $con->query($qry);
	if ($row = $result->fetch_array()) {
		$answer =  $row['se_titolo']." ".$row['se_text'];
	} else {
		$qry = "select se_titolo, se_text, MATCH(se_text) AGAINST ('".$service."') as score  from servizi$lang ORDER BY score desc";
			error_log($qry);
		$result = $con->query($qry);
		if ($row = $result->fetch_array()) {
			$answer =  $row['se_titolo']." ".$row['se_text'];
		}

	}
	return $answer;
};


	if (( ! ($w->get_parameter('event_type') == '')) && ( ! ($w->get_parameter('event_date') == ''))) {
		$event_type = substr($w->get_parameter('event_type'),0, -1);
		if ($lang=="de") {
			$event_type = str_ireplace("ereigniss", "",str_ireplace("ereignisse", "", $event_type));
			$event_type = str_ireplace("Kunst", "Ausstellung", $event_type);
		};
		$event_type = substr($event_type,0, -1);

		$event_dates = $w->get_parameter('event_date');
		$event_date1 = substr($event_dates["startDate"],0,10);
		$event_date2 = substr($event_dates["endDate"],0,10);

		$qry = "select distinct ev_title, ev_type, ev_where, ev_start_date, ev_end_date, ev_descr from events$lang where (('".$event_date1."' between ev_start_date and ev_end_date) or  ('".$event_date2."' between ev_start_date and ev_end_date) or ('".$event_date1."' < ev_start_date and '".$event_date2."' > ev_end_date )) and ev_type like '%".$event_type."%' order by ev_end_date limit 5";
		$answer_tmp = '';
		$result = $con->query($qry);
		while ($row = $result->fetch_array()) {
			$answer_tmp = $answer_tmp . ', '. $row['ev_title'].". Presso ".$row['ev_where'].". ". $row['ev_descr'];
		}
		if (strlen($answer_tmp) > 0) {
			$answer = getLocalizedString($lang, 'Si svolgeranno i seguenti eventi: ', 'Die folgenden Ereignisse werden stadt finden: ') . $answer_tmp;
		} else {
			$answer = getLocalizedString($lang, 'Spiacente, non ci sono eventi di questo tipo nel periodo richiesto', 'Leider sind keine Ereignisse dieser Art während diesen Zeitraum geplant ');
		}
	};

	if (( ! ($w->get_parameter('event_type') == '')) && ( ! ($w->get_parameter('event_singledate') == ''))) {
		$event_type = substr($w->get_parameter('event_type'),0, -1);
		if ($lang=="de") {
			$event_type = str_ireplace("ereigniss", "",str_ireplace("ereignisse", "", $event_type));
			$event_type = str_ireplace("Kunst", "Ausstellung", $event_type);
		};
		$event_type = substr($event_type,0, -1);

		$event_date1 = substr($w->get_parameter('event_singledate'),0,10);

		$qry = "select distinct ev_title, ev_type, ev_where, ev_start_date, ev_end_date, ev_descr from events$lang where ('".$event_date1."' between ev_start_date and ev_end_date)  and ev_type like '%".$event_type."%' order by ev_end_date limit 5";
		$answer_tmp = '';
		$result = $con->query($qry);
		while ($row = $result->fetch_array()) {
			$answer_tmp = $answer_tmp . ', '. $row['ev_title'].". Presso ".$row['ev_where'].". ". $row['ev_descr'];
		}
		if (strlen($answer_tmp) > 0) {
			$answer = getLocalizedString($lang, 'Si svolgeranno i seguenti eventi: ' , 'Die folgenden Ereignisse werden stadt finden: ') . $answer_tmp;

		} else {
			$answer = getLocalizedString($lang, 'Spiacente, non ci sono eventi di questo tipo nel periodo richiesto', 'Leider sind keine Ereignisse während diesen Zeitraum geplant ');


		}
	};

	if ((  ($w->get_parameter('event_type') == '')) && ( ! ($w->get_parameter('event_singledate') == ''))) {

		$event_date1 = substr($w->get_parameter('event_singledate'),0,10);;
		$qry = "select distinct ev_title, ev_type, ev_where, ev_start_date, ev_end_date, ev_descr from events$lang where ('".$event_date1."' between ev_start_date and ev_end_date)   order by ev_end_date limit 0,5";
		$answer_tmp = '';
		$result = $con->query($qry);
		while ($row = $result->fetch_array()) {
			$answer_tmp = $answer_tmp . ', '. $row['ev_title'].". ".$row['ev_where'].". ". $row['ev_descr'];
		}
		if (strlen($answer_tmp) > 0) {
			$answer = getLocalizedString($lang, 'Si svolgeranno i seguenti eventi: ', 'Die folgenden Ereignisse werden stadt finden: ') . $answer_tmp;
		} else {
			$answer = getLocalizedString($lang, 'Spiacente, non ci sono eventi  nel periodo richiesto', 'Leider sind keine Ereignisse  während diesen Zeitraum geplant ');
		}

	};

	if (( ! ($w->get_parameter('event_type') == '')) && ( ($w->get_parameter('event_date') == '') && ( ($w->get_parameter('event_singledate') == '')))) {
		$event_type = substr($w->get_parameter('event_type'),0, -1);
		if ($lang=="de") {
			$event_type = str_ireplace("ereigniss", "",str_ireplace("ereignisse", "", $event_type));
			$event_type = str_ireplace("Kunst", "Ausstellung", $event_type);
		};
		$event_type = substr($event_type,0, -1);
		$qry = "select distinct ev_title, ev_type, ev_where, ev_start_date, ev_end_date, ev_descr from events$lang where ev_type like '%".$event_type."%' order by ev_end_date limit 5 ";
		$answer_tmp = '';
		$result = $con->query($qry);
		while ($row = $result->fetch_array()) {
			$answer_tmp = $answer_tmp . ', '. $row['ev_title'].". Presso ".$row['ev_where'].". ". $row['ev_descr'];
		}
		if (strlen($answer_tmp) > 0) {
			$answer = getLocalizedString($lang, 'Si svolgeranno i seguenti eventi: ', 'Die folgenden Ereignisse werden stadt finden: ') . $answer_tmp;

		} else {
			$answer = getLocalizedString($lang, 'Spiacente, non ci sono eventi di questo tipo nel periodo richiesto', 'Leider sind keine Ereignisse dieser Art während diesen Zeitraum geplant ');


		}

	};

	if ((  ($w->get_parameter('event_type') == '')) && ( ! ($w->get_parameter('event_date') == ''))) {
		$event_dates = $w->get_parameter('event_date');
		$event_date1 = substr($event_dates["startDate"],0,10);
		$event_date2 = substr($event_dates["endDate"],0,10);
		$qry = "select distinct ev_title, ev_type, ev_where, ev_start_date, ev_end_date, ev_descr from events$lang where (('".$event_date1."' between ev_start_date and ev_end_date) or  ('".$event_date2."' between ev_start_date and ev_end_date) or ('".$event_date1."' < ev_start_date and '".$event_date2."' > ev_end_date )) order by ev_end_date limit 5";
		$answer_tmp = '';
		$result = $con->query($qry);
		while ($row = $result->fetch_array()) {
			$answer_tmp = $answer_tmp . ', '. $row['ev_title'].". Presso ".$row['ev_where'].". ". $row['ev_descr'];
		}
		if (strlen($answer_tmp) > 0) {
			$answer = getLocalizedString($lang, 'Si svolgeranno i seguenti eventi: ', 'Die folgenden Ereignisse werden stadt finden: ') . $answer_tmp;
		} else {
			$answer = getLocalizedString($lang, 'Spiacente, non ci sono eventi di questo tipo nel periodo richiesto', 'Leider sind keine Ereignisse dieser Art während diesen Zeitraum geplant ');

		}

	};

	return $answer;
}

function PharmacyIntent($w) {
	global $answer, $pharmacyUrl;

	$lang = $w->get_language();

	$dt = $w->get_parameter('date');
	$dt = substr($dt, 0, 8);
	$json = file_get_contents($pharmacyUrl."dt=".$dt);
	$json_decoded = json_decode($json);
	$answer_tmp = "";
	for ($i=0; $i<count($json_decoded); $i++) {
		$f = $json_decoded[$i];
		if (($f->GEME_ZIP == "39012") && ($f->IS_TURN == "1")) {
			$time = $f->TURN_TIMETABLE;
			if ($lang=="it") {
				$time = str_replace("-", " alle ", str_replace(":", " e ", str_replace("00:00", " mezzanotte ", $time)));
				$answer_tmp = $answer_tmp."Farmacia ".$f->PHAR_DESC_I.", ".$f->PHAR_ADRESS_I.", numero di telefono ".$f->PHAR_PHONE.". Di turno dalle ".$time.". ";
			} else {

				$time = str_replace("-", " bis ", str_replace(":", " und ", str_replace("00:00", " Mitternach ", $time)));
				$answer_tmp = $answer_tmp."Die Apotheke ".$f->PHAR_DESC_D.", ".$f->PHAR_ADRESS_D.", Telefonnummer ".$f->PHAR_PHONE.". Der Dienst beginnt um ".$time.". ";
			}
		}
	}
	$answer = getLocalizedString($lang, "Non risultano farmacie di turno", "Es sind keine Apotheken im Einsatz");

	if (strlen($answer_tmp)>0) $answer = $answer_tmp;
	return $answer;
}

function MuseumIntent($w) {
	global $answer, $museumUrl;

	$lang = $w->get_language();

	$dt = $w->get_parameter('date');
	$dt = substr($dt, 0, 8);
	$json = file_get_contents($museumUrl);
	$json_decoded = json_decode($json);
	$musei = $json_decoded->features;
	$answer_tmp = "";
	if ( ! ($w->get_parameter('museum_list') == '')) {
		$answer_tmp = getLocalizedString($lang, "I musei presenti a Merano sono: ", "Die Museen in Meran sind: ");

		for ($i=0; $i<count($musei); $i++) {
			$m = $musei[$i]->properties;

			if ($m->PLZ == "39012" ) {
				$answer_tmp = $answer_tmp . getLocalizedString($lang,   $m->BEZEICHNUNG_I. " si trova in ".$m->ADRESSE_I.". ", $m->BEZEICHNUNG_D. " befindet sich in ".$m->ADRESSE_D.". ");

			}

		}
	}
	if ( ! ($w->get_parameter('museum_name') == '')) {
		$museum_name = trim($w->get_parameter('museum_name'));
		if ($lang=="de") {
			$museum_name = trim(str_ireplace("museum", "", str_ireplace("museums", "",$museum_name)));
		}
		$museum_name = substr(trim($museum_name),0, -2);
		for ($i=0; $i<count($musei); $i++) {
			$m = $musei[$i]->properties;

			if ($m->PLZ == "39012" ) {
				if ($lang=="it") {
					if (stripos("  ".$m->BEZEICHNUNG_I, $museum_name)>0) $answer_tmp = $m->BEZEICHNUNG_I. " si trova in ".$m->ADRESSE_I.", il numero di telefono è ".$m->TELEFON.", Per l'ingresso ".$m->EINTRITT_I.". ";
				} else {
					error_log($m->BEZEICHNUNG_D." - ". $museum_name." = ".stripos($m->BEZEICHNUNG_D, $museum_name));
					if (stripos("  ".$m->BEZEICHNUNG_D, $museum_name)>0) $answer_tmp = $m->BEZEICHNUNG_D. " befindet sich in ".$m->ADRESSE_D.", die telefonnummer ist ".$m->TELEFON.", ".$m->EINTRITT_D.". ";
				}


			}

		}
	}
	if (strlen($answer_tmp)>0) $answer = $answer_tmp;
	return $answer;

}

function NewsIntent($w) {
	global $answer, $newsUrl, $newsUrlDe;

	$lang = $w->get_language();

    if ($lang=="it") {
        $url = $newsUrl;

    } else {
        $url = $newsUrlDe;

    }
	$feed_to_array = (array) simplexml_load_file($url);
	$news = $feed_to_array['channel'];
	$answer_tmp = getLocalizedString($lang, "Queste sono le ultime notizie: ", "Diese sind die letzten Nachrichten: ");


	for ($i=0; $i<5; $i++) {
		$itm = $news->item[$i];
		$description = html_entity_decode(strip_tags($itm->description));
		$x=$i+1;
		$answer_tmp = $answer_tmp . getLocalizedString($lang, "Notizia numero ", "Nachricht Nummer ").$x.": ";


		$answer_tmp = $answer_tmp . $itm->title.". ";
	}
		$answer_tmp = $answer_tmp . getLocalizedString($lang, "... Dimmi il numero della notizia della quale vuoi maggiori dettagli o stop per terminare", "... Geben Sie mir die Nummer der Nachricht dessen Sie weitere Informationen wissen möchten oder sagen Sie stop um zu beenden.");


	return $answer_tmp;
}

function NewsIntentFollowup($w) {
	global $answer, $newsUrl, $newsUrlDe;

	$lang = $w->get_language();
	if ($lang=="it") {
		$url = $newsUrl;
		$numbersal = array("rim", "econd", "erz", "uart", "uint");
		$numbersal2 = array("no", "ue", "re", "uattro", "inque");
	} else {
		$url = $newsUrlDe;
		$numbersal = array("rste", "ekund", "ritt", "ierte", "nft");
		$numbersal2 = array("in", "wei", "rei", "ier", "ünf");
	}

	$nr = $w->get_parameter('nr');
	$nrNews = 0;

	for($n=0; $n<5;$n++) {
		if (strpos($nr, $numbersal[$n])||strpos($nr, $numbersal2[$n])) {
			$nrNews = $n+1;
		}
	}

	if ($nrNews == 0) {
		if (is_numeric($nr)) $nrNews = $nr;
	}

	if ($nrNews > 0) {
		$feed_to_array = (array) simplexml_load_file($url);
		$news = $feed_to_array['channel'];
		$nrNews--;
		$itm = $news->item[$nrNews];
		$answer_tmp = $itm->title.". ".html_entity_decode(strip_tags($itm->description));

	} else {

			$answer_tmp = getLocalizedString($lang, "Spiacente, non ho trovato la notizia. ", "Leider kann diese Nachricht nicht gefunden werden. ");
	}
	return $answer_tmp;
}



$wh = new Webhook($args);

$lang = $wh->get_language();
$answer = getLocalizedString($lang, 'Spiacente, non conosco la risposta', 'Es tut mir leid, ich weiss es nicht');


$intent = $wh->get_intent();
switch ($intent) {
	case "OfficesIntent":
		$answer = OfficesIntent($wh);
		break;
	case "PharmacyIntent":
		$answer = PharmacyIntent($wh);
		break;
	case "MuseumIntent":
		$answer = MuseumIntent($wh);
		break;
	case "NewsIntent":
		$answer = NewsIntent($wh);
		break;
	case "NewsIntentFollowup":
		$answer = NewsIntentFollowup($wh);
		break;
    
        
}

    $answer = $answer . getLocalizedString($lang, "... Dimmi la tua prossima richiesta o pronuncia stop per terminare", "... sagen Sie mir Ihre nächste Anfrage oder sagen Sie stop um zu beenden.");

$answer =  mb_convert_encoding($answer, 'UTF-8', 'UTF-8');
$qry = "insert into querylog values (0, '".addslashes($wh->get_language())."','".addslashes($wh->get_query())."','".addslashes($answer)."','".$intent."')";
$result = $con2->query($qry);

$wh->respond_simpleMessage($answer, $answer);
?>
