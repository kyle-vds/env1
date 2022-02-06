<?php
/**
 * Markdown  -  A text-to-HTML conversion tool for web writers
 *
 * @package   php-markdown
 * @author    Charlie Jonas <charlie@charliejonas.co.uk>
 * @author    Michel Fortin <michel.fortin@michelf.com>
 * @copyright (changes) 2017 Charlie Jonas <https://github.com/CHTJonas/roomballot>
 * @copyright 2004-2016 Michel Fortin <https://michelf.com/projects/php-markdown/>
 * @copyright (Original Markdown) 2004-2006 John Gruber <https://daringfireball.net/projects/markdown/>
 */

 /**
  * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
  * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
  * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
  * Neither the name "Markdown" nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
  * This software is provided by the copyright holders and contributors "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the copyright owner or contributors be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
  */

interface MarkdownInterface {
	/**
	 * Initialize the parser and return the result of its transform method.
	 * This will work fine for derived classes too.
	 *
	 * @api
	 *
	 * @param  string $text
	 * @return string
	 */
	public static function defaultTransform($text);

	/**
	 * Main function. Performs some preprocessing on the input text
	 * and pass it through the document gamut.
	 *
	 * @api
	 *
	 * @param  string $text
	 * @return string
	 */
	public function transform($text);
}