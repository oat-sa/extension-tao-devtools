<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 * @license GPLv2
 * @package taoDevTools
 *
 */
namespace oat\taoDevTools\helper;

class NameGenerator
{
    private static $nouns = array("adventure","amazement","anger","anxiety","apprehension","artistry","arrogance","awe","beauty","belief","bravery","brutality","calm","cactus","chaos","charity","childhood","clarity","coldness","comfort","communication","compassion","confidence","contentment",
        "courage","crime","curiosity","death","deceit","dedication","defeat","delight","democracy","despair","determination","dexterity","dictatorship","disappointment","disbelief","disquiet","disturbance","education","ego","elegance","energy","enhancement","enthusiasm","envy","evil","excitement","failure",
        "faith","faithfulness","faithlessness","fascination","favouritism","fear","forgiveness","fragility","frailty","freedom","friendship","generosity","goodness","gossip","grace","grief","happiness","hate","hatred","hearsay","helpfulness","helplessness","homelessness","honesty","honour","hope","humility",
        "humour","hurt","idea","idiosyncrasy","imagination","impression","improvement","infatuation","inflation","insanity","intelligence","jealousy","joy","justice","kindness","knowledge","laughter","law","liberty","life","loss","love","loyalty","luck","luxury","man","maturity","memory","mercy","motivation",
        "movement","music","need","omen","opinion","opportunism","opportunity","pain","patience","peace","peculiarity","perseverance","pleasure","poverty","power","pride","principle","reality","redemption","refreshment","relaxation","relief","restoration","riches","romance","rumour","sacrifice","sadness",
        "sanity","satisfaction","self-control","sensitivity","service","shock","silliness","skill","slavery","sleep","sophistication","sorrow","sparkle","speculation","speed","strength","strictness","stupidity","submission","success","surprise","sympathy","talent","thrill","tiredness","tolerance","trust",
        "uncertainty","unemployment","unreality","victory","wariness","warmth","weakness","wealth","weariness","wisdom","wit","worry");
    
    private static $adjectives = array("different","used","important","every","large","available","popular","able","basic","known","various","difficult","several","united","historical","hot","useful","mental","scared","additional","emotional","old","political","similar","healthy","financial","medical",
        "traditional","federal","entire","strong","actual","significant","successful","electrical","expensive","pregnant","intelligent","interesting","poor","happy","responsible","cute","helpful","recent","willing","nice","wonderful","impossible","serious","huge","rare","technical","typical","competitive",
        "critical","electronic","immediate","aware","educational","environmental","global","legal","relevant","accurate","capable","dangerous","dramatic","efficient","powerful","foreign","hungry","practical","psychological","severe","suitable","numerous","sufficient","unusual","consistent","cultural",
        "existing","famous","pure","obvious","careful","latter","obviously","unhappy","acceptable","aggressive","distinct","eastern","logical","reasonable","strict","successfully","administrative","automatic","civil","former","massive","southern","unfair","visible","alive","angry","desperate",
        "exciting","friendly","lucky","realistic","sorry","ugly","unlikely","anxious","comprehensive","curious","impressive","informal","inner","pleasant","insufferable",
        
        "first", "second", "third", "fourth", "fifth", "sixth", "seventh", "eighth", "ninth", "tenth","eleventh","twelfth","thirteenth","fourteenth","fifteenth","sixteenth","seventeenth","eighteenth","nineteenth","twentieth");
    
    protected static function getRandomNoun($excluded = null) {
        do {
            $cand = self::$nouns[rand(0, count(self::$nouns) - 1)];
        } while (!is_null($excluded) && $cand === $excluded);
        
        return $cand; 
    }
    
    protected static function getRandomAdj() {
        return self::$adjectives[rand(0, count(self::$adjectives) - 1)];
    }
    
    /**
     * Generates a random title for a resource 
     * 
     * @return string
     */
    public static function generateTitle() {
        $first = self::getRandomNoun();
        $second = self::getRandomNoun($first);
        
        switch (rand(0, 6)) {
        	case 0:
                return 'The '.$first.' of '.self::getRandomAdj().' '.$second;
        	case 1:
        	    return ucfirst($first).' and '.ucfirst($second);
        	case 2:
        	    return 'The '.self::getRandomAdj().' '.$second;
    	    case 3:
    	        return 'A '.self::getRandomAdj().' '.$second;
        	case 4:
        	    return 'The '.$first.' in '.$second;
    	    case 5:
    	        return 'Of '.$first.' and '.$second;
    	    case 6:
    	        return ucfirst(self::getRandomAdj()).' '.ucfirst($second);
        	         
        }
    }

    /**
     * Generates a string composed of random characters
     * 
     * @param number $length
     * @return string
     */
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
}
