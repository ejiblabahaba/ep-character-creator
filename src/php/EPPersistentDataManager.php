<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.cc
 */

/**
 * Description of EPPersistentDataManager
 *
 * @author reinhardt
 */

require_once 'EPAtom.php';
require_once 'EPSkill.php';
require_once 'EPBonusMalus.php';
require_once 'EPTrait.php';
require_once 'EPConfigFile.php';
require_once 'EPBackground.php';
require_once 'EPGear.php';
require_once 'EPCreditCost.php';
require_once 'EPMorph.php';
require_once 'EPAi.php';
require_once 'EPPsySleight.php';


class EPPersistentDataManager {

    public $errors;
    private $mysqli;
    
    function __construct($configPath) {
        $this->errors = array();
        
        $this->configValues = new EPConfigFile($configPath);
        $serverName = $this->configValues->getValue('SQLValues','serverName');
        $databaseName = $this->configValues->getValue('SQLValues','databaseName');
        $databaseUser = $this->configValues->getValue('SQLValues','databaseUser');
        $databasePassword = $this->configValues->getValue('SQLValues','databasePassword'); 
        $databasePort = $this->configValues->getValue('SQLValues','databasePort');        

        $this->mysqli = new mysqli($serverName, $databaseUser, $databasePassword, $databaseName, $databasePort);
        
        if ($this->mysqli->connect_errno) {
             $this->addError("Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error);
        };
    }
    
    function addError($error){
        array_push($this->errors, $error);
    }
    
    function getLastError(){
        return array_pop($this->errors);
    }
    
    //===== SKILLS ==========
    
    function persistSkill($epSkill){
        if($epSkill->type == EPAtom::$SKILL){
            $insertQuerry = "INSERT INTO `skills`(`name`, `desc`, `linkedApt`,`prefix`, `skillType`, `defaultable`) VALUES ('"
                                                .$this->adjustForSQL($epSkill->name)."','"
                                                .$this->adjustForSQL($epSkill->description)."','"
                                                .$epSkill->linkedApt->abbreviation."','"
                                                .$this->adjustForSQL($epSkill->prefix)."','"
                                                .$epSkill->skillType."','"
                                                .$epSkill->defaultable
                                                ."')";
           
           if($this->mysqli->query($insertQuerry)){
            $groups = $epSkill->groups;
            if($groups  != null){
                foreach($groups as $gr){

                    $insertGroupTargetQuerry = "INSERT INTO `GroupName`(`groupName`, `targetName`) VALUES ('"
                                                   .$this->adjustForSQL($gr)."','"
                                                   .$this->adjustForSQL($epSkill->name)
                                                   ."')";

                   if(!$this->mysqli->query($insertGroupTargetQuerry)){
                       $this->addError("Skill groups list ".$epSkill->atomUid." and ".$gr."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                       return false;
                   }
                }
            }
            return true;      
           }
           else {
               $this->addError("Skill core ".$epSkill->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epSkill->type." on persistSkill()");
            return false;
        }
    }
    
    //======= BONUS MALUS ========
    
    function persistBonusMalus($epBonusMalus){
         if($epBonusMalus->type == EPAtom::$BONUSMALUS){
            $insertQuerry = "INSERT INTO `bonusMalus`(`name`, `desc`, `type`, `target`, `value`, `tragetForCh`, `typeTarget`, `onCost`, `multiOccur`)  VALUES ('"
                                                        .$this->adjustForSQL($epBonusMalus->name)."','"
                                                        .$this->adjustForSQL($epBonusMalus->description)."','"
                                                        .$epBonusMalus->bonusMalusType."','"
                                                        .$this->adjustForSQL($epBonusMalus->forTargetNamed)."',"
                                                        .$epBonusMalus->value.",'"
                                                        .$epBonusMalus->targetForChoice."','"
                                                        .$epBonusMalus->typeTarget."','"
                                                        .$epBonusMalus->onCost."','"
                                                        .$epBonusMalus->multi_occurence
                                                        ."')";
           if($this->mysqli->query($insertQuerry)){
               $groups = $epBonusMalus->groups;
               if($groups != null){
                    foreach($groups as $gr){

                         $insertGroupTargetQuerry = "INSERT INTO `GroupName`(`groupName`, `targetName`) VALUES ('"
                                                        .$this->adjustForSQL($gr)."','"
                                                        .$this->adjustForSQL($epBonusMalus->name)
                                                        ."')";

                        if(!$this->mysqli->query($insertGroupTargetQuerry)){
                            $this->addError("Bonnus Malus groups list ".$epBonusMalus->atomUid." and ".$gr."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                            return false;
                        }
                     }
               }
               $multiChoice = $epBonusMalus->bonusMalusTypes;
               if($multiChoice != null){
                    foreach($multiChoice as $mc){

                         $insertMultiChoiceQuerry = "INSERT INTO `BonusMalusTypes`(`bmNameMain`, `bmChoices`) VALUES ('"
                                                        .$this->adjustForSQL($epBonusMalus->name)."','"
                                                        .$this->adjustForSQL($mc->name)
                                                        ."')";

                        if(!$this->mysqli->query($insertMultiChoiceQuerry)){
                            $this->addError("Bonnus Malus multi choice list ".$epBonusMalus->atomUid." and ".$gr."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                            return false;
                        }
                     }
               }
               return true;
           }
           else {
               $this->addError("BonusMalus ".$epBonusMalus->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epBonusMalus->type." on persistBonusMalus()");
            return false;
        }
    }
    
    //====== INFO ===========
    function persistInfos($id,$data){
        $insertQuerry = "INSERT INTO `infos`(`id`, `data`) VALUES ('".$id."','".$data."')";
        if($this->mysqli->query($insertQuerry)){
            return true;
        }
        else{
           $this->addError("Info ".$id."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
           return false;
        }
    }


    //====== TRAITS ==========
    
    function persistTrait($epTrait){
        if($epTrait->type == EPAtom::$TRAIT){
            $insertQuerry = "INSERT INTO `traits`(`name`, `desc`, `side`, `onwhat`, `cpCost`, `level`,`JustFor`) VALUES ('"
                                                        .$this->adjustForSQL($epTrait->name)."','"
                                                        .$this->adjustForSQL($epTrait->description)."','"
                                                        .$epTrait->traitPosNeg."','"
                                                        .$epTrait->traitEgoMorph."',"
                                                        .$epTrait->cpCost.","
                                                        .$epTrait->level.",'"
                                                        .$epTrait->canUse
                                                        ."')";
           
           if($this->mysqli->query($insertQuerry)){
               $bmTrait = $epTrait->bonusMalus;
               foreach($bmTrait as $bmt){
                   
                   if(!$this->bonusMalusTraitCoupleAllreadyExist($bmt->name,$epTrait->name)){
                   
                        $occur = $this->occureOFBonusMalusOnList($bmTrait, $bmt->name);
                        $insertBMtraitQuerry = "INSERT INTO `TraitBonusMalus`(`traitName`, `bonusMalusName`, `occur`) VALUES ('"
                                                             .$this->adjustForSQL($epTrait->name)."','"
                                                             .$this->adjustForSQL($bmt->name)."','"
                                                             .$occur
                                                             ."')";

                        if(!$this->mysqli->query($insertBMtraitQuerry)){
                            $this->addError("Trait bonnus malus table ".$epTrait->atomUid." and ".$bmt->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                            return false;
                        }
                   }
               }
               return true;
           }
           else {
               $this->addError("Trait core ".$epTrait->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epTrait->type." on persistTraits()");
            return false;
        }
    }
    
    
    function occureOFBonusMalusOnList($bmArray,$name){
        $count  = 0;
        foreach($bmArray as $bmt){
           if($bmt->name == $name) $count++;
       }
       return $count;
    }
    
    function bonusMalusTraitCoupleAllreadyExist($bmName, $traitName){
        if($this->mysqli->real_query("SELECT `traitName`, `bonusMalusName`, `occur` FROM `TraitBonusMalus` WHERE `traitName` = '".$this->adjustForSQL($traitName)."' AND `bonusMalusName` ='".$this->adjustForSQL($bmName)."';")){
            $sqlRes = $this->mysqli->store_result();
            $res = $sqlRes->fetch_assoc();
            if(count($res) > 0) return true;
            else return false;
        }
        
    }
    
    // ==== APTITUDE ======

    function persistAptitude($epAptitude){
        if($epAptitude->type == EPAtom::$APTITUDE){
            $insertQuerry = "INSERT INTO `aptitude`(`name`, `description`, `abbreviation`) VALUES ('"
                                                .$this->adjustForSQL($epAptitude->name)."','"
                                                .$this->adjustForSQL($epAptitude->description)."','"
                                                .$epAptitude->abbreviation
                                                ."')";
           
           if($this->mysqli->query($insertQuerry)){
            $groups = $epAptitude->groups;
            if($groups  != null){
                foreach($groups as $gr){

                    $insertGroupTargetQuerry = "INSERT INTO `GroupName`(`groupName`, `targetName`) VALUES ('"
                                                   .$this->adjustForSQL($gr)."','"
                                                   .$this->adjustForSQL($epAptitude->name)
                                                   ."')";

                   if(!$this->mysqli->query($insertGroupTargetQuerry)){
                       $this->addError("Aptitude groups list ".$epAptitude->atomUid." and ".$gr."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                       return false;
                   }
                }
            }
            return true;      
           }
           else {
               $this->addError("Aptitude core ".$epAptitude->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epAptitude->type." on persistAptitude");
            return false;
        }
        
    }
    
    // === STATS =====
    
    function persistStat($epStat){
        if($epStat->type == EPAtom::$STAT){
            $insertQuerry = "INSERT INTO `stat`(`name`, `description`, `abbreviation`) VALUES ('"
                                                .$this->adjustForSQL($epStat->name)."','"
                                                .$this->adjustForSQL($epStat->description)."','"
                                                .$epStat->abbreviation
                                                ."')";
           
           if($this->mysqli->query($insertQuerry)){
            $groups = $epStat->groups;
            if($groups  != null){
                foreach($groups as $gr){

                    $insertGroupTargetQuerry = "INSERT INTO `GroupName`(`groupName`, `targetName`) VALUES ('"
                                                   .$this->adjustForSQL($gr)."','"
                                                   .$this->adjustForSQL($epStat->name)
                                                   ."')";

                   if(!$this->mysqli->query($insertGroupTargetQuerry)){
                       $this->addError("Stat groups list ".$epStat->atomUid." and ".$gr."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                       return false;
                   }
                }
            }
            return true;      
           }
           else {
               $this->addError("Stat core ".$epStat->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epStat->type." on persistStat");
            return false;
        }
    }
    
    // ====SKILL PREFIX =====
    
    function persistSkillPrefix($epPrefix,$linkedApt,$skillType,$desc){
        
        $insertQuerry = "INSERT INTO `skillPrefixes`(`prefix`, `linkedApt`, `skillType`, `desc`) VALUES ('"
                                            .$this->adjustForSQL($epPrefix)."','"
                                            .$this->adjustForSQL($linkedApt)."','"
                                            .$this->adjustForSQL($skillType)."','"
                                            .$this->adjustForSQL($desc)
                                            ."')";

       if($this->mysqli->query($insertQuerry)){
            return true;      
       }
       else {
           $this->addError("Prefix : ".$epPrefix."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
           return false;
       }
    }
    
    // ===== REPUTATION ========
    
    function persistReputation($epReputation){
        if($epReputation->type == EPAtom::$REPUTATION){
            $insertQuerry = "INSERT INTO `reputation`(`name`, `description`) VALUES ('"
                                                .$this->adjustForSQL($epReputation->name)."','"
                                                .$this->adjustForSQL($epReputation->description)
                                                ."')";
           
           if($this->mysqli->query($insertQuerry)){
            $groups = $epReputation->groups;
            if($groups  != null){
                foreach($groups as $gr){

                    $insertGroupTargetQuerry = "INSERT INTO `GroupName`(`groupName`, `targetName`) VALUES ('"
                                                   .$this->adjustForSQL($gr)."','"
                                                   .$this->adjustForSQL($epReputation->name)
                                                   ."')";

                   if(!$this->mysqli->query($insertGroupTargetQuerry)){
                       $this->addError("Reputation groups list ".$epReputation->atomUid." and ".$gr."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                       return false;
                   }
                }
            }
            return true;      
           }
           else {
               $this->addError("Reputation core ".$epReputation->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epReputation->type." on persistReputation");
            return false;
        }
    }
    
    // ===== BACKGROUND ======
    
    function persistBackground($epBackground){
         if($epBackground->type == EPAtom::$BACKGROUND){
            $insertQuerry = "INSERT INTO `background`(`name`, `description`, `type`) VALUES ('"
                                                        .$this->adjustForSQL($epBackground->name)."','"
                                                        .$this->adjustForSQL($epBackground->description)."','"
                                                        .$epBackground->backgroundType
                                                        ."')";
           
           if($this->mysqli->query($insertQuerry)){
               //--- Bonusmalus
               $bmBkg = $epBackground->bonusMalus;
               foreach($bmBkg as $bmt){
                   
                   if(!$this->bonusMalusBackgroundCoupleAllreadyExist($bmt->name,$epBackground->name)){
                   
                        $occur = $this->occureOFBonusMalusOnList($bmBkg, $bmt->name);
                        $insertBMbkgQuerry = "INSERT INTO `BackgroundBonusMalus`(`background`, `bonusMalus`, `occurrence`) VALUES ('"
                                                             .$this->adjustForSQL($epBackground->name)."','"
                                                             .$this->adjustForSQL($bmt->name)."','"                             
                                                             .$occur
                                                             ."')";

                        if(!$this->mysqli->query($insertBMbkgQuerry)){
                            $this->addError("Background bonnus malus table ".$epBackground->atomUid." and ".$bmt->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                            return false;
                        }
                   }
               }
               //--- traits
               $traitBkg = $epBackground->traits;
               foreach($traitBkg as $trait){
                   
                   
                    $insertTraitBkgQuerry = "INSERT INTO `BackgroundTrait`(`background`, `trait`) VALUES ('"
                                                         .$this->adjustForSQL($epBackground->name)."','"
                                                         .$this->adjustForSQL($trait->name)
                                                         ."')";

                    if(!$this->mysqli->query($insertTraitBkgQuerry)){
                        $this->addError("Background trait table ".$epBackground->atomUid." and ".$trait->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                        return false;
                    }
               }
               //--- limitation
               $limitationBkg = $epBackground->limitations;
               foreach($limitationBkg as $limit){
                   
                   
                    $insertLimitationBkgQuerry = "INSERT INTO `BackgroundLimitation`(`background`, `limitationGroup`) VALUES ('"
                                                         .$this->adjustForSQL($epBackground->name)."','"
                                                         .$this->adjustForSQL($limit)
                                                         ."')";

                    if(!$this->mysqli->query($insertLimitationBkgQuerry)){
                        $this->addError("Background Limitation table ".$epBackground->atomUid." and ".$limit->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                        return false;
                    }
               }
               //--- obligation
               $obligationBkg = $epBackground->obligations;
               foreach($obligationBkg as $oblig){
                   
                   
                    $insertObligationBkgQuerry = "INSERT INTO `BackgroundObligation`(`background`, `obligationGroup`) VALUES ('"
                                                         .$this->adjustForSQL($epBackground->name)."','"
                                                         .$this->adjustForSQL($oblig)
                                                         ."')";

                    if(!$this->mysqli->query($insertObligationBkgQuerry)){
                        $this->addError("Background Obligation table ".$epBackground->atomUid." and ".$oblig->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                        return false;
                    }
               }
               
               return true;  
           }
           else {
               $this->addError("Background core ".$epBackground->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epBackground->type." on persistBackground()");
            return false;
        }
    }
    
    function bonusMalusBackgroundCoupleAllreadyExist($bmName, $backgroundName){
        if($this->mysqli->real_query("SELECT `background`, `bonusMalus` FROM `BackgroundBonusMalus` WHERE `background` = '".$this->adjustForSQL($backgroundName)."' AND `bonusMalus` ='".$this->adjustForSQL($bmName)."';")){
            $sqlRes = $this->mysqli->store_result();
            $res = $sqlRes->fetch_assoc();
            if(count($res) > 0) return true;
            else return false;
        }
        
    }
    
    // ==== MORPH =====
    
    function persistMorph($epMorph){
        if($epMorph->type == EPAtom::$MORPH){
            $insertQuerry = "INSERT INTO `morph`(`name`, `description`, `type`, `gender`, `age`, `maxApptitude`, `durablility`, `cpCost`, `creditCost`) VALUES ('"
                                                        .$this->adjustForSQL($epMorph->name)."','"
                                                        .$this->adjustForSQL($epMorph->description)."','"
                                                        .$epMorph->morphType."','"
                                                        .$epMorph->gender."','"
                                                        .$epMorph->age."','"
                                                        .$epMorph->maxApptitude."','"
                                                        .$epMorph->durability."','"
                                                        .$epMorph->cpCost."','"
                                                        .$epMorph->cost
                                                        ."')";
           //error_log($insertQuerry);
           if($this->mysqli->query($insertQuerry)){
               //--- Bonusmalus
               $bmMorph = $epMorph->bonusMalus;
               foreach($bmMorph as $bmt){
                   
                   if(!$this->bonusMalusMorphCoupleAllreadyExist($bmt->name,$epMorph->name)){
                   
                        $occur = $this->occureOFBonusMalusOnList($bmMorph, $bmt->name);
                        $insertBMMorphQuerry = "INSERT INTO `MorphBonusMalus`(`morph`, `bonusMalus`, `occur`) VALUES ('"
                                                             .$this->adjustForSQL($epMorph->name)."','"
                                                             .$this->adjustForSQL($bmt->name)."','"                             
                                                             .$occur
                                                             ."')";

                        if(!$this->mysqli->query($insertBMMorphQuerry)){
                            $this->addError("Morph bonnus malus table ".$epMorph->atomUid." and ".$bmt->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                            return false;
                        }
                   }
               }
               //--- traits
               $traitMorph = $epMorph->traits;
               foreach($traitMorph as $trait){
                   
                   
                    $insertTraitMorphQuerry = "INSERT INTO `MorphTrait`(`morph`, `trait`) VALUES ('"
                                                         .$this->adjustForSQL($epMorph->name)."','"
                                                         .$this->adjustForSQL($trait->name)
                                                         ."')";

                    if(!$this->mysqli->query($insertTraitMorphQuerry)){
                        $this->addError("Morph trait table ".$epMorph->atomUid." and ".$trait->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                        return false;
                    }
               }
               //--- gear
               $gearMorph = $epMorph->gears;
               foreach($gearMorph as $gear){
                   
                   if(!$this->gearMorphCoupleAllreadyExist($gear->name,$epMorph->name)){
                   
                        $occur = $this->occureOfGearOnList($gearMorph, $gear->name);
                        $insertGearMorphQuerry = "INSERT INTO `MorphGears`(`morph`, `gear`, `occur`) VALUES ('"
                                                             .$this->adjustForSQL($epMorph->name)."','"
                                                             .$this->adjustForSQL($gear->name)."','"                             
                                                             .$occur
                                                             ."')";

                        if(!$this->mysqli->query($insertGearMorphQuerry)){
                            $this->addError("Morph Gear table ".$epMorph->atomUid." and ".$gear->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                            return false;
                        }
                   }
               }
               
               return true;  
           }
           else {
               $this->addError("Morph core ".$epMorph->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epMorph->type." on persistMorph()");
            return false;
        }
    }
    
    function bonusMalusMorphCoupleAllreadyExist($bmName, $morphName){
        if($this->mysqli->real_query("SELECT `morph`, `bonusMalus`, `occur` FROM `MorphBonusMalus` WHERE `morph` = '".$this->adjustForSQL($morphName)."' AND `bonusMalus` ='".$this->adjustForSQL($bmName)."';")){
            $sqlRes = $this->mysqli->store_result();
            $res = $sqlRes->fetch_assoc();
            if(count($res) > 0) return true;
            else return false;
        }
        
    }
    
    function gearMorphCoupleAllreadyExist($gearName, $morphName){
        if($this->mysqli->real_query("SELECT `morph`, `gear`, `occur` FROM `MorphGears` WHERE `morph` = '".$this->adjustForSQL($morphName)."' AND `gear` ='".$this->adjustForSQL($gearName)."';")){
            $sqlRes = $this->mysqli->store_result();
            $res = $sqlRes->fetch_assoc();
            if(count($res) > 0) return true;
            else return false;
        }
        
    }
    
    // ==== Ai =====
    function persistAi($epAi){
        if($epAi->type == EPAtom::$AI){
        	if(!$epAi->unique) $unik = "N";
        	else $unik = "Y";
            $insertQuerry = "INSERT INTO `ai`(`name`, `desc`, `cost`, `unique`) VALUES ('"
                                                        .$this->adjustForSQL($epAi->name)."','"
                                                        .$this->adjustForSQL($epAi->description)."',"
                                                        .$epAi->cost.",'"
                                                        .$unik
                                                        ."')";
           
           if($this->mysqli->query($insertQuerry)){
               $aptGear = $epAi->aptitudes;
               foreach($aptGear as $apt){
                   
                    $insertAptQuerry = "INSERT INTO `AiAptitude`(`ai`, `aptitude`, `value`) VALUES ('"
                                                         .$this->adjustForSQL($epAi->name)."','"
                                                         .$this->adjustForSQL($apt->name)."',"
                                                         .$this->adjustForSQL($apt->value)
                                                         .")";

                    if(!$this->mysqli->query($insertAptQuerry)){
                        $this->addError("Ai Aptitude table ".$epAi->atomUid." and ".$apt->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                        return false;
                    }
               }
               
               $statGear = $epAi->stats;
               foreach($statGear as $stat){
                   
                    $insertStatQuerry = "INSERT INTO `AiStat`(`ai`, `stat`, `value`) VALUES ('"
                                                         .$this->adjustForSQL($epAi->name)."','"
                                                         .$this->adjustForSQL($stat->name)."',"
                                                         .$this->adjustForSQL($stat->value)
                                                         .")";

                    if(!$this->mysqli->query($insertStatQuerry)){
                        $this->addError("Ai Stat table ".$epAi->atomUid." and ".$stat->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                        return false;
                    }
               }
               
               $skillGear = $epAi->skills;
               foreach($skillGear as $skill){
                   
                    $insertSkillQuerry = "INSERT INTO `AiSkill`(`ai`, `skillName`, `skillPrefix`, `value`)  VALUES ('"
                                                         .$this->adjustForSQL($epAi->name)."','"
                                                         .$this->adjustForSQL($skill->name)."','" 
                                                         .$this->adjustForSQL($skill->prefix)."',"
                                                         .$this->adjustForSQL($skill->baseValue)
                                                         .")";

                    if(!$this->mysqli->query($insertSkillQuerry)){
                        $this->addError("Ai Skill table ".$epAi->atomUid." and ".$skill->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                        return false;
                    }
               }
               
               return true;
           }
           else {
               $this->addError("Ai core ".$epAi->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epAi->type." on persistAi()");
            return false;
        }
    }
    
    // ==== GEAR =====
    
    function persistGear($epGear){
         if($epGear->type == EPAtom::$GEAR){
         	if(!$epGear->unique) $uni = "N";
         	else $uni = "Y";
            $insertQuerry = "INSERT INTO `Gear`(`name`, `description`,  `type`, `cost`, `armorKinetic`, `armorEnergy`, `degat`, `armorPene`,`JustFor`, `unique`) VALUES ('"
                                                        .$this->adjustForSQL($epGear->name)."','"
                                                        .$this->adjustForSQL($epGear->description)."','"
                                                        .$epGear->gearType."',"                    
                                                        .$epGear->cost.","
                                                        .$epGear->armorKinetic.","
                                                        .$epGear->armorEnergy.",'"
                                                        .$this->adjustForSQL($epGear->degat)."',"
                                                        .$epGear->armorPenetration.",'"
                                                        .$epGear->gearRestriction."','"
                                                        .$uni
                                                        ."')";
           
           if($this->mysqli->query($insertQuerry)){
               $bmGear = $epGear->bonusMalus;
               foreach($bmGear as $bmg){
                   
                   if(!$this->bonusMalusGearCoupleAllreadyExist($bmg->name,$epGear->name)){
                   
                        $occur = $this->occureOFBonusMalusOnList($bmGear, $bmg->name);
                        $insertBMGearQuerry = "INSERT INTO `GearBonusMalus`(`gear`, `bonusMalus`, `occur`) VALUES ('"
                                                             .$this->adjustForSQL($epGear->name)."','"
                                                             .$this->adjustForSQL($bmg->name)."','"
                                                             .$occur
                                                             ."')";

                        if(!$this->mysqli->query($insertBMGearQuerry)){
                            $this->addError("Gear bonnus malus table ".$bmGear->atomUid." and ".$bmg->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                            return false;
                        }
                   }
               }
               return true;
           }
           else {
               $this->addError("Grear core ".$epGear->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epGear->type." on persistGear()");
            return false;
        }
    }
    
    
    function bonusMalusGearCoupleAllreadyExist($bmName, $gearName){
        if($this->mysqli->real_query("SELECT `gear`, `bonusMalus`, `occur` FROM `GearBonusMalus` WHERE `gear` = '".$this->adjustForSQL($gearName)."' AND `bonusMalus` ='".$this->adjustForSQL($bmName)."';")){
            $sqlRes = $this->mysqli->store_result();
            $res = $sqlRes->fetch_assoc();
            if(count($res) > 0) return true;
            else return false;
        }
        
    }
    
    function occureOfGearOnList($gearArray,$name){
        $count  = 0;
        foreach($gearArray as $bmt){
           if($bmt->name == $name) $count++;
       }
       return $count;
    }
    
    // ==== PSY SLEIGHT =====
    
    function persistPsySleight($epPsyS){
        if($epPsyS->type == EPAtom::$PSY){
            $insertQuerry = "INSERT INTO `psySleight`(`name`, `desc`, `type`, `range`, `duration`, `action`, `strainMod`, `level`,`skillNeeded`) VALUES ('"
                                                        .$this->adjustForSQL($epPsyS->name)."','"
                                                        .$this->adjustForSQL($epPsyS->description)."','"
                                                        .$epPsyS->psyType."','"                    
                                                        .$epPsyS->range."','"
                                                        .$epPsyS->duration."','"
                                                        .$epPsyS->action."','"
                                                        .$epPsyS->strainMod."','"
                                                        .$epPsyS->psyLevel."','"
                                                        .$epPsyS->skillNeeded
                                                        ."')";
           
           if($this->mysqli->query($insertQuerry)){
               $bmPsy = $epPsyS->bonusMalus;
               foreach($bmPsy as $bmp){
                   
                   if(!$this->bonusMalusPsyCoupleAllreadyExist($bmp->name,$epPsyS->name)){
                   
                        $occur = $this->occureOFBonusMalusOnList($bmPsy, $bmp->name);
                        $insertBMGearQuerry = "INSERT INTO `PsySleightBonusMalus`(`psy`, `bonusmalus`, `occur`) VALUES ('"
                                                             .$this->adjustForSQL($epPsyS->name)."','"
                                                             .$this->adjustForSQL($bmp->name)."','"
                                                             .$occur
                                                             ."')";

                        if(!$this->mysqli->query($insertBMGearQuerry)){
                            $this->addError("PsySleight bonnus malus table ".$bmPsy->atomUid." and ".$bmp->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
                            return false;
                        }
                   }
               }
               return true;
           }
           else {
               $this->addError("PsySleight core ".$epPsyS->atomUid."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
        }
        else{
            $this->addError("Try to insert a ".$epPsyS->type." on persistPsySleight()");
            return false;
        }
    }
    
    function bonusMalusPsyCoupleAllreadyExist($bmName, $psyName){
        if($this->mysqli->real_query("SELECT `psy`, `bonusmalus`, `occur` FROM `PsySleightBonusMalus` WHERE `psy` = '".$this->adjustForSQL($psyName)."' AND `bonusMalus` ='".$this->adjustForSQL($bmName)."';")){
            $sqlRes = $this->mysqli->store_result();
            $res = $sqlRes->fetch_assoc();
            if(count($res) > 0) return true;
            else return false;
        }
        
    }
    
    function occureOfPsyOnList($psyArray,$name){
        $count  = 0;
        foreach($psyArray as $bmt){
           if($bmt->name == $name) $count++;
       }
       return $count;
    }
    
    //Book
    function persistAtomeBook($name,$book){
	     $insertQuerry = "INSERT INTO `AtomBook`(`name`, `book`) VALUES ('".$this->adjustForSQL($name)."','".$this->adjustForSQL($book)."')";
		 if($this->mysqli->query($insertQuerry)){
	        return true;
		 }
		 else{
	       $this->addError("Atome Book ".$name."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
	       return false;
	     }
     }
    
    
    //Page
    function persistAtomePage($name,$page){
	     $insertQuerry = "INSERT INTO `AtomPage`(`name`, `page`) VALUES ('".$this->adjustForSQL($name)."','".$this->adjustForSQL($page)."')";
		 if($this->mysqli->query($insertQuerry)){
	        return true;
		 }
		 else{
	       $this->addError("Atome Page ".$name."  persistance failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
	       return false;
	     }
     }
    
    //Delete functions =========================================================
    
    function deleteEntryFromTable($tableName,$entryKeyColumnName,$entryKey){
        
         $deleteQuerry = "DELETE FROM `".$tableName."` WHERE `".$entryKeyColumnName."` = '".$this->adjustForSQL($entryKey)."'";
                    
           if($this->mysqli->query($deleteQuerry)){
               return true;
           }
           else {
               $this->addError("Delete error on ".$tableName." for ".$entryKey."  DELETE failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
               return false;
           }
    }
    
    function deleteAptitude($epAptitudeName){
            
        if($this->deleteEntryFromTable("aptitude", "name", $epAptitudeName)){
        
            if(!$this->deleteEntryFromTable("GroupName", "targetName", $epAptitudeName)){
                       return false;
            }
        
            return true;
        }
        else{
             return false;
        }
    }
    
    function deleteBackground($epBackgroundName){
            
        if($this->deleteEntryFromTable("background", "name", $epBackgroundName)){
         
            if(!$this->deleteEntryFromTable("BackgroundBonusMalus", "background", $epBackgroundName)){
                  return false;
            }
            
            if(!$this->deleteEntryFromTable("BackgroundLimitation", "background", $epBackgroundName)){
                  return false;
            }
            
            if(!$this->deleteEntryFromTable("BackgroundTrait", "background", $epBackgroundName)){
                  return false;
            }
            
            if(!$this->deleteEntryFromTable("BackgroundObligation", "background", $epBackgroundName)){
                  return false;
            }
            
            return true;
        }
        else{
             return false;
        }
    }
    
    
    function deleteBonusMalus($epBonusMalusName){
        if($this->deleteEntryFromTable("bonusMalus", "name", $epBonusMalusName)){
         
            if(!$this->deleteEntryFromTable("GroupName", "targetName", $epBonusMalusName)){
                  return false;
            }
            return true;
        }
        else{
             return false;
        }
    }
    
    function deleteGear($epGearName){
        if($this->deleteEntryFromTable("Gear", "name", $epGearName)){
         
            if(!$this->deleteEntryFromTable("GearBonusMalus", "gear", $epGearName)){
                  return false;
            }
            return true;
        }
        else{
             return false;
        }
    }
    
    function deleteMorph($epMorphName){
        if($this->deleteEntryFromTable("morph", "name", $epMorphName)){
         
            if(!$this->deleteEntryFromTable("MorphBonusMalus", "morph", $epMorphName)){
                  return false;
            }
            
            if(!$this->deleteEntryFromTable("MorphGears", "morph", $epMorphName)){
                  return false;
            }
            
            if(!$this->deleteEntryFromTable("MorphTrait", "morph", $epMorphName)){
                  return false;
            }
            
            return true;
        }
        else{
             return false;
        }
    }
    
    function deleteReputation($epReputationName){
        if($this->deleteEntryFromTable("reputation", "name", $epReputationName)){
         
            if(!$this->deleteEntryFromTable("GroupName", "targetName", $epReputationName)){
                  return false;
            }
            return true;
        }
        else{
             return false;
        }
    }
    
    function deleteSkill($epSkillName){
        if($this->deleteEntryFromTable("skills", "name", $epSkillName)){
         
            if(!$this->deleteEntryFromTable("GroupName", "targetName", $epSkillName)){
                  return false;
            }
            return true;
        }
        else{
             return false;
        }
    }
    
    function deleteSkillPrefix($epSkillPrefixName){
        
        return $this->deleteEntryFromTable("skillPrefixes", "prefix", $epSkillPrefixName);
           
    }
    
    function deleteStat($epStatName){
        if($this->deleteEntryFromTable("stat", "name", $epStatName)){
         
            if(!$this->deleteEntryFromTable("GroupName", "targetName", $epStatName)){
                  return false;
            }
            return true;
        }
        else{
             return false;
        }
    }
    
    function deleteTrait($epTraitName){
        if($this->deleteEntryFromTable("traits", "name", $epTraitName)){
         
            if(!$this->deleteEntryFromTable("TraitBonusMalus", "traitName", $epTraitName)){
                  return false;
            }
            return true;
        }
        else{
             return false;
        }
    }
    
    function deleteAi($epAiName){
        if($this->deleteEntryFromTable("ai", "name", $epAiName)){
         
            if(!$this->deleteEntryFromTable("AiAptitude", "ai", $epAiName)){
                  return false;
            }
            
            if(!$this->deleteEntryFromTable("AiSkill", "ai", $epAiName)){
                  return false;
            }
            
            if(!$this->deleteEntryFromTable("AiStat", "ai", $epAiName)){
                  return false;
            }
            
            return true;
        }
        else{
             return false;
        }
    }
    
    function deletePsy($epPsyName){
        if($this->deleteEntryFromTable("psySleight", "name", $epPsyName)){
         
            if(!$this->deleteEntryFromTable("PsySleightBonusMalus", "psy", $epPsyName)){
                  return false;
            }
            return true;
        }
        else{
             return false;
        }
    }
    
  //---------------------Helper functions---------------------------------------
  function adjustForSQL($string){
    //$candidat = "";
    $candidat = str_replace("'", "''", $string);
    return $candidat;
  }  
    
}
?>
