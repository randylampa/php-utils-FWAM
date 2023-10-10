<?php

namespace TgUtils;

/**
 * Provides an obfuscation possibility for websites using Javascript.
 * <p>The method is similar to rot13 but uses a random char mapping. Please notice
 *    that not all characters are enabled by default. However, you can provide
 *    your own character set in case it is not sufficient.</p>
 *    
 * @author ralph
 *        
 */
class Obfuscation {

    /** The default character set to be used for obfuscation. */
    const DEFAULT_CHAR_SET = ' ()+-./0123456789:@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';
    
    /**
     * Obfuscates an email address along with its link (&lt;a href="mailto:..."&gt;).
     * @param string $email   - the email address to be obfuscated
     * @param string $id      - the ID of the tag that shall be replaced (optional, default will generate a random ID and return the span-tag along with the javascript.
     * @param string $charSet - the character set that will be used. It shall include all characters of the email (optional)
     * @return string the span-tag where obfuscation is displayed (if $id is NULL) and the javascript to replace it.
     */
    public static function obfuscateEmail($email, $id = NULL, $charSet = Obfuscation::DEFAULT_CHAR_SET) {
        $obfuscated = self::createObfuscatedInfo($email, $charSet);
        if (is_array($obfuscated)) {
            $a = $obfuscated[0];
            $e = $obfuscated[1];
            $script = '<script type="text/javascript">var a="'.$a.'";var b=a.split("").sort().join("");var c="'.$e.'";var d="";for(var e=0;e<c.length;e++)d+=b.charAt(a.indexOf(c.charAt(e)));jQuery("#'.$id.'").html("<a href=\"mailto:"+d+"\">"+d+"</a>")</script>';
            if ($id == NULL) {
                $id = self::generateObfuscationId();
                return self::getObfuscatedHtmlSpan($id).$script;
            }
            return $script;
        }
        return '';
    }

    /**
     * Obfuscates a simple text, e.g. phone numbers for display.
     * @param string $text   - the text to be obfuscated
     * @param string $id      - the ID of the tag that shall be replaced (optional, default will generate a random ID and return the span-tag along with the javascript.
     * @param string $charSet - the character set that will be used. It shall include all characters of the text (optional)
     * @return string the span-tag where obfuscation is displayed (if $id is NULL) and the javascript to replace it.
     */
    public static function obfuscateText($text, $id = NULL, $charSet = Obfuscation::DEFAULT_CHAR_SET) {
        $obfuscated = self::createObfuscatedInfo($text, $charSet);
        if (is_array($obfuscated)) {
            $a = $obfuscated[0];
            $e = $obfuscated[1];
            $script = '<script type="text/javascript">var a="'.$a.'";var b=a.split("").sort().join("");var c="'.$e.'";var d="";for(var e=0;e<c.length;e++)d+=b.charAt(a.indexOf(c.charAt(e)));jQuery("#'.$id.'").html(d)</script>';
            if ($id == NULL) {
                $id = self::generateObfuscationId();
                return self::getObfuscatedHtmlSpan($id).$script;
            }
            return $script;
        }
        return '';
    }

    /**
     * Return the HTML span tag to be required where obfuscated text can be displayed.
     * @param string $id - the ID of the span tag
     * @param string the HTML span tag
     */
    public static function getObfuscatedHtmlSpan($id) {
        return '<span id="'.$id.'">[javascript protected]</span>';
    }

    /**
     * Creates the required obfuscation information out of plain text and the character set.
     * @param string $text   - the plain text to be obfuscated
     * @param string $charSet - the character set that will be used. It shall include all characters of the plain text (optional)
     * @return array with two values - 0: the random character map, 1: the obfusacted text 
     */
    protected static function createObfuscatedInfo($text, $charSet = Obfuscation::DEFAULT_CHAR_SET) {
        if (trim($text) != '') {
            $a = self::generateRandomOrder($charSet);

            // Encode the text
            $encrypted = '';
            for ($i=0; $i<strlen($text); $i++) {
                $char = substr($text, $i, 1);
                $posB = strpos($charSet, $char);
                $encC = substr($a, $posB, 1);
                $encrypted .= $encC;
            }
            return array($a, $encrypted);
        }
        return '';
    }

    /**
     * Generated a random obfuscation ID - that is the ID of the tag where obfuscation takes place.
     * @return string the ID of the tag
     */
    public static function generateObfuscationId() {
        return Utils::generateRandomString(15, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    /**
     * Returns the given string in a random order.
     * <p>This method is used to create the random character map.</p>
     * @param string $s - the set of characters that shall be randomized
     * @param string the randomized character map.
     */
    public static function generateRandomOrder($s) {
        $rc   = '';
        $sLen = strlen($s);
        while (strlen($rc) < $sLen) {
            $i = rand(0, $sLen - 1);
            $char = substr($s, $i, 1);
            if (strpos($rc, $char) === FALSE) {
                $rc .= $char;
            }
        }
        return $rc;
    }
    
}

