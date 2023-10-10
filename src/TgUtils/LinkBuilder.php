<?php

namespace TgUtils;

/** Helpers to construct URLs from objects */
interface LinkBuilder {

	const LIST   = 'list';
	const VIEW   = 'view';
	const CREATE = 'create';
	const EDIT   = 'edit';
	const DELETE = 'delete';
	const COPY   = 'copy';

	/**
	  * Constructs and returns the link.
	  * @param mixed $subject - the subject of the link (an object or method or string or anything)
	  * @param string $action - the action that the link will fulfill with the subject
	  * @param array  $params - parameters, either for the link construction process or the GET parameters in the link
	  * @return string - the link constructed, targeting the subject with the action and optional parameters.
	  */
	public function getLink($subject, $action = self::VIEW, $params = NULL);

}

