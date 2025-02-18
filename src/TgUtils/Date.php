<?php
/**
 * @license     GNU General Public License version 3 or later; see LICENSE
 * 
 * This class was slightly enhanced by the author from the original source 
 * "Open Source Matters".
 */

namespace TgUtils;

use \TgI18n\I18N;

I18N::addI18nFile(__DIR__.'/../date_i18n.php', FALSE);

/**
 * Date is a class that stores a date and provides logic to manipulate
 * and render that date in a variety of formats. 
 *
 * @property-read  string   $daysinmonth   t - Number of days in the given month.
 * @property-read  string   $dayofweek     N - ISO-8601 numeric representation of the day of the week.
 * @property-read  string   $dayofyear     z - The day of the year (starting from 0).
 * @property-read  boolean  $isleapyear    L - Whether it's a leap year.
 * @property-read  string   $day           d - Day of the month, 2 digits with leading zeros.
 * @property-read  string   $hour          H - 24-hour format of an hour with leading zeros.
 * @property-read  string   $minute        i - Minutes with leading zeros.
 * @property-read  string   $second        s - Seconds with leading zeros.
 * @property-read  string   $month         m - Numeric representation of a month, with leading zeros.
 * @property-read  string   $ordinal       S - English ordinal suffix for the day of the month, 2 characters.
 * @property-read  string   $week          W - Numeric representation of the day of the week.
 * @property-read  string   $year          Y - A full numeric representation of a year, 4 digits.
 *
 */
class Date extends \DateTime {

	const DAY_ABBR = "\x021\x03";
	const DAY_NAME = "\x022\x03";
	const MONTH_ABBR = "\x023\x03";
	const MONTH_NAME = "\x024\x03";

	/** Seconds per minute */
	const SECONDS_PER_MINUTE = 60;
	/** Seconds per hour */
	const SECONDS_PER_HOUR   = 3600;
	/** Seconds per day */
	const SECONDS_PER_DAY    = 86400;
	/** Seconds per week */
	const SECONDS_PER_WEEK   = 604800;
	
	/**
	 * The format string to be applied when using the __toString() magic method.
	 *
	 * @var    string
	 */
	public static $format = 'Y-m-d H:i:s';

	/**
	 * Placeholder for a DateTimeZone object with GMT as the time zone.
	 *
	 * @var    object
	 */
	protected static $gmt;

	/**
	 * Placeholder for a DateTimeZone object with the default server
	 * time zone as the time zone.
	 *
	 * @var    object
	 */
	protected static $stz;

	/**
	 * The DateTimeZone object for usage in rending dates as strings.
	 *
	 * @var    \DateTimeZone
	 */
	protected $tz;

	/**
	 * Constructor.
	 *
	 * @param   string  $date  String in a format accepted by strtotime(), defaults to "now".
	 * @param   mixed   $tz    Time zone to be used for the date. Might be a string or a DateTimeZone object.
	 *
	 */
	public function __construct($date = 'now', $tz = null) {
		// Create the base GMT and server time zone objects.
		if (empty(self::$gmt) || empty(self::$stz)) {
			self::$gmt = new \DateTimeZone('GMT');
			self::$stz = new \DateTimeZone(@date_default_timezone_get());
		}

		// If the time zone object is not set, attempt to build it.
		if (!($tz instanceof \DateTimeZone)) {
			if ($tz === null) {
				$tz = self::$gmt;
			} elseif (is_string($tz)) {
				$tz = new \DateTimeZone($tz);
			}
		}

		// If the date is numeric assume a unix timestamp and convert it.
		date_default_timezone_set('UTC');
		if (is_numeric($date)) {
			$date = date('c', $date);
			parent::__construct($date);
			$this->setTimezone($tz);
		} else {
			// Call the DateTime constructor.
			parent::__construct($date, $tz);
		}

		// Reset the timezone for 3rd party libraries/extension that does not use Date
		date_default_timezone_set(self::$stz->getName());

		// Set the timezone object for access later.
		$this->tz = $tz;
	}

	/**
	 * Magic method to access properties of the date given by class to the format method.
	 *
	 * @param   string  $name  The name of the property.
	 *
	 * @return  mixed   A value if the property name is valid, null otherwise.
	 *
	 */
	public function __get($name) {
		$value = null;

		switch ($name) {
			case 'daysinmonth':
				$value = $this->format('t', true);
				break;

			case 'dayofweek':
				$value = $this->format('N', true);
				break;

			case 'dayofyear':
				$value = $this->format('z', true);
				break;

			case 'isleapyear':
				$value = (boolean) $this->format('L', true);
				break;

			case 'day':
				$value = $this->format('d', true);
				break;

			case 'hour':
				$value = $this->format('H', true);
				break;

			case 'minute':
				$value = $this->format('i', true);
				break;

			case 'second':
				$value = $this->format('s', true);
				break;

			case 'month':
				$value = $this->format('m', true);
				break;

			case 'ordinal':
				$value = $this->format('S', true);
				break;

			case 'week':
				$value = $this->format('W', true);
				break;

			case 'year':
				$value = $this->format('Y', true);
				break;

			default:
				$trace = debug_backtrace();
				trigger_error(
					'Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'],
					E_USER_NOTICE
				);
		}

		return $value;
	}

	/**
	 * Magic method to render the date object in the format specified in the public
	 * static member Date::$format.
	 *
	 * @return  string  The date as a formatted string.
	 *
	 */
	public function __toString() {
		return (string) parent::format(self::$format);
	}

	/**
	 * Proxy for new Date().
	 *
	 * @param   string  $date  String in a format accepted by strtotime(), defaults to "now".
	 * @param   mixed   $tz    Time zone to be used for the date.
	 *
	 * @return  Date
	 *
	 */
	public static function getInstance($date = 'now', $tz = null) {
		return new Date($date, $tz);
	}

	/**
	 * Translates day of week number to a string.
	 *
	 * @param   integer  $day   The numeric day of the week.
	 * @param   boolean  $abbr  Return the abbreviated day string?
	 *
	 * @return  string  The day of the week.
	 *
	 */
	public function dayToString($day, $abbr = false, $language = null) {
		switch ($day) {
			case 0:
				return $abbr ? I18N::_('date_sunday_short', $language)    : I18N::_('date_sunday', $language);
			case 1:
				return $abbr ? I18N::_('date_monday_short', $language)    : I18N::_('date_monday', $language);
			case 2:
				return $abbr ? I18N::_('date_tuesday_short', $language)   : I18N::_('date_tuesday', $language);
			case 3:
				return $abbr ? I18N::_('date_wednesday_short', $language) : I18N::_('date_wednesday', $language);
			case 4:
				return $abbr ? I18N::_('date_thursday_short', $language)  : I18N::_('date_thursday', $language);
			case 5:
				return $abbr ? I18N::_('date_friday_short', $language)    : I18N::_('date_friday', $language);
			case 6:
				return $abbr ? I18N::_('date_saturday_short', $language)  : I18N::_('date_saturday', $language);
		}
	}

	/**
	 * Gets the date as a formatted string in a local calendar.
	 *
	 * @param   string   $format     The date format specification string (see {@link PHP_MANUAL#date})
	 * @param   boolean  $local      True to return the date string in the local time zone, false to return it in GMT.
	 * @param   boolean  $translate  True to translate localised strings
	 *
	 * @return  string   The date string in the specified format format.
	 *
	 */
	public function calendar($format, $local = false, $translate = true) {
		return $this->format($format, $local, $translate);
	}

	/**
	 * Gets the date as a formatted string.
	 *
	 * @param   string   $format     The date format specification string (see {@link PHP_MANUAL#date})
	 * @param   boolean  $local      True to return the date string in the local time zone, false to return it in GMT.
	 * @param   boolean  $translate  True to translate localised strings
	 *
	 * @return  string   The date string in the specified format format.
	 *
	 */
	public function format($format, $local = false, $translate = true, $language = null):string {
		if ($translate) {
			// Do string replacements for date format options that can be translated.
			$format = preg_replace('/(^|[^\\\])D/', "\\1" . self::DAY_ABBR, $format);
			$format = preg_replace('/(^|[^\\\])l/', "\\1" . self::DAY_NAME, $format);
			$format = preg_replace('/(^|[^\\\])M/', "\\1" . self::MONTH_ABBR, $format);
			$format = preg_replace('/(^|[^\\\])F/', "\\1" . self::MONTH_NAME, $format);
		}

		// If the returned time should not be local use GMT.
		if ($local == false) {
			parent::setTimezone(self::$gmt);
		}

		// Format the date.
		$return = parent::format($format);

		if ($translate) {
			// Manually modify the month and day strings in the formatted time.
			if (strpos($return, self::DAY_ABBR) !== false) {
				$return = str_replace(self::DAY_ABBR, $this->dayToString(parent::format('w'), true, $language), $return);
			}

			if (strpos($return, self::DAY_NAME) !== false) {
				$return = str_replace(self::DAY_NAME, $this->dayToString(parent::format('w'), false, $language), $return);
			}

			if (strpos($return, self::MONTH_ABBR) !== false) {
				$return = str_replace(self::MONTH_ABBR, $this->monthToString(parent::format('n'), true, $language), $return);
			}

			if (strpos($return, self::MONTH_NAME) !== false) {
				$return = str_replace(self::MONTH_NAME, $this->monthToString(parent::format('n'), false, $language), $return);
			}
		}

		if ($local == false) {
			parent::setTimezone($this->tz);
		}

		return $return;
	}

	/**
	 * Get the time offset from GMT in hours or seconds.
	 *
	 * @param   boolean  $hours  True to return the value in hours.
	 *
	 * @return  float  The time offset from GMT either in hours or in seconds.
	 *
	 */
	public function getOffsetFromGMT($hours = false) {
		return (float) $hours ? ($this->tz->getOffset($this) / 3600) : $this->tz->getOffset($this);
	}

	/**
	 * Translates month number to a string.
	 *
	 * @param   integer  $month  The numeric month of the year.
	 * @param   boolean  $abbr   If true, return the abbreviated month string
	 *
	 * @return  string  The month of the year.
	 *
	 */
	public function monthToString($month, $abbr = false, $language = null) {
		switch ($month)
		{
			case 1:
				return $abbr ? I18N::_('date_january_short', $language) : I18N::_('date_january', $language);
			case 2:
				return $abbr ? I18N::_('date_february_short', $language) : I18N::_('date_february', $language);
			case 3:
				return $abbr ? I18N::_('date_march_short', $language) : I18N::_('date_march', $language);
			case 4:
				return $abbr ? I18N::_('date_april_short', $language) : I18N::_('date_april', $language);
			case 5:
				return $abbr ? I18N::_('date_may_short', $language) : I18N::_('date_may', $language);
			case 6:
				return $abbr ? I18N::_('date_june_short', $language) : I18N::_('date_june', $language);
			case 7:
				return $abbr ? I18N::_('date_july_short', $language) : I18N::_('date_july', $language);
			case 8:
				return $abbr ? I18N::_('date_august_short', $language) : I18N::_('date_august', $language);
			case 9:
				return $abbr ? I18N::_('date_september_short', $language) : I18N::_('date_september', $language);
			case 10:
				return $abbr ? I18N::_('date_october_short', $language) : I18N::_('date_october', $language);
			case 11:
				return $abbr ? I18N::_('date_november_short', $language) : I18N::_('date_november', $language);
			case 12:
				return $abbr ? I18N::_('date_december_short', $language) : I18N::_('date_december', $language);
		}
	}

	/**
	 * Method to wrap the setTimezone() function and set the internal time zone object.
	 *
	 * @param   \DateTimeZone  $tz  The new DateTimeZone object.
	 *
	 * @return  Date
	 *
	 * @note    This method can't be type hinted due to a PHP bug: https://bugs.php.net/bug.php?id=61483
	 */
	public function setTimezone($tz):\DateTime {
		$this->tz = $tz;

		return parent::setTimezone($tz);
	}

	/**
	 * Gets the date as an ISO 8601 string.  IETF RFC 3339 defines the ISO 8601 format
	 * and it can be found at the IETF Web site.
	 *
	 * @param   boolean  $local  True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string  The date string in ISO 8601 format.
	 *
	 * @link    http://www.ietf.org/rfc/rfc3339.txt
	 */
	public function toISO8601($local = false) {
		return $this->format(\DateTime::RFC3339, $local, false);
	}

	/**
	 * Gets the date as an RFC 822 string.  IETF RFC 2822 supercedes RFC 822 and its definition
	 * can be found at the IETF Web site.
	 *
	 * @param   boolean  $local  True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string   The date string in RFC 822 format.
	 *
	 * @link    http://www.ietf.org/rfc/rfc2822.txt
	 */
	public function toRFC822($local = false) {
		return $this->format(\DateTime::RFC2822, $local, false);
	}

	public function toMysql($local = false) {
		return $this->format('Y-m-d H:i:s', $local, false);
	}

	/**
	 * Gets the date as UNIX time stamp.
	 *
	 * @return  integer  The date as a UNIX timestamp.
	 *
	 */
	public function toUnix() {
		return (int) parent::format('U');
	}

	/** create a local instance from given format */
	public static function createFromFormat($format, $timeString, $timezone = null): \DateTime/*|FALSE*/ {
		if ($timezone == null) {
			$timezone = new \DateTimeZone(date_default_timezone_get());
		}
		if (is_string($timezone)) {
			$timezone = new \DateTimeZone($timezone);
		}
		$d = \DateTime::createFromFormat($format, $timeString, $timezone);
		if ($d !== FALSE) {
			return self::createLocalInstance((int)$d->format('U'), $timezone);
		}
		return FALSE;
	}

	public static function createFromMysql($timeString, $timezone = null) {
		return new Date($timeString, $timezone);
	}

	/** create a local instance from UNIX timestamp */
	public static function createLocalInstance($timestamp, $timezone = null) {
		if ($timezone == null) {
			$timezone = new \DateTimeZone(date_default_timezone_get());
		}
		if (is_string($timezone)) {
			$timezone = new \DateTimeZone($timezone);
		}
		$rc = new Date($timestamp, 'UTC');
		$rc->setTimezone($timezone);
		return $rc;
	}
}

