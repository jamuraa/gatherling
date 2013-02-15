<?PHP

class Format {
    
    public $name;
    public $description;
    public $type;        // who has access to filter: public, private, system
    public $series_name; // filter owner
    public $priority;
    public $new;
    
    // card set construction
    public $card_banlist = array();
    public $card_restrictedlist = array();
    public $card_legallist = array ();
    public $legal_sets = array(); 

    // deck construction switches
    public $singleton;
    public $commander;
    public $planechase;
    public $vanguard;
    public $prismatic;
    
    // rarities allowed switches
    public $allow_commons;
    public $allow_uncommons;
    public $allow_rares;
    public $allow_mythics;
    public $allow_timeshifted;
    
    // deck limits
    public $min_main_cards_allowed;
    public $max_main_cards_allowed;
    public $min_side_cards_allowed;
    public $max_side_cards_allowed;
    
    private $error = array();   
    
    function __construct($name) {
        if ($name == "") {
            $this->name = ""; 
            $this->description = "";
            $this->type = "";        
            $this->series_name = ""; 
            $this->priority = 1;
            $this->card_banlist = array();
            $this->card_legallist = array();
            $this->card_restrictedlist = array();
            $this->legal_sets = array(); 
            $this->singleton = 0;
            $this->commander = 0;
            $this->planechase = 0;
            $this->vanguard = 0;
            $this->prismatic = 0;
            $this->allow_commons = 0;
            $this->allow_uncommons = 0;
            $this->allow_rares = 0;
            $this->allow_mythics = 0; 
            $this->allow_timeshifted = 0;
            $this->min_main_cards_allowed = 0;
            $this->max_main_cards_allowed = 0;
            $this->min_side_cards_allowed = 0;
            $this->max_side_cards_allowed = 0;
            $this->new = true;
            return; 
        } 

        if ($this->new) {
            $this->new = false;
            return $this->insertNewFormat();
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT name, description, type, series_name, singleton, commander, planechase, vanguard, 
                                         prismatic, allow_commons, allow_uncommons, allow_rares, allow_mythics, allow_timeshifted, 
                                         priority, min_main_cards_allowed, max_main_cards_allowed, min_side_cards_allowed,
                                         max_side_cards_allowed
                                  FROM formats 
                                  WHERE name = ?");
            if (!$stmt) {
                die($db->error);
            }
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->bind_result($this->name, $this->description, $this->type, $this->series_name, $this->singleton, 
                               $this->commander, $this->planechase, $this->vanguard, $this->prismatic, $this->allow_commons, 
                               $this->allow_uncommons, $this->allow_rares, $this->allow_mythics, $this->allow_timeshifted, 
                               $this->priority, $this->min_main_cards_allowed, $this->max_main_cards_allowed,
                               $this->min_side_cards_allowed, $this->max_side_cards_allowed);
            if ($stmt->fetch() == NULL) {
                throw new Exception('Format '. $name .' not found in DB');
            }
            $stmt->close();
            $this->card_banlist = $this->getBanList();
            $this->card_legallist = $this->getLegalList();
            $this->card_restrictedlist = $this->getRestrictedList();
            $this->legal_sets = $this->getLegalCardsets();
        }
    }

    static public function doesFormatExist($format) {
        $success = false;
        $formatName = array();
        $formatName = Database::single_result_single_param("SELECT name FROM formats WHERE name = ?", "s", $format);
        if (count($formatName)) {
            $success = true;
        }
        return $success;
    }
    
    private function insertNewFormat() {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO formats(name, description, type, series_name, singleton, commander, planechase, 
                                                  vanguard, prismatic, allow_commons, allow_uncommons, allow_rares, allow_mythics, 
                                                  allow_timeshifted, priority, min_main_cards_allowed, max_main_cards_allowed,
                                                  min_side_cards_allowed, max_side_cards_allowed)
                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssddddddddddddddd", 
                          $this->name, $this->description, $this->type, $this->series_name, $this->singleton, 
                          $this->commander, $this->planechase, $this->vanguard, $this->prismatic, $this->allow_commons, 
                          $this->allow_uncommons, $this->allow_rares, $this->allow_mythics, $this->allow_timeshifted, 
                          $this->priority, $this->min_main_cards_allowed, $this->max_main_cards_allowed,
                          $this->min_side_cards_allowed, $this->max_side_cards_allowed);
        $stmt->execute() or die($stmt->error);
        $stmt->close();
        return true;        
    }
    
    public function saveAndDeleteAuthorization($playerName) {
        // this will be used to determine if the save and delete buttons will appear on the format editor
        // there are 3 different format types: system, public, private
        
        $player = new Player($playerName); // to access isOrganizer and isSuper functions
        $authorized = false;
        
        switch ($this->type) {
            case "System":
                 // Only supers can save or delete system formats
                if($player->isSuper()) {$authorized = true;}
                break;
            case "Public":
                // Only Series Organizer of the series that created the format
                // and Supers can save or delete Public formats
                if($player->isOrganizer($this->series_name) || $player->isSuper()) {$athorized = true;}
                break;
            case "Private":
                // The only difference in access between a public and private format is that private formats can be
                // viewed only by the series organizers of the series it belongs to
                // the save and delete access is the same
                if($player->isOrganizer($this->series_name) || $player->isSuper()) {$athorized = true;}
                break;
        }
        return $authorized;
    }
    
    public function viewAuthorization($playerName) {
        // this will be used to determine if a format will appear in the drop down to load in the format filter
        // there are 3 different format types: system, public, private
        
        $player = new Player($playerName); // to access isOrganizer and isSuper functions
        $authorized = false;
        
        switch ($this->type) {
            case "System":
                $authorized = true; // anyone can view a system format
                break;
            case "Public":
                $athorized = true; // anyone can view a public format
                break;
            case "Private":
                // Only supers and organizers can view private formats
                if($player->isOrganizer($this->series_name) || $player->isSuper()) {$athorized = true;}
                break;
        }
        return $authorized;
    }
    
    public function save() {
        if ($this->new) {
            $this->new = false;
            return $this->insertNewFormat();
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE formats 
                                  SET description = ?, type = ?, series_name = ?, singleton = ?, commander = ?, 
                                  planechase = ?, vanguard = ?, prismatic = ?, allow_commons = ?, allow_uncommons = ?, allow_rares = ?, 
                                  allow_mythics = ?, allow_timeshifted = ?, priority = ?, min_main_cards_allowed = ?, 
                                  max_main_cards_allowed = ?, min_side_cards_allowed = ?, max_side_cards_allowed = ?
                                  WHERE name = ?");
            $stmt or die($db->error);
            $stmt->bind_param("sssddddddddddddddds", 
                              $this->description, $this->type, $this->series_name, $this->singleton, $this->commander, 
                              $this->planechase, $this->vanguard, $this->prismatic, $this->allow_commons, $this->allow_uncommons, 
                              $this->allow_rares, $this->allow_mythics, $this->allow_timeshifted, $this->priority, 
                              $this->min_main_cards_allowed, $this->max_main_cards_allowed, $this->min_side_cards_allowed, 
                              $this->max_side_cards_allowed, $this->name);
            $stmt->execute() or die($stmt->error);
            $stmt->close(); 
            return true;
        }
    }
    
    public function saveAs($oldName = "") {
        // name, type, and series_name should all be specified before calling this function
        $success = $this->insertNewFormat();
        if($oldName != "") {
            $oldFormat = new Format($oldName);
            $this->allow_commons = $oldFormat->allow_commons;
            $this->allow_uncommons = $oldFormat->allow_uncommons;
            $this->allow_rares = $oldFormat->allow_rares;
            $this->allow_mythics = $oldFormat->allow_mythics;
            $this->allow_timeshifted = $oldFormat->allow_timeshifted;
            $this->singleton = $oldFormat->singleton;
            $this->commander = $oldFormat->commander;
            $this->planechase = $oldFormat->planechase;
            $this->vanguard = $oldFormat->vanguard;
            $this->prismatic = $oldFormat->prismatic;
            $this->priority = $oldFormat->priority;
            $this->description = $oldFormat->description;
            $this->min_main_cards_allowed = $oldFormat->min_main_cards_allowed;
            $this->max_main_cards_allowed = $oldFormat->max_main_cards_allowed;
            $this->min_side_cards_allowed = $oldFormat->min_side_cards_allowed;
            $this->max_side_cards_allowed = $oldFormat->max_side_cards_allowed;
            $this->new = false;
            $success = $this->save();
            if (!$success) {return false;}
            
            foreach($oldFormat->card_banlist as $bannedCard) {
                $this->insertCardIntoBanlist($bannedCard);
            }
            
            foreach($oldFormat->card_restrictedlist as $restrictedCard) {
                $this->insertCardIntoRestrictedlist($restrictedCard);
            }
            
            foreach($oldFormat->card_legallist as $legalCard) {
                $this->insertCardIntoLegallist($legalCard);
            }
            
            foreach($oldFormat->legal_sets as $legalset) {
                $this->insertNewLegalSet($legalset);
            }            
        }
        return $success;
    }
    
    public function rename($oldName = "") {
    // $this->name, $this->type, and $this->series_name of the new format should all be specified before calling this function
        $success = $this->saveAs($oldName);
        if($oldName != "" && $success) {
            $oldFormat = new Format($oldName);
            $success = $oldFormat->delete();
        }
        return $success;
    }
    
    public function delete() {
        $success = $this->deleteEntireLegallist();
        $success = $this->deleteEntireBanlist();
        $success = $this->deleteEntireRestrictedlist();
        $success = $this->deleteAllLegalSets();
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM formats WHERE name = ? AND series_name = ?");
        $stmt->bind_param("ss", $this->name, $this->series_name);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();  
        return $success;
    }
    
    public function load($formatName) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT name, description, type, series_name, singleton, commander, planechase, vanguard, 
                                     prismatic, allow_commons, allow_uncommons, allow_rares, allow_mythics, allow_timeshifted, 
                                     priority, min_main_cards_allowed, max_main_cards_allowed, min_side_cards_allowed, 
                                     max_side_cards_allowed
                              FROM formats 
                              WHERE name = ?");
        if (!$stmt) {
            die($db->error);
        }
        $stmt->bind_param("s", $formatName);
        $stmt->execute();
        $stmt->bind_result($this->name, $this->description, $this->type, $this->series_name, $this->singleton, 
                           $this->commander, $this->planechase, $this->vanguard, $this->prismatic, $this->allow_commons, 
                           $this->allow_uncommons, $this->allow_rares, $this->allow_mythics, $this->allow_timeshifted, 
                           $this->priority, $this->min_main_cards_allowed, $this->max_main_cards_allowed, 
                           $this->min_side_cards_allowed, $this->max_side_cards_allowed);
        if ($stmt->fetch() == NULL) {
            $this->error[] = "Format not found!";
            return false;
        }
        return true;
    }
    
    public function noFormatLoaded() {
        return (($this->name == "") || (is_null($this->name)));
    }
    
    public function getLegalCardsets() {
        return database::list_result_single_param("SELECT cardset FROM setlegality WHERE format = ?", "s", $this->name);
    }
    
    public function getLegalCard($cardName) {
        $db = Database::getConnection(); 
        $stmt = $db->prepare("SELECT id, name FROM cards WHERE name = ? AND cardset = ?");
        $cardar = array();

        foreach($this->legal_sets as $setName) {
            $stmt->bind_param("ss", $cardName, $setName);
            $stmt->execute(); 
            $stmt->bind_result($cardar['id'], $cardar['name']); 
            if (is_null($stmt->fetch())) {
                $cardar = NULL;
            } else {
                break; // we only need to know that the card exists in the legal card sets once
            } 
        }
        $stmt->close();       
        return $cardar; 
    }
    
    static public function getSystemFormats() {
        return Database::list_result_single_param("SELECT name FROM formats WHERE type = ?", "s", "System");
    }
    
    static public function getPublicFormats() {
        return Database::list_result_single_param("SELECT name FROM formats WHERE type = ?", "s", "Public");
    }
    
    static public function getPrivateFormats($seriesName) {
        return Database::list_result_double_param("SELECT name FROM formats WHERE type = ? AND series_name = ?", 
                                                  "ss", "Private", $seriesName);
    }
    
    static public function getAllFormats() {
        return Database::list_result("SELECT name FROM formats");
    }
    
    public function getCoreCardsets() {
        $legalSets = Database::list_result_single_param("SELECT cardset FROM setlegality WHERE format = ?", "s", $this->name);
        
        $legalCoreSets = array();
        foreach($legalSets as $legalSet) {
            $setType = Database::single_result_single_param("SELECT type FROM cardsets WHERE name = ?", "s", $legalSet);
            if (strcmp($setType, "Core") == 0) {
                $legalCoreSets[] = $legalSet;
            }
        }
        return $legalCoreSets;
    }
    
    public function getBlockCardsets() {
        $legalSets = Database::list_result_single_param("SELECT cardset FROM setlegality WHERE format = ?", "s", $this->name);
        
        $legalBlockSets = array();
        foreach($legalSets as $legalSet) {
            $setType = Database::single_result_single_param("SELECT type FROM cardsets WHERE name = ?", "s", $legalSet);
            if (strcmp($setType, "Block") == 0) {
                $legalBlockSets[] = $legalSet;
            }
        }
        return $legalBlockSets;
    }
    
    public function getExtraCardsets() {
        $legalSets = Database::list_result_single_param("SELECT cardset FROM setlegality WHERE format = ?", "s", $this->name);
        
        $legalExtraSets = array();
        foreach($legalSets as $legalSet) {
            $setType = Database::single_result_single_param("SELECT type FROM cardsets WHERE name = ?", "s", $legalSet);
            if (strcmp($setType, "Extra") == 0) {
                $legalExtraSets[] = $legalSet;
            }
        }
        return $legalExtraSets;
    }
    
    public function getBanList() {
        return Database::list_result_single_param("SELECT card_name 
                                                   FROM bans 
                                                   WHERE format = ? 
                                                   AND allowed = 0", "s", $this->name);
    }
    
    public function getLegalList() {
        return Database::list_result_single_param("SELECT card_name 
                                                   FROM bans 
                                                   WHERE format = ? AND allowed = 1", 
                                                  "s", $this->name);
    }
    
    public function getRestrictedList() {
        return Database::list_result_single_param("SELECT card_name 
                                                   FROM restricted 
                                                   WHERE format = ?", 
                                                  "s", $this->name);        
    }

    public function isError() {
        return count($this->errors) > 0;
    }
    
    public function getErrors() {
        $currentErrors = $this->error;
        $this->error = array();
        return $currentErrors;
    }

    public function getFormats() {
        return Database::list_result("SELECT name FROM formats");
    }
    
    public function isCardLegalByRarity($cardName) {
        $db = Database::getConnection(); 
        $stmt = $db->prepare("SELECT rarity FROM cards WHERE name = ? AND cardset = ?");
        $isLegal = false;
        $cardRarities = array();        

        foreach($this->legal_sets as $setName) {
            $stmt->bind_param("ss", $cardName, $setName);
            $stmt->execute(); 
            $stmt->bind_result($result);
            if ($stmt->fetch()) {
                $cardRarities[] = $result;
            }
        }
        $stmt->close();       

        foreach($cardRarities as $rarity) {
            switch($rarity) {
                case "Land":
                   $isLegal = true;
                    break;
                case "Common":
                    if($this->allow_commons == 1){$isLegal = true;}  
                    break;
                case "Uncommon":
                    if($this->allow_uncommons == 1){$isLegal = true;}  
                    break;
                case "Rare":
                    if($this->allow_rares == 1){$isLegal = true;}  
                    break;
                case "Mythic Rare":
                    if($this->allow_mythics == 1){$isLegal = true;}  
                    break;
                case "Timeshifted":
                    if($this->allow_timeshifted == 1) {$isLegal = true;}
                    break;
                case "Special":
                    if($this->vanguard == 1) {$isLegal = true;}
                    break;                  
            }
        }
        return $isLegal;
    }
    
    public function isCardOnBanList($card) {
        return count(Database::list_result_double_param("SELECT card_name 
                                                         FROM bans 
                                                         WHERE (format = ? 
                                                         AND card_name = ?
                                                         AND allowed = 0)", 
                                                         "ss", $this->name, $card)) > 0;
    }
    
    public function isCardOnLegalList($card) {
        return count(Database::list_result_double_param("SELECT card_name 
                                                         FROM bans 
                                                         WHERE (format = ? 
                                                         AND card_name = ?
                                                         AND allowed = 1)", 
                                                         "ss", $this->name, $card)) > 0;
    }
    
    public function isCardOnRestrictedList($card) {
        return count(Database::list_result_double_param("SELECT card_name 
                                                         FROM restricted 
                                                         WHERE (format = ? 
                                                         AND card_name = ?)", 
                                                         "ss", $this->name, $card)) > 0;
    }
    
    public function isCardSingletonLegal($card, $amt) {
        $isLegal = false;
        
        if($amt == 1) {$isLegal = true;}
        
        switch($card) {
            case "Swamp":
                $isLegal = true;
                break;
            case "Plains":
                $isLegal = true;  
                break;
            case "Island":
                $isLegal = true;  
                break;
            case "Mountain":
                $isLegal = true;  
                break;
            case "Forest":
                $isLegal = true;  
                break;
            case "Snow-Covered Swamp":
                $isLegal = true;
                break;
            case "Snow-Covered Plains":
                $isLegal = true;  
                break;
            case "Snow-Covered Island":
                $isLegal = true;  
                break;
            case "Snow-Covered Mountain":
                $isLegal = true;  
                break;
            case "Snow-Covered Forest":
                $isLegal = true;  
                break;
        }
        return $isLegal;
    }
    
    public function isDeckCommanderLegal($deckID) {
        $isLegal = true;
        $deck = new Deck($deckID);
        $commanderColors = array();
        $commanderCard = Format::getCommanderCard($deck);
        
        if(is_null($commanderCard)) {
            $this->error[] = "Cannot find a Commander in your deck. There must be a Legendary Creature on the sideboard to serve as the Commander.";
            return false;
        } else {
            $commanderColors = Format::getCardColors($commanderCard);            
        }
        
        foreach($deck->maindeck_cards as $card => $amt){
            $colors = Format::getCardColors($card);
            foreach($colors as $color => $num) {
                if($num > 0) {
                    if ($commanderColors[$color] == 0) {
                       $isLegal = false;
                       $this->error[] = "Illegal card: $card. Card contains the color $color which does not match the Commander's Colors. The Commander was determined to be $commanderCard."; 
                    }
                }
            }
        }
        
        return $isLegal;
    }
    
    public static function getCardColors($card) {
        $db = Database::getConnection(); 
        $stmt = $db->prepare("SELECT isw, isr, isg, isu, isb
                              FROM cards 
                              WHERE name = ?"); 
        $stmt->bind_param("s", $card);
        $stmt->execute(); 
        $stmt->bind_result($colors["White"], $colors["Red"], $colors["Green"], $colors["Blue"], $colors["Black"]);
        $stmt->fetch();
        $stmt->close();
        return $colors;        
    }
    
    public static function getCommanderCard($deck) {
        foreach($deck->sideboard_cards as $card => $amt) {
            if(Format::isCardLegendary($card)) {
                return $card;
            }
        }    
        return NULL;
    }
    
    public static function isCardLegendary ($card) {
        return (count(Database::list_result_single_param("SELECT id FROM cards WHERE name = ? AND type LIKE '%Legendary%'", 
                                                         "s", $card)) > 0);
    }
    
    public function isQuantityLegal($card, $amt) {
        $isLegal = false;
        
        if($amt <= 4) {$isLegal = true;}
        
        switch($card) {
            case "Relentless Rats":
                $isLegal = true;
                break;
            case "Swamp":
                $isLegal = true;
                break;
            case "Plains":
                $isLegal = true;  
                break;
            case "Island":
                $isLegal = true;  
                break;
            case "Mountain":
                $isLegal = true;  
                break;
            case "Forest":
                $isLegal = true;  
                break;
            case "Snow-Covered Swamp":
                $isLegal = true;
                break;
            case "Snow-Covered Plains":
                $isLegal = true;  
                break;
            case "Snow-Covered Island":
                $isLegal = true;  
                break;
            case "Snow-Covered Mountain":
                $isLegal = true;  
                break;
            case "Snow-Covered Forest":
                $isLegal = true;  
                break;
        }
        return $isLegal;
    }
    
    public function isQuantityLegalAgainstMain($sideCard, $sideAmt, $mainCard, $mainAmt) {
        $isLegal = false;
        
        if ($sideCard == $mainCard) {
            if(($sideAmt + $mainAmt) <= 4) {$isLegal = true;}
        
            switch($sideCard) {
                case "Relentless Rats":
                    $isLegal = true;
                    break;
                case "Swamp":
                    $isLegal = true;
                    break;
                case "Plains":
                    $isLegal = true;  
                    break;
                case "Island":
                    $isLegal = true;  
                    break;
                case "Mountain":
                    $isLegal = true;  
                    break;
                case "Forest":
                    $isLegal = true;  
                    break;
                case "Snow-Covered Swamp":
                    $isLegal = true;
                    break;
                case "Snow-Covered Plains":
                    $isLegal = true;  
                    break;
                case "Snow-Covered Island":
                    $isLegal = true;  
                    break;
                case "Snow-Covered Mountain":
                    $isLegal = true;  
                    break;
                case "Snow-Covered Forest":
                    $isLegal = true;  
                    break;
                }
        } else {
            $isLegal = true; // mainCard and sideCard don't match so is automatically legal
                             // individual quantity check has already been done. We are only
                             // interested in finding too many of the same card between the side and main
        }
        return $isLegal;
    }
    
    public function insertCardIntoBanlist($card) {
        $card = stripslashes($card);
        $card = $this->getCardName($card);
        $cardID = $this->getCardID($card);
        if (is_null($cardID)) {
            return false; // card not found in database
        }
        
        if($this->isCardOnBanList($card) || $this->isCardOnLegalList($card) || $this->isCardOnLegalList($card)) {
            return false;
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO bans(card_name, card, format, allowed) VALUES(?, ?, ?, 0)");
            $stmt->bind_param("sds", $card, $cardID, $this->name);
            $stmt->execute() or die($stmt->error);
            $stmt->close();
            return true;
        }
    }
    
    public function insertCardIntoLegallist($card) {
        $card = stripslashes($card);
        $card = $this->getCardName($card);
        $cardID = $this->getCardID($card);
        if (is_null($cardID)) {
            return false; // card not found in database
        }
        
        if($this->isCardOnBanList($card) || $this->isCardOnLegalList($card) || $this->isCardOnLegalList($card)) {
            return false;
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO bans(card_name, card, format, allowed) VALUES(?, ?, ?, 1)");
            $stmt->bind_param("sds", $card, $cardID, $this->name);
            $stmt->execute() or die($stmt->error);
            $stmt->close();
            return true;
        }
    }
    
    public function insertCardIntoRestrictedlist($card) {
        $card = stripslashes($card);
        $card = $this->getCardName($card);
        $cardID = $this->getCardID($card);
        if (is_null($cardID)) {
            return false; // card not found in database
        }
        
        if($this->isCardOnBanList($card) || $this->isCardOnLegalList($card) || $this->isCardOnLegalList($card)) {
            return false;
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO restricted(card_name, card, format, allowed) VALUES(?, ?, ?, 2)");
            $stmt->bind_param("sds", $card, $cardID, $this->name);
            $stmt->execute() or die($stmt->error);
            $stmt->close();
            return true;
        }
    }
    
    public function deleteCardFromBanlist ($cardName) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM bans WHERE format = ? AND card_name = ? and allowed = 0");
        $stmt->bind_param("ss", $this->name, $cardName);
        $stmt->execute();
        $removed = $stmt->affected_rows > 0;
        $stmt->close(); 
        return $removed;
     }
     
     public function deleteEntireBanlist () {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM bans WHERE format = ? AND allowed = 0");
        $stmt->bind_param("s", $this->name);
        $stmt->execute();
        $removed = $stmt->affected_rows > 0;
        $stmt->close(); 
        return $removed;         
     }
    
     public function deleteAllLegalSets () {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM setlegality WHERE format = ?");
        $stmt->bind_param("s", $this->name);
        $stmt->execute();
        $removed = $stmt->affected_rows > 0;
        $stmt->close(); 
        return $removed;         
     }
     
    public function deleteCardFromLegallist ($cardName) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM bans WHERE format = ? AND card_name = ? AND allowed = 1");
        $stmt->bind_param("ss", $this->name, $cardName);
        $stmt->execute();
        $removed = $stmt->affected_rows > 0;
        $stmt->close(); 
        return $removed;
     }
     
     public function deleteEntireLegallist () {         
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM bans WHERE format = ? AND allowed = 1");
        $stmt->bind_param("s", $this->name);
        $stmt->execute();
        $removed = $stmt->affected_rows > 0;
        $stmt->close(); 
        return $removed;
     }
    
    public function deleteCardFromRestrictedlist ($cardName) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM restricted WHERE format = ? AND card_name = ?");
        $stmt->bind_param("ss", $this->name, $cardName);
        $stmt->execute();
        $removed = $stmt->affected_rows > 0;
        $stmt->close(); 
        return $removed;
     }
     
     public function deleteEntireRestrictedlist () {         
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM restricted WHERE format = ?");
        $stmt->bind_param("s", $this->name);
        $stmt->execute();
        $removed = $stmt->affected_rows > 0;
        $stmt->close(); 
        return $removed;
     }
    
  private function getCardID($cardname) {
      // Honestly I can't think of a good reason why we would have to ban a specific card (ban by id number). 
      // When you ban a card, don't you want to ban all versions of it? Not just one version?
      // so it makes more sense to ban by card name. But I will implement cardID's for now since that is how the
      // database was set up.
      return Database::single_result_single_param("SELECT id FROM cards WHERE name = ?", "s", $cardname);
  }

  static public function getCardName($cardname) {
      // this is used to return the name of the card as it appears in the database
      // otherwise the ban list will have cards on it like rOnCoR, RONCOR, rONCOR, etc
      return Database::single_result_single_param("SELECT name FROM cards WHERE name = ?", "s", $cardname);
  }
  
  public function insertNewLegalSet($cardsetName) {
      $db = Database::getConnection();
      $stmt = $db->prepare("INSERT INTO setlegality(format, cardset)VALUES(?, ?)");
      $stmt->bind_param("ss", $this->name, $cardsetName);
      $stmt->execute() or die($stmt->error);
      $stmt->close();
      return true;      
  }
  
  public function deleteLegalCardSet($cardsetName) {
      $db = Database::getConnection();
      $stmt = $db->prepare("DELETE FROM setlegality WHERE format = ? AND cardset = ?");
      $stmt->bind_param("ss", $this->name, $cardsetName);
      $stmt->execute();
      $removed = $stmt->affected_rows > 0;
      $stmt->close(); 
      return $removed;
  }
  
  static public function formatEditor($calledBy, $format = "", $seriesName = "System") {
    $active_format = NULL;
    if(Format::doesFormatExist($format)) {
        $active_format = new Format($format);   
        $bandCards = $active_format->getBanList();
        $legalCards = $active_format->getLegalList();
        $restrictedCards = $active_format->getRestrictedList();
        $coreCardSets = $active_format->getCoreCardsets();
        $blockCardSets = $active_format->getBlockCardsets();
        $extraCardSets = $active_format->getExtraCardsets();
    } else {
        $active_format = new Format("");
        $bandCards = array();
        $legalCards = array();
        $restrictedCards = array();
        $coreCardSets = array();
        $blockCardSets = array();
        $extraCardSets = array();
    }
    echo "<center>";
    echo "<h3>Format Editor</h3>";
    if ($active_format->name != "") {echo "<h4>Currently Editing: $active_format->name</h4>";}
    
    echo "<form action=\"{$calledBy}\" method=\"post\">"; 
    echo "<input type=\"hidden\" name=\"view\" value=\"no_view\" />";
    echo "<input type=\"hidden\" name=\"format\" value=\"{$active_format->name}\" />";
    echo "<input type=\"hidden\" name=\"series\" value=\"{$seriesName}\" />";
    echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">";
    echo "<tr><td class=\"buttons\"><input class=\"inputbutton\" style=\"width: 75px\" type=\"submit\" value=\"New\" name =\"action\" />";
    echo "<input class=\"inputbutton\" style=\"width: 75px\" type=\"submit\" value=\"Load\" name =\"action\" />";
    echo "<input class=\"inputbutton\" style=\"width: 75px\" type=\"submit\" value=\"Save As\" name =\"action\" />"; 
    echo "<input class=\"inputbutton\" style=\"width: 75px\" type=\"submit\" value=\"Rename\" name =\"action\" />"; 
    echo "<input class=\"inputbutton\" style=\"width: 75px\" type=\"submit\" value=\"Delete\" name =\"action\" /></tr>";
    echo "</table></form>";
    
    echo "<p style=\"width: 75%; text-align: left;\">This is where you define the format for your series. Step one is to 
          add the card sets that you want to allow players to use to build decks. Once you do that, any cards in those 
          sets you don't want players to use, add to the ban list. You don't need to ban cards that aren't in the allowed 
          card sets. Finally make sure that the appropriate rarities that you want to allow are checked. For example
          a pauper event would leave only the commons box checked.</p>";
    echo "<p style=\"width: 75%; text-align: left;\">The name of this filter will default to the name of the series.  
          To use this filter, go to the Season Points Management->Season Format and select this filter. This sets the
          filter to be used for the entire season. You can also set this filter by going to Host CP->Format. This only
          sets the filter to be used for that single event.</p>";
    echo "<p style=\"width: 75%; text-align: left;\">Coming in a future update will be the ability for you to create
          and manage your own custom filters. That way you can have Alt Events that have special filters.</p>";

    echo "<form action=\"{$calledBy}\" method=\"post\">"; 
    echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
    echo "<input type=\"hidden\" name=\"format\" value=\"{$active_format->name}\" />";
    echo "<input type=\"hidden\" name=\"series\" value=\"{$seriesName}\" />";

    echo "<h4>Format Description</h4>";
    echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">";
    if ($active_format->noFormatLoaded()) {
        echo "<tr><td>";
        echo "<textarea class=\"inputbox\" rows=\"10\" cols=\"60\" disabled=\"disabled\">";
        echo "$active_format->description";
        echo "</textarea>";
        echo "</td></tr>\n";
    } else {
        echo "<tr><td>";
        echo "<textarea class=\"inputbox\" rows=\"10\" cols=\"60\" name=\"formatdescription\">";
        echo "$active_format->description";
        echo "</textarea>";
        echo "</td></tr>\n";
    }
    echo "</table>";
    echo "<h4>Card Modifiers</h4>";
    echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">";
    echo "<tr><th>Minimum Mainboard Cards</th>";
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 50px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 50px; text-align: center;\">";
        stringField("minmain", $active_format->min_main_cards_allowed, 5);
        echo "</td>";
    }
    echo "<th>&nbsp;Maximum Mainboard Cards&nbsp;</th>";
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 50px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 50px; text-align: center;\">";
        stringField("maxmain", $active_format->max_main_cards_allowed, 5);
        echo "</td>";
    }
    echo "</tr><tr><th>Minimum Sideboard Cards</th>";
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 50px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 50px; text-align: center;\">";
        stringField("minside", $active_format->min_side_cards_allowed, 5);
        echo "</td>";
    }
    echo "<th>&nbsp;Maximum Sideboard Cards&nbsp;</th>";
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 50px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 50px; text-align: center;\">";
        stringField("maxside", $active_format->max_side_cards_allowed, 5);
        echo "</td>";
    }
    echo "</tr></table>";
    
    echo "<h4>Deck Modifiers</h4>";
    echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">";
    echo "<tr><th style=\"width: 100px; text-align: center;\">Singleton</th><th style=\"width: 100px; text-align: center;\">Commander</th>";
    echo "<th style=\"width: 100px; text-align: center;\">Vanguard</th><th style=\"width: 100px; text-align: center;\">Planechase</th>";
    echo "<th style=\"width: 100px; text-align: center;\">Prismatic</th></tr>";
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 100px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 100px; text-align: center;\"><input type=\"checkbox\" name=\"singleton\" value=\"1\" ";
        if($active_format->singleton == 1) {echo "checked=\"yes\" ";}   
        echo " /></td>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 100px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 100px; text-align: center;\"><input type=\"checkbox\" name=\"commander\" value=\"1\" ";
        if($active_format->commander == 1) {echo "checked=\"yes\" ";} 
        echo " /></td>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 100px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 100px; text-align: center;\"><input type=\"checkbox\" name=\"vanguard\" value=\"1\" ";
        if($active_format->vanguard == 1) {echo "checked=\"yes\" ";}    
        echo " /></td>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 100px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 100px; text-align: center;\"><input type=\"checkbox\" name=\"planechase\" value=\"1\" ";
        if($active_format->planechase == 1) {echo "checked=\"yes\" ";}    
        echo " /></td>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 100px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 100px; text-align: center;\"><input type=\"checkbox\" name=\"prismatic\" value=\"1\" ";
        if($active_format->prismatic == 1) {echo "checked=\"yes\" ";}    
        echo " /></td>";
    }
    echo "</tr></table>";
    
    echo "<h4>Allow Rarity Selection</h4>";
    echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">";
    echo "<tr><th style=\"width: 100px; text-align: center;\">Commons</th><th style=\"width: 100px; text-align: center;\">Uncommons</th>";
    echo "<th style=\"width: 100px; text-align: center;\">Rares</th><th style=\"width: 100px; text-align: center;\">Mythics</th>";
    echo "<th style=\"width: 100px; text-align: center;\">Timeshifted</th></tr>";
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 100px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 100px; text-align: center;\"><input type=\"checkbox\" name=\"allowcommons\" value=\"1\" ";
        if($active_format->allow_commons == 1) {echo "checked=\"yes\" ";}   
        echo " /></td>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 100px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 100px; text-align: center;\"><input type=\"checkbox\" name=\"allowuncommons\" value=\"1\" ";
        if($active_format->allow_uncommons == 1) {echo "checked=\"yes\" ";} 
        echo " /></td>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 100px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 100px; text-align: center;\"><input type=\"checkbox\" name=\"allowrares\" value=\"1\" ";
        if($active_format->allow_rares == 1) {echo "checked=\"yes\" ";}    
        echo " /></td>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 100px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 100px; text-align: center;\"><input type=\"checkbox\" name=\"allowmythics\" value=\"1\" ";
        if($active_format->allow_mythics == 1) {echo "checked=\"yes\" ";}    
        echo " /></td>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<td style=\"width: 100px; text-align: center;\">";
        not_allowed("No Format Loaded, Please Load a Format to Edit");
        echo "</td>";
    } else {
        echo "<td style=\"width: 100px; text-align: center;\"><input type=\"checkbox\" name=\"allowtimeshifted\" value=\"1\" ";
        if($active_format->allow_timeshifted == 1) {echo "checked=\"yes\" ";}    
        echo " /></td>";
    }
    echo "</tr>";
    echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
    echo "<tr>";
    if ($active_format->noFormatLoaded()) {
        echo "<td colspan=\"5\" class=\"buttons\">";
        echo "<input class=\"inputbutton\" type=\"submit\" value=\"Update Format\" name =\"action\" disabled=\"disabled\" />";
        echo "</td>";
    } else {
        echo "<td colspan=\"5\" class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Update Format\" name =\"action\" /></td>";
    }
    echo "</tr>";
    echo "</table></form>";
    
    // beginning of the restricted list
        $cardCount = count($restrictedCards);
        echo "<form action=\"{$calledBy}\" method=\"post\">"; 
        echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
        echo "<input type=\"hidden\" name=\"format\" value=\"{$active_format->name}\" />";
        echo "<input type=\"hidden\" name=\"series\" value=\"{$seriesName}\" />";
        echo "<h4>Card Restricted List: $cardCount Cards</h4>\n";
        echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
        echo "<tr><th style=\"text-align: center;\">Card Name</th><th style=\"width: 50px; text-align: center;\">Delete</th></tr>";
        if (count($restrictedCards)) {
            foreach($restrictedCards as $card) {
                echo "<tr><td style=\"text-align: center;\">";
                // don't print card link if list is over 100 cards
                if ($cardCount > 100) {
                    echo "$card <br />";
                } else {
                    printCardLink($card);
                }
                echo "</td>";
                echo "<td style=\"text-align: center;\">";
                echo "<input type=\"checkbox\" name=\"delrestrictedcards[]\" value=\"{$card}\" /></td></tr>";
            }
        } else {
            echo "<tr><td><font color=\"red\">No cards have been restricted</font></td>";
            echo "<td style=\"width: 100px; text-align: center;\">";
            if ($active_format->noFormatLoaded()) {
                not_allowed("No Format Loaded, Please Load a Format to Edit");
            } else {
                not_allowed("No Restricted Cards To Delete");            
            }
            echo "</td>";
            echo "</tr>";
        }
        if ($active_format->noFormatLoaded()) {
            echo "<tr><td colspan=\"2\"> Add new: ";
            echo "<textarea class=\"inputbox\" disabled=\"disabled\" rows=\"5\" cols=\"40\"></textarea></td></tr>\n";
            echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
            echo "<tr>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Update Restricted List\" name =\"action\" disabled=\"disabled\" /></td>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Delete Entire Restricted List\" name =\"action\" disabled=\"disabled\" /></td>";
        } else {
            echo "<tr><td colspan=\"2\"> Add new: ";
            echo "<textarea class=\"inputbox\" rows=\"5\" cols=\"40\" name=\"addrestrictedcard\"></textarea></td></tr>\n";
            echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
            echo "<tr>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Update Restricted List\" name =\"action\" /></td>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Delete Entire Restricted List\" name =\"action\" /></td>";
        }
        echo "</tr></table></form>";
    
    // if the series is using a legal card list, don't show the banlist
    if (!count($legalCards)) {
        $cardCount = count($bandCards);
        echo "<form action=\"{$calledBy}\" method=\"post\">"; 
        echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
        echo "<input type=\"hidden\" name=\"format\" value=\"{$active_format->name}\" />";
        echo "<input type=\"hidden\" name=\"series\" value=\"{$seriesName}\" />";
        echo "<h4>Card Banlist: $cardCount Cards</h4>\n";
        echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
        echo "<tr><th style=\"text-align: center;\">Card Name</th><th style=\"width: 50px; text-align: center;\">Delete</th></tr>";
        if (count($bandCards)) {
            foreach($bandCards as $card) {
                echo "<tr><td style=\"text-align: center;\">";
                // don't print card link if list is over 100 cards
                if ($cardCount > 100) {
                    echo "$card <br />";
                } else {
                    printCardLink($card);
                }
                echo "</td>";
                echo "<td style=\"text-align: center;\">";
                echo "<input type=\"checkbox\" name=\"delbancards[]\" value=\"{$card}\" /></td></tr>";
            }
        } else {
            echo "<tr><td><font color=\"red\">No cards have been banned</font></td>";
            echo "<td style=\"width: 100px; text-align: center;\">";
            if ($active_format->noFormatLoaded()) {
                not_allowed("No Format Loaded, Please Load a Format to Edit");
            } else {
                not_allowed("No Ban Cards To Delete");            
            }
            echo "</td>";
            echo "</tr>";
        }
        if ($active_format->noFormatLoaded()) {
            echo "<tr><td colspan=\"2\"> Add new: ";
            echo "<textarea class=\"inputbox\" rows=\"5\" cols=\"40\" disabled=\"disabled\"></textarea></td></tr>\n";
            echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
            echo "<tr>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Update Banlist\" name =\"action\" disabled=\"disabled\" /></td>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Delete Entire Banlist\" name =\"action\" disabled=\"disabled\" /></td>";
        } else {
            echo "<tr><td colspan=\"2\"> Add new: ";
            echo "<textarea class=\"inputbox\" rows=\"5\" cols=\"40\" name=\"addbancard\"></textarea></td></tr>\n";
            echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
            echo "<tr>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Update Banlist\" name =\"action\" /></td>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Delete Entire Banlist\" name =\"action\" /></td>";
        }
        echo "</tr></table></form>";
    }

    // if the series is using a ban list, then don't show the legal card list
    if (!count($bandCards)) {
        $cardCount = count($legalCards);
        echo "<form action=\"{$calledBy}\" method=\"post\">"; 
        echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
        echo "<input type=\"hidden\" name=\"format\" value=\"{$active_format->name}\" />";
        echo "<input type=\"hidden\" name=\"series\" value=\"{$seriesName}\" />";
        echo "<h4>Legal Card List: $cardCount Cards</h4>\n";
        echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
        echo "<tr><th style=\"text-align: center;\">Card Name</th><th style=\"width: 50px; text-align: center;\">Delete</th></tr>";
        if (count($legalCards)) {
            foreach($legalCards as $card) {
                echo "<tr><td style=\"text-align: center;\">";
                // don't print card link if list is over 100 cards
                if ($cardCount > 100) {
                    echo "$card <br />";
                } else {
                    printCardLink($card);
                }
                echo "</td>";
                echo "<td style=\"text-align: center;\">";
                echo "<input type=\"checkbox\" name=\"dellegalcards[]\" value=\"{$card}\" /></td></tr>";
            }
        } else {
            echo "<tr><td><font color=\"red\">No cards have been allowed</font></td>";
            echo "<td style=\"width: 100px; text-align: center;\">";
            if ($active_format->noFormatLoaded()) {
                not_allowed("No Format Loaded, Please Load a Format to Edit");
            } else {
                not_allowed("No Legal List Cards to Delete");            
            }
            echo "</td>";
            echo "</tr>";
        }
        if ($active_format->noFormatLoaded()) {
            echo "<tr><td colspan=\"2\"> Add new: ";
            echo "<textarea class=\"inputbox\" rows=\"5\" cols=\"40\" disabled=\"disabled\"></textarea></td></tr>\n";
            echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
            echo "<tr>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Update Legal List\" name =\"action\" disabled=\"disabled\" /></td>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Delete Entire Legal List\" name =\"action\" disabled=\"disabled\" /></td>";
        } else {
            echo "<tr><td colspan=\"2\"> Add new: ";
            echo "<textarea class=\"inputbox\" rows=\"5\" cols=\"40\" name=\"addlegalcard\"></textarea></td></tr>\n";
            echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
            echo "<tr>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Update Legal List\" name =\"action\" /></td>";
            echo "<td class=\"buttons\"><input class=\"inputbutton\" type=\"submit\" value=\"Delete Entire Legal List\" name =\"action\" /></td>";
        }
        echo "</tr></table></form>";
    }

    echo "<h4>Core Cardsets Allowed</h4>\n";
    echo "<form action=\"{$calledBy}\" method=\"post\">"; 
    echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
    echo "<input type=\"hidden\" name=\"format\" value=\"{$active_format->name}\" />";
    echo "<input type=\"hidden\" name=\"series\" value=\"{$seriesName}\" />";
    echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
    echo "<tr><th style=\"text-align: center;\">Cardset Name</th><th style=\"width: 50px; text-align: center;\">Delete</th></tr>";
    if (count($coreCardSets)) {
        foreach($coreCardSets as $setName) {
            echo "<tr><td style=\"text-align: center;\">{$setName}</td>";
            echo "<td style=\"text-align: center; width: 50px; \"><input type=\"checkbox\" name=\"delcardsetname[]\" value=\"{$setName}\" />";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td><font color=\"red\">No Core Sets are Allowed</font></td>";
        echo "<td style=\"width: 100px; text-align: center;\">";
        if ($active_format->noFormatLoaded()) {
            not_allowed("No Format Loaded, Please Load a Format to Edit");
        } else {
            not_allowed("No Selected Card Set To Delete");            
        }
        echo "</td>";
        echo "</tr>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<tr><td>";
        cardsetDropMenu("Core", $active_format, true);
        echo "</td>";
        echo "<td colspan=\"2\" class=\"buttons\">";
        echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
        echo "<input class=\"inputbutton\" type=\"submit\" value=\"Update Cardsets\" name =\"action\" disabled=\"disabled\" />";
    } else {
        echo "<tr><td>";
        cardsetDropMenu("Core", $active_format, false);
        echo "</td>";
        echo "<td colspan=\"2\" class=\"buttons\">";
        echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
        echo "<input class=\"inputbutton\" type=\"submit\" value=\"Update Cardsets\" name =\"action\" />";
    }
    echo"</td></tr></table></form>";

    echo "<h4>Block Cardsets Allowed</h4>\n";
    echo "<form action=\"{$calledBy}\" method=\"post\">"; 
    echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
    echo "<input type=\"hidden\" name=\"format\" value=\"{$active_format->name}\" />";
    echo "<input type=\"hidden\" name=\"series\" value=\"{$seriesName}\" />";
    echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
    echo "<tr><th style=\"text-align: center;\">Cardset Name</th><th style=\"width: 50px; text-align: center;\">Delete</th></tr>";
    if (count($blockCardSets)) {
        foreach($blockCardSets as $setName) {
            echo "<tr><td style=\"text-align: center;\">{$setName}</td>";
            echo "<td style=\"text-align: center; width: 50px; \"><input type=\"checkbox\" name=\"delcardsetname[]\" value=\"{$setName}\" />";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td><font color=\"red\">No Block Sets are Allowed</font></td>";
        echo "<td style=\"width: 100px; text-align: center;\">";
        if ($active_format->noFormatLoaded()) {
            not_allowed("No Format Loaded, Please Load a Format to Edit");
        } else {
            not_allowed("No Selected Card Set To Delete");            
        }
        echo "</td>";
        echo "</tr>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<tr><td>";
        cardsetDropMenu("Block", $active_format, true);
        echo "</td>";
        echo "<td colspan=\"2\" class=\"buttons\">";
        echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
        echo "<input class=\"inputbutton\" type=\"submit\" value=\"Update Cardsets\" name =\"action\" disabled=\"disabled\" />";
    } else {
        echo "<tr><td>";
        cardsetDropMenu("Block", $active_format, false);
        echo "</td>";
        echo "<td colspan=\"2\" class=\"buttons\">";
        echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
        echo "<input class=\"inputbutton\" type=\"submit\" value=\"Update Cardsets\" name =\"action\" />";
    }
    echo"</td></tr></table></form>";

    echo "<h4>Extra Cardsets Allowed</h4>\n";
    echo "<form action=\"{$calledBy}\" method=\"post\">"; 
    echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
    echo "<input type=\"hidden\" name=\"format\" value=\"{$active_format->name}\" />";
    echo "<input type=\"hidden\" name=\"series\" value=\"{$seriesName}\" />";
    echo "<table class=\"form\" style=\"border-width: 0px;\" align=\"center\">"; 
    echo "<tr><th style=\"text-align: center;\">Cardset Name</th><th style=\"width: 50px; text-align: center;\">Delete</th></tr>";
    if (count($extraCardSets)) {
        foreach($extraCardSets as $setName) {
            echo "<tr><td style=\"text-align: center;\">{$setName}</td>";
            echo "<td style=\"text-align: center; width: 50px;\"><input type=\"checkbox\" name=\"delcardsetname[]\" value=\"{$setName}\" />";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td><font color=\"red\">No Extra Sets are Allowed</font></td>";
        echo "<td style=\"width: 100px; text-align: center;\">";
        if ($active_format->noFormatLoaded()) {
            not_allowed("No Format Loaded, Please Load a Format to Edit");
        } else {
            not_allowed("No Selected Card Set To Delete");            
        }
        echo "</td>";
        echo "</tr>";
    }
    if ($active_format->noFormatLoaded()) {
        echo "<tr><td>";
        cardsetDropMenu("Extra", $active_format, true);
        echo "</td>";
        echo "<td colspan=\"2\" class=\"buttons\">";
        echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
        echo "<input class=\"inputbutton\" type=\"submit\" value=\"Update Cardsets\" name =\"action\" disabled=\"disabled\" />";
    } else {
        echo "<tr><td>";
        cardsetDropMenu("Extra", $active_format, false);
        echo "</td>";
        echo "<td colspan=\"2\" class=\"buttons\">";
        echo "<input type=\"hidden\" name=\"view\" value=\"format_editor\" />";
        echo "<input class=\"inputbutton\" type=\"submit\" value=\"Update Cardsets\" name =\"action\" />";
    }
    echo"</td></tr></table></form></center>";
  }  
}
