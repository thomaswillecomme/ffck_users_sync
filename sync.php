<?php

require_once ('actions.php');



class ffck_connector{
    var $login;
    var $password;
    var $token;
    var $cookie_file;
    var $session;
    
    //login : a string containing the login of the user to perform all queries. It is actualy the licence id. The user needs access to member list.
    //password : the password of the user
    function __construct($login, $password){
        $this->login = $login;
        $this->password = $password;
        $this->cookie_file = dirname(__FILE__).'/cookie.txt';
        unlink($this->cookie_file); // remove previous cookies
    }
    
   
    //we get a temporary session for login purpose (token, username and password are not used by remote server)
    function get_session(){
        echo '#fetching temporary session and token'.PHP_EOL;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ffck-goal.multimediabs.com/login');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ffck-goal.multimediabs.com', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'));
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // get headers too with this line
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $result = curl_exec($ch);
        // get cookie
        // multi-cookie variant contributed by @Combuster in comments
        $cookies = $this->get_cookies($result);
        $this->session = $cookies['FFCK_SESSION'];
        echo 'got session '.$cookies['FFCK_SESSION'].PHP_EOL;
        
        $pattern ='<input type="hidden" name="authenticityToken" value="(?P<token>.*?)">';
        preg_match($pattern, $result, $matches, PREG_OFFSET_CAPTURE, 3);
        $this->token= $matches['token'][0];
        echo 'got token '.$this->token.PHP_EOL;
    }
    
    
    function get_cookies($header){
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        
        return $cookies;
    }
    
    //third we run again the request with the temporary session id, login, password and token. it will return an actual session id
    function login(){
        //$this->get_token();
        $this->get_session();
        
        echo '#Login in'.PHP_EOL;
        $this->flash='%00previousUrl%3A%2F%00%00url%3A%2F%00';
        $this->lb='was02';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ffck-goal.multimediabs.com/login');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ffck-goal.multimediabs.com', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('authenticityToken' => $this->token, 'username' => $this->login, 'password'=> $this->password)));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // get headers too with this line

        $result = curl_exec($ch);
        
        $cookies = $this->get_cookies($result);
        
        
        if(strcmp($this->session,$cookies['FFCK_SESSION']) == 0){
            echo 'session not updated, login failed'.PHP_EOL;
            var_dump($result);
            return FALSE;
        }else{
            echo 'session updated ('.$cookies['FFCK_SESSION'].'), logged in'.PHP_EOL;
            return TRUE;
        }
    }

    
    function get_members($season){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ffck-goal.multimediabs.com/licences/afficherlistelicencies?idSaison='.$season);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ffck-goal.multimediabs.com', 'Accept: application/json; q=0.01'));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_HEADER, 1);
        $result = curl_exec($ch);

        

        $php_result = json_decode($result,true);
        curl_close($ch);
        return $php_result['licences'];

        

    }
    
    function get_member_data($person_id){
        //curl 'https://ffck-goal.multimediabs.com/personnes/gettabpanel?tabId=Coordonnees_Personne&personne.id=293774' -H 'Host: ffck-goal.multimediabs.com' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:42.0) Gecko/20100101 Firefox/42.0' -H 'Accept: */*' -H 'Accept-Language: en-US,en;q=0.5' --compressed -H 'X-Requested-With: XMLHttpRequest' -H 'Referer: https://ffck-goal.multimediabs.com/personnes/show?idPersonne=293774&selectedTab=Coordonnees_Personne' -H 'Cookie: LB=was03; FFCK_SESSION=f49c99cf5055331b3ce598ba2f641dfb27adf3e3-%00idStructureTravail%3A1836%00%00___ID%3Aedfebc2a-e5ef-4f31-99d7-60503edb428f%00%00username%3A260864%00%00idSaisonEnCours%3A2015%00%00userId%3A269806%00%00___AT%3Af7736c82c5258db5ca51974b26e43817c335d937%00%00idSaisonLicence%3A2015%00; FFCK_FLASH=%00previousUrl%3A%2Flicences%2FlisterLicencies%3FidStructure%3D1836%00%00url%3A%2Fpersonnes%2Fshow%3FidPersonne%3D293774%26selectedTab%3DCoordonnees_Personne%00' -H 'DNT: 1' -H 'Connection: keep-alive'
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ffck-goal.multimediabs.com/personnes/gettabpanel?tabId=Coordonnees_Personne&personne.id='.$person_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ffck-goal.multimediabs.com', 'Accept: application/json; q=0.01'));
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        
        $member = array();
        $member['licence_id']=$this->extract_id($result,'identite.codeAdherent');
        $member['email']=$this->extract($result,'~<span id="email"><a href="mailto:.*?">(?P<email>.*?)</a></span>~', 'email');
        $member['firstname']=$this->extract_id($result,'identite.prenom');
        $member['lastname']=$this->extract_id($result,'identite.nom');
        $member['birthdate']=DateTime::createFromFormat('d/m/Y',$this->extract_id($result,'identite.dateNaissance'))->setTime(0,0)->format('Y-m-d');
        $member['gender']=$this->extract_id($result,'identite.sexe');
        $member['maidname']=$this->extract_id($result,'identite.nomJeuneFille');
        $member['phone']=$this->extract_id($result,'mobile');
        $member['phone2']=$this->extract_id($result,'mobile2');
        $member['address']=$this->extract_id($result,'numero')
                            .' '.$this->extract_id($result,'typeVoie')
                            .' '.$this->extract_id($result,'nomVoie')
                            .' '.$this->extract_id($result,'codePostal')
                            .' '.$this->extract_id($result,'ville')
                            .' '.$this->extract_id($result,'pays');
        
        //curl 'https://ffck-goal.multimediabs.com/personnes/gettabpanel?tabId=Licences_Personne&personne.id=260864' -H 'Host: ffck-goal.multimediabs.com' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:42.0) Gecko/20100101 Firefox/42.0' -H 'Accept: */*' -H 'Accept-Language: en-US,en;q=0.5' --compressed -H 'X-Requested-With: XMLHttpRequest' -H 'Referer: https://ffck-goal.multimediabs.com/personnes/show?idPersonne=260864&selectedTab=Coordonnees_Personne' -H 'Cookie: LB=was03; FFCK_SESSION=f49c99cf5055331b3ce598ba2f641dfb27adf3e3-%00idStructureTravail%3A1836%00%00___ID%3Aedfebc2a-e5ef-4f31-99d7-60503edb428f%00%00username%3A260864%00%00idSaisonEnCours%3A2015%00%00userId%3A269806%00%00___AT%3Af7736c82c5258db5ca51974b26e43817c335d937%00%00idSaisonLicence%3A2015%00; FFCK_FLASH=%00previousUrl%3A%2Flicences%2FlisterLicencies%3FidStructure%3D1836%00%00url%3A%2Fpersonnes%2Fshow%3FidPersonne%3D260864%26selectedTab%3DCoordonnees_Personne%00' -H 'DNT: 1' -H 'Connection: keep-alive'
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ffck-goal.multimediabs.com/personnes/gettabpanel?tabId=Licences_Personne&personne.id='.$licence_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ffck-goal.multimediabs.com', 'Accept: application/json; q=0.01'));
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        
        //get all medical certificates
        $certificats = array();

        //get last competition certificate date and last regular certificate date (competition certificate work for both)
        $competition_timestamp=0;
        $regular_timestamp=0;
        for($index=0; $index!=2; $index++){
            $date=$this->extract_id($result,'licence.certificat_'. ($index+1) .'.date');
            if($date != FALSE){
                $certificats[$index] = array();
                $certificats[$index]['date'] = DateTime::createFromFormat('d/m/Y',$date)->setTime(0,0)->getTimestamp();
                $certificats[$index]['competition'] = strstr($this->extract_id($result,'licence.certificat_'. ($index+1) .'.libelle'), 'en comp&eacute;tition') == FALSE ? FALSE : TRUE;
            }
            
            if($regular_timestamp < $certificats[$index]['date'])
                $regular_timestamp = $certificats[$index]['date'];
                        
            if($certificats[$index]['competition'] && $competition_timestamp < $certificats[$index]['date'])
                $competition_timestamp = $certificats[$index]['date'];
        }

        if($competition_timestamp != 0){
            $member['competition_certificate']= new DateTime();
            $member['competition_certificate']= $member['competition_certificate']->setTimestamp($competition_timestamp)->format('Y-m-d');
        }else{
            $member['competition_certificate'] = NULL;
        }
            
        if($regular_timestamp != 0){
            $member['certificate']= new DateTime();
            $member['certificate']= $member['certificate']->setTimestamp($regular_timestamp)->format('Y-m-d');        
        }else{
            $member['certificate'] = NULL;
        }
        return $member;
    }
    
    static function extract($html,$regex, $key){
        preg_match($regex, $html, $matches, PREG_OFFSET_CAPTURE, 3);
        return $matches[$key][0];
    }
    
    static function extract_id($html,$id){
        $compatible_id = str_replace('.','_',$id);
        return ffck_connector::extract($html, '~id="'.$id.'".*?>(?P<'.$compatible_id.'>.*?)<~', $compatible_id);
    }
        
}

function ffck_sync($season = NULL){
    if($season == NULL)
        $season = (int)date("Y");
    //FIXME unsecure
    $connector = new ffck_connector($_POST['user'],$_POST['password']);

    if($connector->login() == FALSE){
        return;
    }


   
    //FIXME unsecure
    echo 'fetching users list...';
    $members = $connector->get_members($season);
    if($members == FALSE){
        return;
    }
    echo 'done : found '.count($members).PHP_EOL;

    echo 'fetching users informations...'.PHP_EOL;
    foreach($members as $member){
        //get data for this member
        $member_data = $connector->get_member_data(ffck_connector::extract($member[9],'~style=\'display:none\'>(?P<id>.*?)</span>~', 'id'));
        $member_data['season'] = $season;
        //check against wordpress user database
        ffck_check_user($member_data);
    }

    echo 'done fetching users informations'.PHP_EOL;
    

}

