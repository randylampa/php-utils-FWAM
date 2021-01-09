<?php

namespace TgUtils;

class Utils {

    /**
     * Anonymizes a string.
     * <p>This function replaces most
     * characters with an asterisk (*). Leading and
     * trailing characters will be kept if possible
     * to allow an identification when the real value
     * is known to the reade.
     * <p>This method can be used for log messages</p>.
     * @param string $s - the string to anonymize
     * @return string the anonymized string
     */
    public static function anonymize($s) {
        $rc = '';
        for ($i = 0; $i < strlen($s); $i ++) {
            $c = substr($s, $i, 1);
            if (($i == 0) || ($i == strlen($s) - 1)) $rc .= $c;
            else if (ctype_alpha($c) || ctype_digit($c)) $rc .= '*';
            else $rc .= $c;
        }
        return $rc;
    }

    /**
     * Sort an array of object by one of their attributes.
     * @param array $array - the array of objects to sort
     * @param string $attribute - the attribute to be sorted upon
     * @param boolean $reverse - sort in reversed order (optional, default is FALSE).
     * @param boolean $ignoreCase - sort by ignoring upper/lower cases (optional, default is FALSE).
     */
    public static function sort(&$array, $attribute, $reverse = FALSE, $ignoreCase = FALSE) {
        usort($array, function ($a, $b) {
            $rc = $ignoreCase ? strcmp(strtoupper($a->$attribute), strtoupper($b->$attribute)) : strcmp($a->$attribute, $b->$attribute);
            if ($reverse) {
                $rc = 0-$reverse;
            }
            return $rc;
        });
    }

    /**
     * Extract object attributes from a list and return them as a new list.
     * @param array $array - array of objects
     * @param string $attr - the attribute name to be extracted
     * @return array list of values of this attribute from the objects.
     */
    public static function extractAttributeFromList($array, $attr) {
        $rc = array();
        foreach ($array as $o) {
            $rc[] = $o->$attr;
        }
        return $rc;
    }

    /**
     * Generate a random string.
     * @param int $length - length of string to generated (optional, default is 10).
     * @param string $chars - allowed characters (optional, default is alphanumeric class [0-9A-Za-z]).
     * @return string a string with given length containing allowed characters only.
     */
    public static function generateRandomString($length = 10, $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        $charsLen = strlen($chars);
        $rc = '';
        for ($i = 0; $i < $length; $i ++) {
            $rc .= $chars[rand(0, $charsLen - 1)];
        }
        return $rc;
    }
    
    /**
     * Find first object in list that has a given value as attribute uid.
     * @param array $list - list of objects
     * @param mixed $uid - UID value to search for. 
     * @return object Object whose attribute uid is of given value.
     */
    public static function findByUid($list, $uid) {
		return self::findBy($list, 'uid', $uid);
	}

    /**
     * Find first object in list that has a given value in an attribute.
     * @param array $list - list of objects
     * @param string $attr - name of attribute. 
     * @param mixed $value - value to search for. 
     * @return object Object whose attribute $attr is of given value $value.
     */
	public static function findBy($list, $attr, $value) {
		$rc = null;
		if (is_array($list)) {
			foreach ($list AS $obj) {
				if ($obj->$attr == $value) {
					$rc = $obj;
					break;
				}
			}
		}
		return $rc;
	}

	/**
	 * Test whether the given string is NULL or empty after trimming.
	 * @param mixed $s - string or NULL to be tested
	 * @return boolean TRUE when string is empty
	 */
	public static function isEmpty($s) {
		if ($s == NULL) return TRUE;
		return strlen(trim($s)) == 0;
	}

}
